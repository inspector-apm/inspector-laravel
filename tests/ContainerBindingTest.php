<?php


namespace Inspector\Laravel\Tests;


use Inspector\Laravel\Providers\DatabaseQueryServiceProvider;
use Inspector\Laravel\Providers\EmailServiceProvider;
use Inspector\Laravel\Providers\HttpResponseCollectorProvider;
use Inspector\Laravel\Providers\JobServiceProvider;
use Inspector\Laravel\Providers\UnhandledExceptionServiceProvider;

class ContainerBindingTest extends BasicTestCase
{
    public function testBinding()
    {
        // Bind Inspector service
        $this->assertInstanceOf(\Inspector\Inspector::class, $this->app['inspector']);

        // Register service providers
        //$this->assertInstanceOf(HttpResponseCollectorProvider::class, $this->app->getProvider(HttpResponseCollectorProvider::class));
        $this->assertInstanceOf(EmailServiceProvider::class, $this->app->getProvider(EmailServiceProvider::class));
        $this->assertInstanceOf(JobServiceProvider::class, $this->app->getProvider(JobServiceProvider::class));
        $this->assertInstanceOf(UnhandledExceptionServiceProvider::class, $this->app->getProvider(UnhandledExceptionServiceProvider::class));
        $this->assertInstanceOf(DatabaseQueryServiceProvider::class, $this->app->getProvider(DatabaseQueryServiceProvider::class));
    }
}