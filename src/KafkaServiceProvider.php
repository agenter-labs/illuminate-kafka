<?php

namespace AgenterLab\Kafka;

use Illuminate\Support\ServiceProvider;


class KafkaServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {

        $this->app->singleton('event.producer', function ($app) {
            return new Producer(
                config('Kafka.brokers'),
                config('Kafka.enabled'),
                config('app.env')
            );
        });

        $this->mergeConfigFrom(__DIR__ . '/../config/kafka.php', 'kafka');
    }

    /**
     * Boot the authentication services for the application.
     *
     * @return void
     */
    public function boot()
    {
        
        
    }
}
