<?php

declare(strict_types=1);

namespace Inspector\Laravel\Providers;

use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\ServiceProvider;
use Inspector\Laravel\Facades\Inspector;
use Throwable;

use function array_merge;
use function class_exists;

class ExceptionsServiceProvider extends ServiceProvider
{
    /**
     * Booting of services.
     */
    public function boot(): void
    {
        if (class_exists(MessageLogged::class)) {
            // starting from L5.4 MessageLogged event class was introduced
            // https://github.com/laravel/framework/commit/57c82d095c356a0fe0f9381536afec768cdcc072
            $this->app['events']->listen(MessageLogged::class, function (MessageLogged $log): void {
                $this->handleLog($log->level, $log->message, $log->context);
            });
        } else {
            $this->app['events']->listen('illuminate.log', function (string $level, $message, $context): void {
                $this->handleLog($level, $message, $context);
            });
        }
    }

    /**
     * Attach the event to the current transaction.
     */
    protected function handleLog(string $level, mixed $message, mixed $context): void
    {
        if (
            isset($context['exception']) &&
            $context['exception'] instanceof Throwable
        ) {
            $this->reportException($context['exception']);
            return;
        }

        if ($message instanceof Throwable) {
            $this->reportException($message);
            return;
        }

        // Report general log messages
        if (Inspector::hasTransaction()) {
            Inspector::transaction()
                ->addContext('logs', array_merge(
                    Inspector::transaction()->getContext()['logs'] ?? [],
                    [
                        ['level' => $level, 'message' => $message]
                    ]
                ));
        }
    }

    protected function reportException(Throwable $exception): void
    {
        Inspector::reportException($exception, false);
        Inspector::transaction()->setResult('error');
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        //
    }
}
