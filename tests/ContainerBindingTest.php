<?php


namespace Inspector\Laravel\Tests;


use Inspector\Laravel\Providers\CommandServiceProvider;
use Inspector\Laravel\Providers\DatabaseQueryServiceProvider;
use Inspector\Laravel\Providers\EmailServiceProvider;
use Inspector\Laravel\Providers\GateServiceProvider;
use Inspector\Laravel\Providers\JobServiceProvider;
use Inspector\Laravel\Providers\NotificationServiceProvider;
use Inspector\Laravel\Providers\RedisServiceProvider;
use Inspector\Laravel\Providers\ExceptionsServiceProvider;

class ContainerBindingTest extends BasicTestCase
{
    public function testBinding()
    {
        // Bind Inspector service
        $this->assertInstanceOf(\Inspector\Inspector::class, $this->app['inspector']);

        // Register service providers
        $this->assertInstanceOf(CommandServiceProvider::class, $this->app->getProvider(CommandServiceProvider::class));
        $this->assertInstanceOf(GateServiceProvider::class, $this->app->getProvider(GateServiceProvider::class));
        $this->assertInstanceOf(RedisServiceProvider::class, $this->app->getProvider(RedisServiceProvider::class));
        $this->assertInstanceOf(EmailServiceProvider::class, $this->app->getProvider(EmailServiceProvider::class));
        $this->assertInstanceOf(JobServiceProvider::class, $this->app->getProvider(JobServiceProvider::class));
        $this->assertInstanceOf(
            NotificationServiceProvider::class,
            $this->app->getProvider(NotificationServiceProvider::class)
        );
        $this->assertInstanceOf(
            ExceptionsServiceProvider::class,
            $this->app->getProvider(ExceptionsServiceProvider::class)
        );
        $this->assertInstanceOf(
            DatabaseQueryServiceProvider::class,
            $this->app->getProvider(DatabaseQueryServiceProvider::class)
        );
    }
}
