<?php


namespace LogEngine\Laravel\Tests;


class ContainerBindingTest extends BasicTestCase
{
    public function testBinding()
    {
        $this->assertInstanceOf(\LogEngine\ApmAgent::class, $this->app['logengine']);
    }
}