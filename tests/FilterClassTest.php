<?php


namespace Inspector\Laravel\Tests;


use Illuminate\Http\Request;
use Inspector\Laravel\Facades\Inspector;
use Inspector\Laravel\Filters;
use Inspector\Laravel\Middleware\WebRequestMonitoring;

class FilterClassTest extends BasicTestCase
{
    public function testRequestApproved()
    {
        $this->app->router->get('test', function (Request $request) {
            $this->assertTrue(Filters::isApprovedRequest($request));
            $this->assertTrue(Inspector::isRecording());
        })->middleware(WebRequestMonitoring::class);

        $this->call('GET', 'test');
    }

    public function testRequestNotApproved()
    {
        $this->app->router->get('nova', function (Request $request) {
            $this->assertFalse(Filters::isApprovedRequest($request));
            $this->assertFalse(Inspector::isRecording());
        })->middleware(WebRequestMonitoring::class);

        $this->call('GET', 'nova');
    }
}