<?php

declare(strict_types=1);

namespace Inspector\Laravel\Providers;

use Illuminate\Support\ServiceProvider;
use Inspector\Laravel\Facades\Inspector;
use Inspector\Models\Segment;
use Laravel\Ai\Events\AgentPrompted;
use Laravel\Ai\Events\AgentStreamed;
use Laravel\Ai\Events\InvokingTool;
use Laravel\Ai\Events\PromptingAgent;
use Laravel\Ai\Events\StreamingAgent;
use Laravel\Ai\Events\ToolInvoked;

use function array_key_exists;

class AiServiceProvider extends ServiceProvider
{
    /**
     * Active agent prompt segments, keyed by invocation ID.
     *
     * @var array<string, Segment>
     */
    protected array $segments = [];

    /**
     * Active tool invocation segments, keyed by tool invocation ID.
     *
     * @var array<string, Segment>
     */
    protected array $toolSegments = [];

    /**
     * Booting of services.
     */
    public function boot(): void
    {
        $this->app['events']->listen(PromptingAgent::class, $this->handlePromptingAgent(...));
        $this->app['events']->listen(StreamingAgent::class, $this->handlePromptingAgent(...));

        $this->app['events']->listen(AgentPrompted::class, $this->handleAgentPrompted(...));
        $this->app['events']->listen(AgentStreamed::class, $this->handleAgentPrompted(...));

        $this->app['events']->listen(InvokingTool::class, $this->handleInvokingTool(...));
        $this->app['events']->listen(ToolInvoked::class, $this->handleToolInvoked(...));
    }

    /**
     * Intercepting the start of an agent prompt.
     */
    public function handlePromptingAgent(PromptingAgent $event): void
    {
        if (!Inspector::canAddSegments()) {
            return;
        }

        $agent = $event->prompt->agent;

        $this->segments[$event->invocationId] = Inspector::startSegment('ai.prompt', $this->shortClassName($agent))
            ->addContext('Agent', [
                'class' => $agent::class,
                'model' => $event->prompt->model,
            ]);
    }

    /**
     * Intercepting the end of an agent prompt.
     */
    public function handleAgentPrompted(AgentPrompted $event): void
    {
        if (!array_key_exists($event->invocationId, $this->segments)) {
            return;
        }

        $response = $event->response;
        $model = $response->meta->model ?? $event->prompt->model;

        $context = [
            'model' => $model,
            'provider' => $response->meta->provider,
            'usage' => $response->usage->toArray(),
        ];

        if (config('inspector.ai_body', true)) {
            $context['output'] = $response->text;
        }

        $this->segments[$event->invocationId]->label = $this->shortClassName($event->prompt->agent).' ('.$model.')';
        $this->segments[$event->invocationId]
            ->addContext('Response', $context)
            ->end();

        unset($this->segments[$event->invocationId]);
    }

    /**
     * Intercepting the start of a tool invocation.
     */
    public function handleInvokingTool(InvokingTool $event): void
    {
        if (!Inspector::canAddSegments()) {
            return;
        }

        $tool = $event->tool;

        $this->toolSegments[$event->toolInvocationId] = Inspector::startSegment('ai.tool', $this->shortClassName($tool))
            ->addContext('Tool', [
                'class' => $tool::class,
                'arguments' => $event->arguments,
            ]);
    }

    /**
     * Intercepting the end of a tool invocation.
     */
    public function handleToolInvoked(ToolInvoked $event): void
    {
        if (!array_key_exists($event->toolInvocationId, $this->toolSegments)) {
            return;
        }

        if (config('inspector.ai_body', true)) {
            $this->toolSegments[$event->toolInvocationId]
                ->addContext('Result', ['result' => $event->result]);
        }

        $this->toolSegments[$event->toolInvocationId]->end();
        unset($this->toolSegments[$event->toolInvocationId]);
    }

    /**
     * Get the short, unqualified name of the given object's class.
     */
    protected function shortClassName(object $object): string
    {
        $parts = explode('\\', $object::class);

        return (string) end($parts);
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        //
    }
}
