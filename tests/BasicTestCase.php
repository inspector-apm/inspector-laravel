<?php

namespace LogEngine\Laravel\Tests;


use Illuminate\Http\Request;
use LogEngine\Laravel\Facades\ApmAgent;
use LogEngine\Laravel\LogEngineServiceProvider;
use LogEngine\Laravel\Middleware\WebRequestMonitoring;
use LogEngine\Models\Transaction;
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
            'logengine' => ApmAgent::class,
        ];
    }
}