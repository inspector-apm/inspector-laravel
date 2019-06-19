<?php

namespace Inspector\Laravel;


use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;
use Illuminate\Foundation\Application as LaravelApplication;
use Laravel\Lumen\Application as LumenApplication;
use Inspector\Configuration;

class InspectorServiceProvider extends ServiceProvider
{
    /**
     * Booting of services.
     *
     * @return void
     */
    public function boot()
    {
        $this->setupConfigFile();
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

        if(config('inspector.unhandled_exceptions')){
            $this->app->register(UnhandledExceptionServiceProvider::class);
        }

        if(config('inspector.query', false)) {
            $this->app->register(DatabaseQueryServiceProvider::class);
        }

        if(config('inspector.job', false)) {
            $this->app->register(JobServiceProvider::class);
        }

        if(config('inspector.email', false)) {
            $this->app->register(EmailServiceProvider::class);
        }
    }
}