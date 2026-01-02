<?php

declare(strict_types=1);

namespace Inspector\Laravel\Tests;

use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Auth\User;
use Inspector\Laravel\Facades\Inspector;
use Inspector\Laravel\Providers\GateServiceProvider;
use Inspector\Models\Segment;
use Mockery;
use TypeError;

class GateServiceProviderTest extends BasicTestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testAfterGateCheckWithBooleanTrue(): void
    {
        $user = new User();
        $ability = 'view-post';
        $arguments = ['Post'];
        $result = true;

        // Mock Inspector and Segment
        $segment = Mockery::mock(Segment::class);
        $segment->shouldReceive('addContext')->andReturnSelf(); // Accept any addContext calls
        $segment->shouldReceive('end')->andReturnSelf();

        Inspector::shouldReceive('canAddSegments')->andReturn(true);
        Inspector::shouldReceive('startSegment')->andReturn($segment);

        $provider = $this->app->getProvider(GateServiceProvider::class);

        // Call beforeGateCheck to populate segments array
        $provider->beforeGateCheck($user, $ability, $arguments);

        // Now test afterGateCheck
        $returnValue = $provider->afterGateCheck($user, $ability, $result, $arguments);

        $this->assertTrue($returnValue);
    }

    public function testAfterGateCheckWithBooleanFalse(): void
    {
        $user = new User();
        $ability = 'edit-post';
        $arguments = ['Post'];
        $result = false;

        // Mock Inspector and Segment
        $segment = Mockery::mock(Segment::class);
        $segment->shouldReceive('addContext')->andReturnSelf(); // Accept any addContext calls
        $segment->shouldReceive('end')->andReturnSelf();

        Inspector::shouldReceive('canAddSegments')->andReturn(true);
        Inspector::shouldReceive('startSegment')->andReturn($segment);

        $provider = $this->app->getProvider(GateServiceProvider::class);

        // Call beforeGateCheck to populate segments array
        $provider->beforeGateCheck($user, $ability, $arguments);

        // Now test afterGateCheck
        $returnValue = $provider->afterGateCheck($user, $ability, $result, $arguments);

        $this->assertFalse($returnValue);
    }

    public function testAfterGateCheckWithNull(): void
    {
        $user = new User();
        $ability = 'publish-post';
        $arguments = ['Post'];
        $result = null;

        // Mock Inspector and Segment
        $segment = Mockery::mock(Segment::class);
        $segment->shouldReceive('addContext')->andReturnSelf(); // Accept any addContext calls
        $segment->shouldReceive('end')->andReturnSelf();

        Inspector::shouldReceive('canAddSegments')->andReturn(true);
        Inspector::shouldReceive('startSegment')->andReturn($segment);

        $provider = $this->app->getProvider(GateServiceProvider::class);

        // Call beforeGateCheck to populate segments array
        $provider->beforeGateCheck($user, $ability, $arguments);

        // Now test afterGateCheck
        $returnValue = $provider->afterGateCheck($user, $ability, $result, $arguments);

        $this->assertNull($returnValue);
    }

    public function testAfterGateCheckWhenInspectorCannotAddSegments(): void
    {
        Inspector::shouldReceive('canAddSegments')->andReturn(false);

        $user = new User();
        $ability = 'view-post';
        $arguments = ['Post'];
        $result = true;

        $provider = $this->app->getProvider(GateServiceProvider::class);
        $returnValue = $provider->afterGateCheck($user, $ability, $result, $arguments);

        $this->assertTrue($returnValue);
    }

    public function testAfterGateCheckWithResponseWhenInspectorCannotAddSegments(): void
    {
        Inspector::shouldReceive('canAddSegments')->andReturn(false);

        $user = new User();
        $ability = 'update-post';
        $arguments = ['Post'];
        $response = new Response(true, 'Allowed');

        $provider = $this->app->getProvider(GateServiceProvider::class);
        $result = $provider->afterGateCheck($user, $ability, $response, $arguments);

        // When Inspector cannot add segments, the method returns $isAllowed (boolean)
        // which is true when Response->allowed() is true
        $this->assertTrue($result);
    }
}
