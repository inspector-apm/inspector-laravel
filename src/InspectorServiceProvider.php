<?php

namespace Inspector\Laravel;

use Illuminate\Support\ServiceProvider;
use Illuminate\Foundation\Application as LaravelApplication;
use Inspector\Laravel\Commands\TestCommand;
use Inspector\Laravel\Providers\CommandServiceProvider;
use Inspector\Laravel\Providers\DatabaseQueryServiceProvider;
use Inspector\Laravel\Providers\EmailServiceProvider;
use Inspector\Laravel\Providers\GateServiceProvider;
use Inspector\Laravel\Providers\JobServiceProvider;
use Inspector\Laravel\Providers\NotificationServiceProvider;
use Inspector\Laravel\Providers\RedisServiceProvider;
use Inspector\Laravel\Providers\UnhandledExceptionServiceProvider;
use Laravel\Lumen\Application as LumenApplication;
use Inspector\Configuration;

class InspectorServiceProvider extends ServiceProvider
{
    /**
     * The latest version of the client library.
     *
     * @var string
     */
    const VERSION = '4.6.5';

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
                TestCommand::class,
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
        // Default package configuration
        $this->mergeConfigFrom(__DIR__ . '/../config/inspector.php', 'inspector');

        // Bind Inspector service class
        $this->app->singleton('inspector', function () {
            $configuration = (new Configuration(config('inspector.key')))
                ->setEnabled(config('inspector.enable'))
                ->setUrl(config('inspector.url'))
                ->setVersion(self::VERSION)
                ->setTransport(config('inspector.transport'))
                ->setOptions(config('inspector.options'))
                ->setMaxItems(config('inspector.max_items'));

            return new Inspector($configuration);
        });

        $this->registerInspectorServiceProviders();
    }

    /**
     * Bind Inspector service providers based on package configuration.
     */
    public function registerInspectorServiceProviders()
    {
        if ($this->app->runningInConsole() && Filters::isApprovedArtisanCommand(config('inspector.ignore_commands'))) {
            $this->app->register(CommandServiceProvider::class);
        }

        $this->app->register(GateServiceProvider::class);

        // For Laravel >=6
        if (config('inspector.redis') && strpos($this->app->version(), '5') === false) {
            $this->app->register(RedisServiceProvider::class);
        }

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
