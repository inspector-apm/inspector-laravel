<?php

namespace Inspector\Laravel\Tests;


use Illuminate\Http\Request;
use Inspector\Laravel\Facades\ApmAgent;
use Inspector\Laravel\LogEngineServiceProvider;
use Inspector\Models\Transaction;
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