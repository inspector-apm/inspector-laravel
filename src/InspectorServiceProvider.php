<?php

namespace Inspector\Laravel;

use Illuminate\Support\ServiceProvider;
use Illuminate\Foundation\Application as LaravelApplication;
use Inspector\Inspector;
use Inspector\Laravel\Providers\DatabaseQueryServiceProvider;
use Inspector\Laravel\Providers\EmailServiceProvider;
use Inspector\Laravel\Providers\JobServiceProvider;
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
        //
        $this->mergeConfigFrom(__DIR__ . '/../config/inspector.php', 'inspector');

        if(config('inspector.enable')){
            $this->registerInspectorService();

            // Start a transaction if the app is running in console
            if ($this->app->runningInConsole() && Filters::isApprovedArtisanCommand()) {
                $this->app['inspector']->startTransaction(implode(' ', $_SERVER['argv']));
            }
        }
    }

    /**
     * Bind Inspector service and providers
     */
    public function registerInspectorService()
    {
        // Bind Inspector service
        $this->app->singleton('inspector', function () {
            return new Inspector(
                (new Configuration(config('inspector.key')))
                    ->setUrl(config('inspector.url'))
                    ->setTransport(config('inspector.transport'))
                    ->setOptions(config('inspector.options'))
            );
        });

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
    }
}
