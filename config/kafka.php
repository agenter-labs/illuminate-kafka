<?php

return [
    'brokers' => env('KAFKA_BROKERS', '127.0.0.1'),
    
    'group_id' => env('KAFKA_GROUP_ID', 'MyConsumer'),

    'enabled' => env('KAFKA_ENABLED', true),

    'debug' => env('KAFKA_DEBUG', true),

    'auto_create_topics' => false,

    'offset' => env('KAFKA_OFFSET_RESET', 'earliest'),
    
    'consumers' => []
];