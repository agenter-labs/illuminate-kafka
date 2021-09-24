<?php

namespace AgenterLab\Kafka;

/**
 * Kafka event producer
 */
class Producer {

    /**
     * Producer instance.
     * 
     * @var \RdKafka\Producer
     */
    private $producer;

    /**
     * @var boolean
     */
    private $purge = false;

    /**
     * @var array
     */
    private $headers = [];

    /**
     * Construct producer
     */
    public function __construct($brokers = '127.0.0.1', $enabled = true, $env = 'development') {

        if (defined('RD_KAFKA_PARTITION_UA') && $enabled) {
            
            $conf = new \RdKafka\Conf();
            $conf->set('metadata.broker.list', $brokers);

            if ($env != 'production') {
                $conf->set('log_level', (string) LOG_DEBUG);
                $conf->set('debug', 'all');
            }
            $this->producer = new \RdKafka\Producer($conf);

            $this->purge = method_exists($this->producer, 'purge');
        }
    }

    /**
     * Set Headers
     * 
     * @param array $headers
     */
    public function setHeaders(array $headers = []) {
        $this->headers = array_merge($this->headers, $headers);
    }

    /**
     * Send to kafka
     * 
     * @param string $topic Event topic
     * @param string|array $message Message send to event consumers
     * @param string|int $key Partition key
     * @param array $headers Header information
     */
    public function fire(string $topic, $message, $key = null, array $headers = []) {

        if (!$this->producer) {
            return false;
        }

        $headers = array_merge($this->headers, $headers);

        $headers['time'] = time();

        $header['client'] = [
            'ip' => request()->ip(),
            'user-agent' => request()->userAgent()
        ];

        $payload = json_encode(['body' => $message, 'headers' => $headers]);

        $topic = $this->producer->newTopic($topic);

        // RD_KAFKA_PARTITION_UA, lets librdkafka choose the partition.
        // Messages with the same "$key" will be in the same topic partition.
        // This ensure that messages are consumed in order.
        $topic->produce(RD_KAFKA_PARTITION_UA, 0, $payload, $key);

        // pull for any events
        $this->producer->poll(0);

        $this->producer->flush(10000);

        if ($this->purge) {
            $this->producer->purge(RD_KAFKA_PURGE_F_QUEUE);
        }
    }
}