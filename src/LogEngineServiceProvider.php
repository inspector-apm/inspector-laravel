<?php

namespace LogEngine\Laravel;


use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;
use LogEngine\LogEngine;
use Illuminate\Foundation\Application as LaravelApplication;
use Laravel\Lumen\Application as LumenApplication;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\QueueManager;

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
        $this->setupQueryMonitoring($this->app->events, $this->app->config->get('logengine'));
        $this->setupQueueMonitoring($this->app->queue);
    }

    /**
     * Setup configuration file.
     */
    public function setupConfig()
    {
        $source = realpath($raw = __DIR__.'/../config/logengine.php') ?: $raw;
        if ($this->app instanceof LaravelApplication && $this->app->runningInConsole()) {
            $this->publishes([$source => config_path('logengine.php')]);
        } elseif ($this->app instanceof LumenApplication) {
            $this->app->configure('logengine');
        }

        $this->mergeConfigFrom($source, 'logengine');
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

                $this->app->logengine->debug(
                    'Query executed',
                    [
                        'query' => $this->formatQuery($query->sql, $showBindings ? $query->bindings : [], $query->time, $query->connectionName)
                    ]
                );

            });
        } else {
            $events->listen('illuminate.query', function ($sql, array $bindings, $time, $connection) use ($showBindings) {

                $this->app->logengine->debug(
                    'Query executed',
                    [
                        'query' => $this->formatQuery($sql, $showBindings ? $bindings : [], $time, $connection)
                    ]
                );

            });
        }
    }

    /**
     * Register event for queue monitoring.
     *
     * @param QueueManager $queue
     */
    public function setupQueueMonitoring(QueueManager $queue)
    {
        $queue->looping(function () {
            $this->app->logengine->flush();
        });

        if (!class_exists(JobProcessing::class)) {
            return;
        }

        $queue->before(function (JobProcessing $event) {
            $job = [
                'name' => $event->job->getName(),
                'queue' => $event->job->getQueue(),
                'attempts' => $event->job->attempts(),
                'connection' => $event->connectionName,
            ];
            if (method_exists($event->job, 'resolveName')) {
                $job['resolved'] = $event->job->resolveName();
            }
            $this->app->logengine->debug('Job Processing', compact('job'));
        });
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        // Bind log engine service
        $this->app->singleton('logengine', function (Container $app) {
            $logengine = new LogEngine(config('logengine.key'));
            return $logengine->setSeverityLevel(config('logengine.severity_level'));
        });
    }

    /**
     * Format the query as breadcrumb metadata.
     *
     * @param string $sql
     * @param array  $bindings
     * @param float  $time
     * @param string $connection
     *
     * @return array
     */
    protected function formatQuery($sql, array $bindings, $time, $connection)
    {
        $data = ['sql' => $sql];
        foreach ($bindings as $index => $binding) {
            $data["binding {$index}"] = $binding;
        }
        $data['time'] = "{$time}ms";
        $data['connection'] = $connection;
        return $data;
    }
}