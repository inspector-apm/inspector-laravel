<?php

namespace Inspector\Laravel;

use Illuminate\Support\ServiceProvider;
use Illuminate\Foundation\Application as LaravelApplication;
use Inspector\Inspector;
use Inspector\Laravel\Commands\InspectorTest;
use Inspector\Laravel\Providers\DatabaseQueryServiceProvider;
use Inspector\Laravel\Providers\EmailServiceProvider;
use Inspector\Laravel\Providers\JobServiceProvider;
use Inspector\Laravel\Providers\NotificationServiceProvider;
use Inspector\Laravel\Providers\UnhandledExceptionServiceProvider;
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

        if ($this->app->runningInConsole()) {
            $this->commands([
                InspectorTest::class,
            ]);
        }
    }

    /**
     * Setup configuration file.
     */
    protected function setupConfigFile()
    {
        if ($this->app instanceof LaravelApplication && $this->app->runningInConsole()) {
            $this->publishes([__DIR__ . '/../config/inspector.php' => config_path('inspector.php')]);
        } elseif ($this->app instanceof LumenApplication) {
            $this->app->configure('inspector');
        }
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/inspector.php', 'inspector');

        // Bind Inspector service class
        $this->app->singleton('inspector', function () {
            $configuration = (new Configuration(config('inspector.enable') ? config('inspector.key') : null))
                ->setUrl(config('inspector.url'))
                ->setTransport(config('inspector.transport'))
                ->setOptions(config('inspector.options'));

            return new Inspector($configuration);
        });

        // Start a transaction if the app is running in console
        if ($this->app->runningInConsole() && Filters::isApprovedArtisanCommand(config('inspector.ignore_commands'))) {
            $this->app['inspector']->startTransaction(implode(' ', $_SERVER['argv']));
        }

        $this->registerInspectorServiceProviders();
    }

    /**
     * Bind Inspector service providers based on package configuration.
     */
    public function registerInspectorServiceProviders()
    {
        if (config('inspector.unhandled_exceptions')) {
            $this->app->register(UnhandledExceptionServiceProvider::class);
        }

        if(config('inspector.query')){
            $this->app->register(DatabaseQueryServiceProvider::class);
        }

        if (config('inspector.job')) {
            $this->app->register(JobServiceProvider::class);
        }

        if (config('inspector.email')) {
            $this->app->register(EmailServiceProvider::class);
        }

        if (config('inspector.notifications')) {
            $this->app->register(NotificationServiceProvider::class);
        }
    }
}
