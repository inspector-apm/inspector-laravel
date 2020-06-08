<?php


namespace Inspector\Laravel\Tests;


use Illuminate\Http\Request;
use Inspector\Laravel\Facades\Inspector;
use Inspector\Laravel\Filters;
use Inspector\Laravel\Middleware\WebRequestMonitoring;
use Inspector\Laravel\Tests\Jobs\JobTest;

class FilterClassTest extends BasicTestCase
{
    public function testRequestApproved()
    {
        $this->app->router->get('test', function (Request $request) {
            $this->assertTrue(Filters::isApprovedRequest(config('inspector.ignore_url'), $request));
        })->middleware(WebRequestMonitoring::class);

        $this->call('GET', 'test');
    }

    public function testRequestNotApproved()
    {
        $this->app->router->get('nova', function (Request $request) {
            $this->assertFalse(Filters::isApprovedRequest(config('inspector.ignore_url'), $request));
        })->middleware(WebRequestMonitoring::class);

        $this->call('GET', 'nova');
    }

    public function testJobNotApproved()
    {
        $notAllowed = [JobTest::class];

        $this->assertFalse(Filters::isApprovedJobClass(JobTest::class, $notAllowed));

        $this->assertTrue(Filters::isApprovedJobClass(JobTest::class, config('inspector.ignore_jobs')));

        config()->set('inspector.ignore_jobs', $notAllowed);

        $this->assertFalse(Filters::isApprovedJobClass(JobTest::class, config('inspector.ignore_jobs')));
    }
}
