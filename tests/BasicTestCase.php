<?php

namespace Inspector\Laravel\Tests;


use Inspector\Laravel\Facades\Inspector;
use Inspector\Laravel\LogEngineServiceProvider;
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
            'inspector' => Inspector::class,
        ];
    }
}