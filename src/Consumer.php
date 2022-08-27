<?php

namespace AgenterLab\Kafka;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;
use Throwable;

class Consumer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kafka:consumer';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Consume events';
    
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Consume events';

    /**
     * Create a new console command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $conf = new \RdKafka\Conf();
        // Set a rebalance callback to log partition assignments (optional)
        $conf->setRebalanceCb(function (\RdKafka\KafkaConsumer $kafka, $err, array $partitions = null) {
            switch ($err) {
                case RD_KAFKA_RESP_ERR__ASSIGN_PARTITIONS:
                    $kafka->assign($partitions);
                    break;

                case RD_KAFKA_RESP_ERR__REVOKE_PARTITIONS:
                    $kafka->assign(NULL);
                    break;
                default:
                    throw new \Exception($err);
            }
        });

        // Configure the group.id. All consumer with the same group.id will consume
        // different partitions.
        $conf->set('group.id', config('kafka.group_id', 'MyGroup'));

        // Initial list of Kafka brokers
        $conf->set('metadata.broker.list', config('kafka.brokers', '127.0.0.1'));

        // Set where to start consuming messages when there is no initial offset in
        // offset store or the desired offset is out of range.
        // 'earliest': start from the beginning
        $conf->set('auto.offset.reset', config('kafka.offset', 'earliest'));

        if (config('kafka.auto_create_topics', false)) {
            $conf->set('allow.auto.create.topics', true);
        }
        
        $consumer = new \RdKafka\KafkaConsumer($conf);

        // Subscribe to topic 'test'
        $topics = array_keys(config('kafka.consumers', []));
        $consumer->subscribe($topics);

        $this->info("Waiting for partition assignment... 
            (make take some time when quickly re-joining the group after leaving it.)");


        while (true) {
            $message = $consumer->consume(120*1000);
            switch ($message->err) {
                case RD_KAFKA_RESP_ERR_NO_ERROR:
                    $this->process($message);
                    break;
                case RD_KAFKA_RESP_ERR__PARTITION_EOF:
                    $this->info("No more messages; will wait for more");
                    break;
                case RD_KAFKA_RESP_ERR__TIMED_OUT:
                    $this->info("Timed out");
                    break;
                default:
                    $this->error($message->errstr());
                    break;
            }
        }
    }

     /**
     * Consumer process
     */
    public function process(\RdKafka\Message $message) {
        $this->info('Process topic ' . $message->topic_name);

        $consumers = config('kafka.consumers', [])[$message->topic_name] ?? null;

        if (!$consumers) {
            $this->error('Consumers not set for topic ' . $message->topic_name);
            return;
        }

        $payload = json_decode($message->payload, true);

        if (config('kafka.debug')) {
            Log::debug($payload);
        }
        
        $headers = $payload['headers'] ?? [];
        $body = $payload['body'] ?? null;

        if (is_string($consumers)) {
            $this->runConsumer($consumers, $headers, $body);
        } else {
            foreach($consumers as $consumer) {
                $this->runConsumer($consumer, $headers, $body);
            }
        }
    }

    private function runConsumer($consumer, $headers, $body) {

        try {
            (new $consumer($headers, $body))->handle();
        } catch (\Exception $e) {
            $this->reportError($e);
        }
    }

    /**
     * Report or log an exception.
     *
     * @param \Throwable $e
     * @return void
     *
     * @throws \Exception
     */
    private function reportError(Throwable $e)
    {
        try {
            $logger = app(LoggerInterface::class);
        } catch (Exception $ex) {
            throw $e; // throw the original exception
        }

        $logger->error($e->getMessage(), ['exception' => $e]);
    }

}
