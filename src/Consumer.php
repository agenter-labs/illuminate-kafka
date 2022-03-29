<?php

namespace AgenterLab\Kafka;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

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
        $consumer->subscribe(config('kafka.topics', []));

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

        $name = str_replace('.', '-', $message->topic_name);
        $listeners = config('kafka.listeners.' . $name);

        if (!$listeners) {
            $this->error('Listener not set for topic ' . $message->topic_name);
            return;
        }

        $payload = json_decode($message->payload, true);

        Log::debug('Process topic: ' . $message->topic_name);

        if (config('kafka.debug')) {
            Log::debug($payload);
        }
        
        $headers = $payload['headers'] ?? [];
        $body = $payload['body'] ?? null;

        if (is_string($listeners)) {
            return $this->runListener($listeners, $headers, $body);
        }

        foreach($listeners as $listener) {
            $this->runListener($listener, $headers, $body);
        }
    }

    private function runListener($listener, $headers, $body) {
        return (new $listener($headers, $body))->handle();
    }

}
