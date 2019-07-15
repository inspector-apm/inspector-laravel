<?php


namespace Inspector\Laravel\Tests;


use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Inspector\Laravel\Filters;

class FilterClassTest extends BasicTestCase
{
    public function testRequestApproved()
    {
        $this->app->router->get('test', function (Request $request) {
            $this->assertTrue(Filters::isApprovedRequest($request));
        });

        $this->call('GET', 'test');
    }

    public function testRequestNotApproved()
    {
        $this->app->router->get('nova', function (Request $request) {
            $this->assertFalse(Filters::isApprovedRequest($request));
        });

        $this->call('GET', 'nova');
    }
}