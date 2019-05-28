<?php

namespace LogEngine\Laravel;


use Illuminate\Contracts\Container\Container;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\ServiceProvider;
use LogEngine\ApmAgent;
use Illuminate\Foundation\Application as LaravelApplication;
use Laravel\Lumen\Application as LumenApplication;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\QueueManager;
use LogEngine\Configuration;
use LogEngine\Laravel\Middleware\InstrumentingWebRequest;

class LogEngineServiceProvider extends ServiceProvider
{
    /**
     * Booting of services.
     *
     * @return void
     */
    public function boot()
    {
        $this->setupConfig();
        $this->registerMiddleware();
        $this->setupQueryMonitoring($this->app->events, $this->app->config->get('logengine'));
        //$this->setupQueueMonitoring($this->app->queue);
    }

    /**
     * Setup configuration file.
     */
    public function setupConfig()
    {
        $source = realpath($raw = __DIR__ . '/../config/logengine.php') ?: $raw;
        if ($this->app instanceof LaravelApplication && $this->app->runningInConsole()) {
            $this->publishes([$source => config_path('logengine.php')]);
        } elseif ($this->app instanceof LumenApplication) {
            $this->app->configure('logengine');
        }

        $this->mergeConfigFrom($source, 'logengine');
    }

    /**
     * Register middleware to intercept and instrumenting web requests.
     */
    protected function registerMiddleware()
    {
        $this->app->router->middleware(InstrumentingWebRequest::class);
    }

    /**
     * Register event for database interaction monitoring.
     *
     * @param \Illuminate\Contracts\Events\Dispatcher $events
     * @param array $config
     */
    public function setupQueryMonitoring($events, $config)
    {
        if (isset($config['query']) && !$config['query']) {
            return;
        }

        $showBindings = isset($config['bindings']) && $config['bindings'];

        if (class_exists(QueryExecuted::class)) {
            $events->listen(QueryExecuted::class, function (QueryExecuted $query) use ($showBindings) {
                $span = $this->app->logengine->startSpan('query');

                $span->getContext()->getDb()
                    ->setType($query->connectionName)
                    ->setSql($query->sql);

                if($showBindings){
                    $span->getContext()->getDb()->setBindings($query->bindings);
                }

                $span->end($query->time);
            });
        } else {
            $events->listen('illuminate.query', function ($sql, array $bindings, $time, $connection) use ($showBindings) {
                $span = $this->app->logengine->startSpan('query');

                $span->getContext()->getDb()
                    ->setType($connection)
                    ->setSql($sql);

                if($showBindings){
                    $span->getContext()->getDb()->setBindings($bindings);
                }

                $span->end($time);
            });
        }
    }

    /**
     * Register event for queue monitoring.
     *
     * @param QueueManager $queue
     */
    /*public function setupQueueMonitoring(QueueManager $queue)
    {
        if (!class_exists(JobProcessing::class)) {
            return;
        }

        $queue->before(function (JobProcessing $event) {
        });

        $queue->after(function (JobProcessed $event) {
        });
    }*/

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        // Bind log engine service
        $this->app->singleton('logengine', function (Container $app) {
            $configuration = new Configuration(config('logengine.key'));
            $configuration->setUrl(config('logengine.url'));
            $configuration->setOptions(config('logengine.options'));

            $apm = new ApmAgent($configuration);

            if ($app->runningInConsole()) {
                $apm->startTransaction(implode(' ', $_SERVER['argv']));
            }

            return $apm;
        });
    }
}