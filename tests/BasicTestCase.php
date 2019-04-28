<?php

namespace LogEngine\Laravel\Tests;


use LogEngine\Laravel\Facades\LogEngine;
use LogEngine\Laravel\LogEngineServiceProvider;
use Orchestra\Testbench\TestCase;

class BasicTestCase extends TestCase
{
    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [LogEngineServiceProvider::class];
    }

    /**
     * Get package aliases.
     *
     * @param  \Illuminate\Foundation\Application  $app
     *
     * @return array
     */
    protected function getPackageAliases($app)
    {
        return [
            'logengine' => LogEngine::class,
        ];
    }

    public function testBinding()
    {
        $this->assertInstanceOf(\LogEngine\LogEngine::class, $this->app['logengine']);
    }
}