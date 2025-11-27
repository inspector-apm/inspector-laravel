<?php

declare(strict_types=1);

namespace Inspector\Laravel\Tests;

use Illuminate\Foundation\Application;
use Inspector\Laravel\Facades\Inspector;
use Inspector\Laravel\InspectorServiceProvider;
use Orchestra\Testbench\TestCase;

class BasicTestCase extends TestCase
{
    /**
     * Get package providers.
     *
     * @param  Application  $app
     */
    protected function getPackageProviders(mixed $app): array
    {
        return [InspectorServiceProvider::class];
    }

    /**
     * Get package aliases.
     *
     * @param  Application  $app
     */
    protected function getPackageAliases(mixed $app): array
    {
        return [
            'Inspector' => Inspector::class,
        ];
    }
}
