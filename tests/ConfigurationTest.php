<?php

declare(strict_types=1);

namespace Inspector\Laravel\Tests;

class ConfigurationTest extends BasicTestCase
{
    public function testMaxItems(): void
    {
        $this->assertSame(100, (int) config('inspector.max_items'));
    }

    public function testKey(): void
    {
        $this->assertEquals('xxx', config('inspector.key'));
    }
}
