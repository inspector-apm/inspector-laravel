<?php

namespace LogEngine\Laravel;


use Illuminate\Contracts\Container\Container;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\ServiceProvider;
use Illuminate\Foundation\Application as LaravelApplication;
use Laravel\Lumen\Application as LumenApplication;
use Illuminate\Database\Events\QueryExecuted;
use LogEngine\Configuration;
use LogEngine\Laravel\Facades\ApmAgent;

class LogEngineServiceProvider extends ServiceProvider
{
    /**
     * Booting of services.
     *
     * @return void
     */
    public function boot()
    {
        $this->setupConfigFile();
        $this->setupQueryMonitoring(config('logengine'));
    }

    /**
     * Setup configuration file.
     */
    protected function setupConfigFile()
    {
        $source = realpath($raw = __DIR__ . '/../config/logengine.php') ?: $raw;
        if ($this->app instanceof LaravelApplication && $this->app->runningInConsole()) {
            $this->publishes([$source => config_path('logengine.php')]);
        } elseif ($this->app instanceof LumenApplication) {
            $this->app->configure('logengine');
        }

        $this->mergeConfigFrom($source, 'logengine');
    }

    protected function interceptLogs()
    {
        if (class_exists(MessageLogged::class)) {
            // starting from L5.4 MessageLogged event class was introduced
            // https://github.com/laravel/framework/commit/57c82d095c356a0fe0f9381536afec768cdcc072
            $this->app['events']->listen(MessageLogged::class, function($log) {
                $this->handleExceptionLog($log->message, $log->context);
            });
        } else {
            $this->app['events']->listen('illuminate.log', function($level, $message, $context) {
                $this->handleExceptionLog($message, $context);
            });
        }
    }

    protected function handleExceptionLog($message, $context)
    {
        if(
            isset($context['exception']) &&
            ($context['exception'] instanceof \Exception || $context['exception'] instanceof \Throwable)
        ){
            ApmAgent::reportException($context['exception']);
        }

        if($message instanceof \Exception || $message instanceof \Throwable){
            ApmAgent::reportException($message);
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

        $showBindings = isset($config['bindings']) && $config['bindings'];

        if (class_exists(QueryExecuted::class)) {
            $this->app['events']->listen(QueryExecuted::class, function (QueryExecuted $query) use ($showBindings) {
                $span = ApmAgent::startSpan('query');

                $span->getContext()->getDb()
                    ->setType($query->connectionName)
                    ->setSql($query->sql);

                if($showBindings){
                    $span->getContext()->getDb()->setBindings($query->bindings);
                }

                $span->end($query->time);
            });
        } else {
            $this->app['events']->listen('illuminate.query', function ($sql, array $bindings, $time, $connection) use ($showBindings) {
                $span = ApmAgent::startSpan('query');

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
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        // Bind log engine service
        $this->app->singleton('logengine', function (Container $app) {
            $configuration = new Configuration(config('logengine.key'));
            $configuration->setUrl(config('logengine.url'))
                ->setOptions(config('logengine.options'))
                ->setEnabled(config('logengine.enable'));

            $apm = new \LogEngine\ApmAgent($configuration);

            if ($app->runningInConsole()) {
                $apm->startTransaction(implode(' ', $_SERVER['argv']));
            }

            return $apm;
        });
    }
}