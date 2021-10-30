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
        $listeners = config('kafka.listeners.' . $topic);

        if (!$listeners) {
            throw new \OutOfBoundsException('Listener not set for topic ' . $topic);
        }
        
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
