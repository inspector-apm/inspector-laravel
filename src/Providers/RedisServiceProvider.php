<?php


namespace Inspector\Laravel\Providers;


use Illuminate\Redis\Events\CommandExecuted;
use Illuminate\Support\ServiceProvider;
use Inspector\Laravel\Facades\Inspector;

class RedisServiceProvider extends ServiceProvider
{
    /**
     * Booting of services.
     *
     * @return void
     */
    public function boot()
    {
        $this->app['events']->listen(CommandExecuted::class, function (CommandExecuted $event) {
            if (Inspector::isRecording()) {
                Inspector::startSegment('redis', "redis:{$event->command}")
                    ->start(microtime(true) - ($event->time/1000))
                    ->addContext('data', [
                        'connection' => $event->connectionName,
                        'parameters' => $event->parameters
                    ])
                    ->end($event->time);
            }
        });

        foreach ((array) $this->app['redis']->connections() as $connection) {
            $connection->setEventDispatcher($this->app['events']);
        }

        $this->app['redis']->enableEvents();
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
