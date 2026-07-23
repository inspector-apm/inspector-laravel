<?php

declare(strict_types=1);

namespace Inspector\Laravel\Tests;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Inspector\Laravel\Facades\Inspector;
use Inspector\Laravel\Providers\AiServiceProvider;
use Inspector\Models\Segment;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Providers\TextProvider;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Events\AgentPrompted;
use Laravel\Ai\Events\InvokingTool;
use Laravel\Ai\Events\PromptingAgent;
use Laravel\Ai\Events\ToolInvoked;
use Laravel\Ai\Gateway\TextGenerationLoop;
use Laravel\Ai\Prompts\AgentPrompt;
use Laravel\Ai\Responses\AgentResponse;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Responses\Data\Usage;
use Laravel\Ai\Responses\QueuedAgentResponse;
use Laravel\Ai\Responses\StreamableAgentResponse;
use Laravel\Ai\Tools\Request;
use Mockery;
use Stringable;

class AiServiceProviderTest extends BasicTestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testPromptStartsSegmentWhenCanAddSegments(): void
    {
        $segment = Mockery::mock(Segment::class);
        /** @phpstan-ignore method.notFound */
        $segment->shouldReceive('addContext')->with('Agent', Mockery::type('array'))->once()->andReturnSelf();

        Inspector::shouldReceive('canAddSegments')->andReturn(true);
        Inspector::shouldReceive('startSegment')
            ->once()
            ->with('ai.prompt', 'StubAgent')
            ->andReturn($segment);

        $this->provider()->handlePromptingAgent($this->promptingEvent());

        $this->addToAssertionCount(1);
    }

    public function testPromptIsSkippedWhenCannotAddSegments(): void
    {
        Inspector::shouldReceive('canAddSegments')->andReturn(false);
        Inspector::shouldReceive('startSegment')->never();

        $this->provider()->handlePromptingAgent($this->promptingEvent());

        $this->addToAssertionCount(1);
    }

    public function testPromptEndReportsResponseContextAndUsage(): void
    {
        $segment = Mockery::mock(Segment::class);
        /** @phpstan-ignore method.notFound */
        $segment->shouldReceive('addContext')->with('Agent', Mockery::type('array'))->once()->andReturnSelf();

        $expectedResponseContext = fn (array $context): bool => $context['model'] === 'gpt-4o'
            && $context['provider'] === 'openai'
            && $context['usage']['prompt_tokens'] === 10
            && $context['usage']['completion_tokens'] === 20
            && $context['output'] === 'Hello back';
        /** @phpstan-ignore method.notFound */
        $segment->shouldReceive('addContext')->with('Response', Mockery::on($expectedResponseContext))->once()->andReturnSelf();
        /** @phpstan-ignore method.notFound */
        $segment->shouldReceive('end')->once()->andReturnSelf();

        Inspector::shouldReceive('canAddSegments')->andReturn(true);
        Inspector::shouldReceive('startSegment')->andReturn($segment);

        $provider = $this->provider();
        $provider->handlePromptingAgent($event = $this->promptingEvent());
        $provider->handleAgentPrompted($this->agentPromptedEvent($event->invocationId));

        $this->addToAssertionCount(1);
    }

    public function testToolStartAndEndReportSegment(): void
    {
        $segment = Mockery::mock(Segment::class);
        /** @phpstan-ignore method.notFound */
        $segment->shouldReceive('addContext')->with('Tool', Mockery::type('array'))->once()->andReturnSelf();
        /** @phpstan-ignore method.notFound */
        $segment->shouldReceive('addContext')->with('Result', ['result' => 'tool-output'])->once()->andReturnSelf();
        /** @phpstan-ignore method.notFound */
        $segment->shouldReceive('end')->once()->andReturnSelf();

        Inspector::shouldReceive('canAddSegments')->andReturn(true);
        Inspector::shouldReceive('startSegment')
            ->once()
            ->with('ai.tool', 'StubTool')
            ->andReturn($segment);

        $provider = $this->provider();
        $provider->handleInvokingTool($invoking = $this->invokingToolEvent());
        $provider->handleToolInvoked($this->toolInvokedEvent($invoking->toolInvocationId));

        $this->addToAssertionCount(1);
    }

    public function testToolIsSkippedWhenCannotAddSegments(): void
    {
        Inspector::shouldReceive('canAddSegments')->andReturn(false);
        Inspector::shouldReceive('startSegment')->never();

        $this->provider()->handleInvokingTool($this->invokingToolEvent());

        $this->addToAssertionCount(1);
    }

    public function testEndingAPromptWithoutStartingItIsIgnored(): void
    {
        // An end event for an invocation that was never started must be a safe no-op.
        $this->provider()->handleAgentPrompted($this->agentPromptedEvent('unknown-invocation'));

        $this->addToAssertionCount(1);
    }

    /**
     * Resolve the registered AI service provider from the container.
     */
    private function provider(): AiServiceProvider
    {
        /** @var AiServiceProvider $provider */
        $provider = $this->app->getProvider(AiServiceProvider::class);

        return $provider;
    }

    private function promptingEvent(): PromptingAgent
    {
        return new PromptingAgent(
            'inv-1',
            new AgentPrompt(new StubAgent(), 'Hello there', [], new StubTextProvider(), 'gpt-4o'),
        );
    }

    private function agentPromptedEvent(string $invocationId): AgentPrompted
    {
        return new AgentPrompted(
            $invocationId,
            new AgentPrompt(new StubAgent(), 'Hello there', [], new StubTextProvider(), 'gpt-4o'),
            new AgentResponse('inv-1', 'Hello back', new Usage(10, 20), new Meta('openai', 'gpt-4o')),
        );
    }

    private function invokingToolEvent(): InvokingTool
    {
        return new InvokingTool(
            'inv-1',
            'tool-1',
            new StubAgent(),
            new StubTool(),
            ['city' => 'Rome'],
        );
    }

    private function toolInvokedEvent(string $toolInvocationId): ToolInvoked
    {
        return new ToolInvoked(
            'inv-1',
            $toolInvocationId,
            new StubAgent(),
            new StubTool(),
            ['city' => 'Rome'],
            'tool-output',
        );
    }
}

/**
 * Minimal Agent implementation used to assert the short class name resolution.
 *
 * Parameter types are intentionally omitted on the implemented methods: PHP allows
 * wider (untyped) parameters in implementations, keeping this stub dependency-free.
 */
class StubAgent implements Agent
{
    public function instructions(): Stringable|string
    {
        return 'instructions';
    }

    public function prompt($prompt, $attachments = [], $provider = null, ?string $model = null, ?int $timeout = null): AgentResponse
    {
        return new AgentResponse('inv', '', new Usage(), new Meta());
    }

    public function stream($prompt, $attachments = [], $provider = null, ?string $model = null, ?int $timeout = null): StreamableAgentResponse
    {
        // @phpstan-ignore return.type
        return Mockery::mock(StreamableAgentResponse::class);
    }

    public function queue($prompt, $attachments = [], $provider = null, ?string $model = null): QueuedAgentResponse
    {
        // @phpstan-ignore return.type
        return Mockery::mock(QueuedAgentResponse::class);
    }

    public function broadcast($prompt, $channels, $attachments = [], bool $now = false, $provider = null, ?string $model = null): StreamableAgentResponse
    {
        // @phpstan-ignore return.type
        return Mockery::mock(StreamableAgentResponse::class);
    }

    public function broadcastNow($prompt, $channels, $attachments = [], $provider = null, ?string $model = null): StreamableAgentResponse
    {
        // @phpstan-ignore return.type
        return Mockery::mock(StreamableAgentResponse::class);
    }

    public function broadcastOnQueue($prompt, $channels, $attachments = [], $provider = null, ?string $model = null): QueuedAgentResponse
    {
        // @phpstan-ignore return.type
        return Mockery::mock(QueuedAgentResponse::class);
    }
}

/**
 * Minimal Tool implementation used to assert the short class name resolution.
 */
class StubTool implements Tool
{
    public function description(): Stringable|string
    {
        return 'A test tool.';
    }

    public function handle(Request $request): Stringable|string
    {
        return 'result';
    }

    /** @return array<string, mixed> */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}

/**
 * Minimal TextProvider implementation. Its methods are never invoked during the
 * monitored lifecycle, so they return harmless placeholders.
 */
class StubTextProvider implements TextProvider
{
    public function name(): string
    {
        return 'openai';
    }

    public function driver(): string
    {
        return 'openai';
    }

    /** @return array<string, mixed> */
    public function providerCredentials(): array
    {
        return [];
    }

    /** @return array<string, mixed> */
    public function additionalConfiguration(): array
    {
        return [];
    }

    public function prompt(AgentPrompt $prompt): AgentResponse
    {
        return new AgentResponse('inv', '', new Usage(), new Meta());
    }

    public function stream(AgentPrompt $prompt): StreamableAgentResponse
    {
        // @phpstan-ignore return.type
        return Mockery::mock(StreamableAgentResponse::class);
    }

    public function useTextGateway($gateway): static
    {
        return $this;
    }

    public function textGenerationLoop(): TextGenerationLoop
    {
        // @phpstan-ignore return.type
        return Mockery::mock(TextGenerationLoop::class);
    }

    public function defaultTextModel(): string
    {
        return 'gpt-4o';
    }

    public function cheapestTextModel(): string
    {
        return 'gpt-4o-mini';
    }

    public function smartestTextModel(): string
    {
        return 'gpt-4o';
    }
}
