<?php

namespace Inspector\Laravel;


use Illuminate\Contracts\Container\Container;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;
use Illuminate\Foundation\Application as LaravelApplication;
use Laravel\Lumen\Application as LumenApplication;
use Illuminate\Database\Events\QueryExecuted;
use Inspector\Configuration;

class InspectorServiceProvider extends ServiceProvider
{
    /**
     * Collection of mail event spans.
     *
     * @var array
     */
    protected $spansMail = [];

    /**
     * Booting of services.
     *
     * @return void
     */
    public function boot()
    {
        $this->setupConfigFile();

        if(config('inspector.unhandled_exceptions')){
            $this->reportUnhandledExceptions();
        }

        if(config('inspector.query', false)) {
            $this->setupQueryMonitoring();
        }

        if(config('inspector.job', false)) {
            $this->setupJobProcessMonitoring();
        }

        if(config('inspector.email', false)) {
            $this->setupEmailMonitoring();
        }
    }

    /**
     * Setup configuration file.
     */
    protected function setupConfigFile()
    {
        $source = realpath($raw = __DIR__ . '/../config/inspector.php') ?: $raw;
        if ($this->app instanceof LaravelApplication && $this->app->runningInConsole()) {
            $this->publishes([$source => config_path('inspector.php')]);
        } elseif ($this->app instanceof LumenApplication) {
            $this->app->configure('inspector');
        }

        $this->mergeConfigFrom($source, 'inspector');
    }

    protected function reportUnhandledExceptions()
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
        if (!$this->app['inspector']->hasTransaction()) {
            $this->app['inspector']->startTransaction(implode(' ', $_SERVER['argv']));
        }

        if (
            isset($context['exception']) &&
            ($context['exception'] instanceof \Exception || $context['exception'] instanceof \Throwable)
        ) {
            $this->app['inspector']->reportException($context['exception']);
        }

        if ($message instanceof \Exception || $message instanceof \Throwable) {
            $this->app['inspector']->reportException($message);
        }
    }

    /**
     * Add a span for database interaction.
     */
    protected function setupQueryMonitoring()
    {
        if (class_exists(QueryExecuted::class)) {
            $this->app['events']->listen(QueryExecuted::class, function (QueryExecuted $query) {
                $this->handleQueryReport($query->sql, $query->bindings, $query->time, $query->connectionName);
            });
        } else {
            $this->app['events']->listen('illuminate.query', function ($sql, array $bindings, $time, $connection) {
                $this->handleQueryReport($sql, $bindings, $time, $connection);
            });
        }
    }

    /**
     * Add a span for scheduled job executions.
     */
    protected function setupJobProcessMonitoring()
    {
        Queue::looping(function () {
            $this->app['inspector']->flush();
        });

        $this->app['events']->listen(JobProcessing::class, function (JobProcessing $event) {
            if(!$this->app['inspector']->hasTransaction()){
                $this->app['inspector']->startTransaction($event->job->resolveName());
            }
        });
    }

    /**
     * Add a span for email.
     */
    protected function setupEmailMonitoring()
    {
        $this->app['events']->listen(MessageSending::class, function (MessageSending $event){
            $this->spansMail[$event->message->getId()] = $this->app['inspector']->startSpan('email');
        });

        $this->app['events']->listen(MessageSent::class, function (MessageSent $event){
            if(array_key_exists($event->message->getId(), $this->spansMail)){
                $this->spansMail[$event->message->getId()]->end();
            }
        });
    }

    /**
     * Attach a span to monitor query execution.
     *
     * @param $sql
     * @param array $bindings
     * @param $time
     * @param $connection
     */
    protected function handleQueryReport($sql, array $bindings, $time, $connection)
    {
        if (!$this->app['inspector']->hasTransaction()) {
            return;
        }

        $span = $this->app['inspector']->startSpan($connection);

        $span->getContext()
            ->getDb()
            ->setType($connection)
            ->setSql($sql);

        if (config('inspector.bindings', false)) {
            $span->getContext()->getDb()->setBindings($bindings);
        }

        $span->end($time);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        // Bind Inspector service
        $this->app->singleton('inspector', function (Container $app) {
            $configuration = new Configuration(config('inspector.key'));
            $configuration->setUrl(config('inspector.url'))
                ->setOptions(config('inspector.options'))
                ->setEnabled(config('inspector.enable'));

            $inspector = new \Inspector\Inspector($configuration);

            if ($app->runningInConsole()) {
                $inspector->startTransaction(implode(' ', $_SERVER['argv']));
            }

            return $inspector;
        });
    }
}