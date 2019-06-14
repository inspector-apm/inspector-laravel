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
use Inspector\Laravel\Facades\Inspector;

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
        $this->interceptLogs();
        $this->setupQueryMonitoring(config('inspector'));
        $this->setupJobProcessMonitoring();
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

    protected function interceptLogs()
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
        if (!Inspector::hasTransaction()) {
            Inspector::startTransaction('Error');
        }

        if (
            isset($context['exception']) &&
            ($context['exception'] instanceof \Exception || $context['exception'] instanceof \Throwable)
        ) {
            Inspector::reportException($context['exception']);
        }

        if ($message instanceof \Exception || $message instanceof \Throwable) {
            Inspector::reportException($message);
        }
    }

    /**
     * Register event for database interaction monitoring.
     *
     * @param array $config
     */
    protected function setupQueryMonitoring($config)
    {
        if (isset($config['query']) && !$config['query']) {
            return;
        }

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

    protected function setupJobProcessMonitoring()
    {
        Queue::looping(function () {
            Inspector::flush();
        });

        $this->app['events']->listen(JobProcessing::class, function (JobProcessing $event) {
            if(!Inspector::hasTransaction()){
                Inspector::startTransaction($event->job->resolveName());
            }
        });
    }

    protected function setupEmailMonitoring()
    {
        $this->app['events']->listen(MessageSending::class, function (MessageSending $event){
            $this->spansMail[$event->message->getId()] = Inspector::startSpan('email');
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
        if (!Inspector::hasTransaction()) {
            return;
        }

        $span = Inspector::startSpan('DB');

        $span->getContext()->getDb()
            ->setType($connection)
            ->setSql($sql);

        if (config('bindings')) {
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