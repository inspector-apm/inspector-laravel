<?php

declare(strict_types=1);

namespace Inspector\Laravel\Tests;

use Inspector\Laravel\Providers\DatabaseQueryServiceProvider;
use Inspector\Laravel\Providers\EmailServiceProvider;
use Inspector\Laravel\Providers\JobServiceProvider;
use Inspector\Laravel\Providers\NotificationServiceProvider;
use Inspector\Laravel\Providers\RedisServiceProvider;
use Inspector\Laravel\Providers\ExceptionsServiceProvider;

class DisablingProvidersTest extends BasicTestCase
{
    protected function resolveApplicationConfiguration($app)
    {
        parent::resolveApplicationConfiguration($app);

        $app['config']->set('inspector.job', false);
        $app['config']->set('inspector.query', false);
        $app['config']->set('inspector.email', false);
        $app['config']->set('inspector.notifications', false);
        $app['config']->set('inspector.unhandled_exceptions', false);
        $app['config']->set('inspector.redis', false);
    }

    public function testBindingDisabled()
    {
        // Bind Inspector service
        $this->assertInstanceOf(\Inspector\Inspector::class, $this->app['inspector']);

        // Nor register service providers
        $this->assertNull($this->app->getProvider(JobServiceProvider::class));
        $this->assertNull($this->app->getProvider(DatabaseQueryServiceProvider::class));
        $this->assertNull($this->app->getProvider(EmailServiceProvider::class));
        $this->assertNull($this->app->getProvider(NotificationServiceProvider::class));
        $this->assertNull($this->app->getProvider(ExceptionsServiceProvider::class));
        $this->assertNull($this->app->getProvider(RedisServiceProvider::class));
    }
}
