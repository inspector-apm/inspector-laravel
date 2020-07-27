<?php


namespace Inspector\Laravel\Tests;


class ConfigurationTest extends BasicTestCase
{
    public function testMaxItems()
    {
        $this->assertSame(150, (int) config('inspector.max_items'));
    }
}
