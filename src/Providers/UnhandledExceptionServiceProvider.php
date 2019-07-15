<?php


namespace Inspector\Laravel\Providers;


use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class UnhandledExceptionServiceProvider extends ServiceProvider
{
    /**
     * Booting of services.
     *
     * @return void
     */
    public function boot()
    {
        if (class_exists(MessageLogged::class)) {
            // starting from L5.4 MessageLogged event class was introduced
            // https://github.com/laravel/framework/commit/57c82d095c356a0fe0f9381536afec768cdcc072
            $this->app['events']->listen(MessageLogged::class, function ($log) {
                $this->handleExceptionLog($log->message, $log->context);
            });
        } else {
            $this->app['events']->listen('illuminate.log', function ($level, $message, $context) {
                $this->handleExceptionLog($message, $context);
            });
        }
    }

    protected function handleExceptionLog($message, $context)
    {
        if (
            isset($context['exception']) &&
            ($context['exception'] instanceof \Exception || $context['exception'] instanceof \Throwable)
        ) {
            $this->reportException($context['exception']);
        }

        if ($message instanceof \Exception || $message instanceof \Throwable) {
            $this->reportException($message);
        }
    }

    protected function reportException(\Throwable $exception)
    {
        if (!$this->app['inspector']->isRecording()) {
            $this->app['inspector']->startTransaction(get_class($exception));
        }

        $error = $this->app['inspector']->reportException($exception, false);;

        if (Auth::check() && config('inspector.user')) {
            $error->withUser(
                Auth::user()->getAuthIdentifier(),
                Auth::user()->getAuthIdentifierName()
            );
        }
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