<?php

declare(strict_types=1);

namespace Inspector\Laravel\Providers;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View;
use Inspector\Laravel\Filters;
use Inspector\Models\Segment;
use Livewire\Component;
use Livewire\EventBus;

use function str_contains;

class LivewireServiceProvider extends ServiceProvider
{
    /**
     * @var array<string, Segment>
     */
    protected array $segments = [];

    /**
     * Booting of services.
     */
    public function boot(): void
    {
        $bus = $this->app->make(EventBus::class);

        try {
            //$bus->before('pre-mount', fn (string $component, array $props, string $stubbedId) => $this->handlePreMount($component, $props, $stubbedId));
            $bus->before('mount', fn (Component $component) => $this->handleMount($component));

            $bus->before('hydrate', fn (Component $component) => $this->handleHydrate($component));

            $bus->before('call', fn (Component $component, string $method, array $params) => $this->handleCalling($component, $method, $params));
            $bus->after('call', fn (Component $component) => $this->handleCalled($component));

            $bus->before('render', fn (Component $component, View $view) => $this->handleRendering($component, $view));
            $bus->after('render', fn (Component $component, View $view) => $this->handleRendered($component, $view));

            $bus->after('destroy', fn (Component $component) => $this->handleDestroy($component));
            //$bus->before('exception', fn (Component $component) => Log::debug('Livewire exception', ['component' => get_class($component)]));
        } catch (Exception) {
        }
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        //
    }

    protected function handleMount(Component $component): void
    {
        if (!inspector()->canAddSegments() || !$this->shouldBeMonitored($component::class)) {
            return;
        }

        $this->segments[$component->id()] = inspector()->startSegment('livewire', $component::class);
    }

    protected function handleHydrate(Component $component): void
    {
        if (!inspector()->canAddSegments() || !$this->shouldBeMonitored($component::class)) {
            return;
        }

        if (str_contains(inspector()->transaction()->name, (string) config('inspector.livewire.path'))) {
            inspector()->transaction()->setType('livewire')->name = $component::class;
        }
    }

    protected function handleRendering(Component $component, View $view): void
    {
        if (!isset($this->segments[$component->id()])) {
            return;
        }

        $this->segments[
            $component->id().".render.{$view->name()}"
        ] = inspector()->startSegment('livewire', "render::{$view->name()}")
            ->addContext('View', ['name' => $view->name(), 'data' => $view->getData()]);
    }

    protected function handleRendered(Component $component, View $view): void
    {
        $segmentKey = $component->id().".render.{$view->name()}";

        if (!isset($this->segments[$segmentKey])) {
            return;
        }

        $this->segments[$segmentKey]->end();
    }

    protected function handleCalling(Component $component, string $method, array $params): void
    {
        if (!inspector()->canAddSegments() || $this->shouldBeMonitored($component::class)) {
            return;
        }

        $this->segments[$component->id().".call"] = inspector()->startSegment('livewire', $method.'()')->addContext('Parameters', $params);
    }

    protected function handleCalled(Component $component): void
    {
        if (!isset($this->segments[$component->id().".call"])) {
            return;
        }

        $this->segments[$component->id().".call"]->end();
        unset($this->segments[$component->id().".call"]);
    }

    protected function handleDestroy(Component $component): void
    {
        if (!isset($this->segments[$component->id()])) {
            return;
        }

        $this->segments[$component->id()]->end();
        unset($this->segments[$component->id()]);
    }

    /**
     * Determine if the current command should be monitored.
     */
    protected function shouldBeMonitored(string $component): bool
    {
        return Filters::isApprovedClass($component, config('inspector.livewire.ignore_components'));
    }
}
