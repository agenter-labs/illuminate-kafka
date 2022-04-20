<?php

namespace AgenterLab\Kafka;


trait EventTestTrait
{
    /**
     * Execute topic
     * @param string $topic
     * @param mixed $body
     * @param array $headers
     * @return \Illuminate\Foundation\Application
     */
    public function executeEvent(string $topic, $body, array $headers = [])
    {
        $consumers = config('kafka.consumers.' . $topic);

        if (!$consumers) {
            throw new \OutOfBoundsException('Consumers not set for topic ' . $topic);
        }
        
        if (is_string($consumers)) {
            return $this->runConsumer($consumers, $headers, $body);
        }

        foreach($consumers as $consumer) {
            $this->runConsumer($consumer, $headers, $body);
        }
    }

    private function runConsumer($consumer, $headers, $body) {
        return (new $consumer($headers, $body))->handle();
    }
}
