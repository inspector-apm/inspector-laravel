<?php


namespace Inspector\Laravel\Tests;


class ContainerBindingTest extends BasicTestCase
{
    public function testBinding()
    {
        $this->assertInstanceOf(\Inspector\ApmAgent::class, $this->app['inspector']);
    }
}