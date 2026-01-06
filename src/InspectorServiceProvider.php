<?php

declare(strict_types=1);

namespace Inspector\Laravel;

use Illuminate\Contracts\View\Engine;
use Illuminate\Contracts\View\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Foundation\Application;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Factory as ViewFactory;
use Inspector\Laravel\Commands\TestCommand;
use Inspector\Laravel\Providers\CommandServiceProvider;
use Inspector\Laravel\Providers\DatabaseQueryServiceProvider;
use Inspector\Laravel\Providers\EmailServiceProvider;
use Inspector\Laravel\Providers\GateServiceProvider;
use Inspector\Laravel\Providers\HttpClientServiceProvider;
use Inspector\Laravel\Providers\JobServiceProvider;
use Inspector\Laravel\Providers\LivewireServiceProvider;
use Inspector\Laravel\Providers\NotificationServiceProvider;
use Inspector\Laravel\Providers\RedisServiceProvider;
use Inspector\Laravel\Providers\ExceptionsServiceProvider;
use Inspector\Laravel\Views\ViewEngineDecorator;
use Inspector\Configuration;

use function class_exists;
use function version_compare;

class InspectorServiceProvider extends ServiceProvider
{
    /**
     * The latest version of the client library.
     *
     * @var string
     */
    public const VERSION = '4.18.4';

    /**
     * Booting of services.
     */
    public function boot(): void
    {
        $this->setupConfigFile();

        $this->commands([
            TestCommand::class,
        ]);
    }

    /**
     * Setup configuration file.
     */
    protected function setupConfigFile(): void
    {
        if ($this->app instanceof Application) {
            $this->publishes([__DIR__ . '/../config/inspector.php' => config_path('inspector.php')]);
        } elseif ($this->app::class === 'Laravel\Lumen\Application') {
            $this->app->configure('inspector');
        }
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        // Default package configuration
        $this->mergeConfigFrom(__DIR__ . '/../config/inspector.php', 'inspector');

        // Bind Inspector service class
        $this->app->singleton('inspector', function (): \Inspector\Laravel\Inspector {
            $configuration = (new Configuration(config('inspector.key')))
                ->setEnabled(config('inspector.enable', true))
                ->setUrl(config('inspector.url', 'https://ingest.inspector.dev'))
                ->setVersion(self::VERSION)
                ->setTransport(config('inspector.transport', 'async'))
                ->setOptions(config('inspector.options', []))
                ->setMaxItems(config('inspector.max_items', 100));

            return new Inspector($configuration);
        });

        $this->registerInspectorServiceProviders();
    }

    /**
     * Bind Inspector service providers based on package configuration.
     */
    public function registerInspectorServiceProviders(): void
    {
        $this->app->register(CommandServiceProvider::class);

        $this->app->register(GateServiceProvider::class);

        // For Laravel >=6
        if (config('inspector.redis', true) && version_compare(app()->version(), '6.0.0', '>=')) {
            $this->app->register(RedisServiceProvider::class);
        }

        if (config('inspector.unhandled_exceptions', true)) {
            $this->app->register(ExceptionsServiceProvider::class);
        }

        if (config('inspector.query', true)) {
            $this->app->register(DatabaseQueryServiceProvider::class);
        }

        if (config('inspector.job', true)) {
            $this->app->register(JobServiceProvider::class);
        }

        if (config('inspector.email', true)) {
            $this->app->register(EmailServiceProvider::class);
        }

        if (config('inspector.notifications', true)) {
            $this->app->register(NotificationServiceProvider::class);
        }

        if (
            config('inspector.livewire', true) &&
            class_exists(\Livewire\Livewire::class) &&
            class_exists(\Livewire\EventBus::class)
        ) {
            $this->app->register(LivewireServiceProvider::class);
        }

        // Compatibility with Laravel < 8.4
        if (
            config('inspector.http_client', true) &&
            class_exists(\Illuminate\Http\Client\Events\RequestSending::class) &&
            class_exists(\Illuminate\Http\Client\Events\ResponseReceived::class)
        ) {
            $this->app->register(HttpClientServiceProvider::class);
        }

        if (config('inspector.views')) {
            $this->bindViewEngine();
        }
    }

    /**
     * Decorate View engine to monitor view rendering performance.
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    protected function bindViewEngine(): void
    {
        $viewEngineResolver = function (EngineResolver $engineResolver): void {
            foreach (['file', 'php', 'blade'] as $engineName) {
                $realEngine = $engineResolver->resolve($engineName);

                $engineResolver->register($engineName, fn (): \Illuminate\Contracts\View\Engine => $this->wrapViewEngine($realEngine));
            }
        };

        if ($this->app->resolved('view.engine.resolver')) {
            $viewEngineResolver($this->app->make('view.engine.resolver'));
        } else {
            $this->app->afterResolving('view.engine.resolver', $viewEngineResolver);
        }
    }

    private function wrapViewEngine(Engine $realEngine): Engine
    {
        /** @var ViewFactory $viewFactory */
        $viewFactory = $this->app->make('view');

        $viewFactory->composer('*', static function (View $view) use ($viewFactory): void {
            $viewFactory->share(ViewEngineDecorator::SHARED_KEY, $view->name());
        });

        return new ViewEngineDecorator($realEngine, $viewFactory);
    }
}
