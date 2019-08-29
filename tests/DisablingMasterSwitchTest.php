<?php


namespace Inspector\Laravel\Tests;

class DisablingMasterSwitchTest extends BasicTestCase
{
    /**
     * This method load application configuration before package service provider
     *
     * @param \Illuminate\Foundation\Application $app
     */
    protected function resolveApplicationConfiguration($app)
    {
        parent::resolveApplicationConfiguration($app);

        $app['config']->set('inspector.enable', false);
    }

    public function testPackageDisable()
    {
        $this->assertFalse($this->app->bound('inspector'));
    }
}
