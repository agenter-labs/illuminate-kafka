<?php

namespace AgenterLab\Kafka;

abstract class AbstractConsumer
{

    /**
     * Header
     * 
     * @var array
     */
    protected $header = [];

    /**
     * Payload
     * 
     * @var array|string|int
     */
    protected $payload;

    /**
     * Constructor
     */
    public function __construct(array $header, $payload) {
        $this->header = $header;
        $this->payload = $payload;
    }

    /**
     * Handle Listener
     */
    abstract function handle();
}