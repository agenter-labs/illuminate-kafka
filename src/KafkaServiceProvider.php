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
        $this->mergeConfigFrom(__DIR__ . '/../config/kafka.php', 'kafka');

        $this->app->singleton('event.producer', function ($app) {
            return new Producer(
                $app['config']->get('kafka.brokers'),
                $app['config']->get('kafka.enabled'),
                $app['config']->get('app.env')
            );
        });
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
