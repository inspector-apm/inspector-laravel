<?php

declare(strict_types=1);

namespace Inspector\Laravel\Providers;

use Illuminate\Redis\Events\CommandExecuted;
use Illuminate\Support\ServiceProvider;
use Inspector\Laravel\Facades\Inspector;

use function microtime;

class RedisServiceProvider extends ServiceProvider
{
    /**
     * Booting of services.
     */
    public function boot(): void
    {
        $this->app['events']->listen(CommandExecuted::class, function (CommandExecuted $event): void {
            if (Inspector::canAddSegments()) {
                Inspector::startSegment('db.redis', "redis:{$event->command}")
                    ->start(microtime(true) - ($event->time / 1000))
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
     */
    public function register(): void
    {
        //
    }
}
