<?php


namespace Inspector\Laravel\Tests;


class ConfigurationTest extends BasicTestCase
{
    public function testMaxItems()
    {
        $this->assertSame(150, (int) config('inspector.max_items'));
    }

    public function testKey()
    {
        $this->assertEquals('xxx', config('inspector.key'));
    }
}
