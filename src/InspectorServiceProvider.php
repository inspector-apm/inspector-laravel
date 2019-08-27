<?php

namespace Inspector\Laravel;

use Illuminate\Contracts\Container\Container;
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

        // Bind Inspector service
        $this->app->singleton('inspector', function (Container $app) {
            $configuration = new Configuration(config('inspector.key'));
            $configuration->setUrl(config('inspector.url'))
                ->setTransport(config('inspector.transport'))
                ->setOptions(config('inspector.options'))
                ->setEnabled(config('inspector.enable'));

            $inspector = new Inspector($configuration);

            // Start a transaction if the app is running in console
            if ($app->runningInConsole() && Filters::isApprovedArtisanCommand()) {
                $inspector->startTransaction(implode(' ', $_SERVER['argv']));
            }

            if (config('inspector.unhandled_exceptions')) {
                $app->register(UnhandledExceptionServiceProvider::class);
            }

            if(config('inspector.query')){
                $this->app->register(DatabaseQueryServiceProvider::class);
            }

            if (config('inspector.job')) {
                $app->register(JobServiceProvider::class);
            }

            if (config('inspector.email')) {
                $app->register(EmailServiceProvider::class);
            }

            return $inspector;
        });
    }
}
