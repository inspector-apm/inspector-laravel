<?php


namespace Inspector\Laravel\Tests;


use Inspector\Laravel\Facades\Inspector;
use Inspector\Laravel\Middleware\WebRequestMonitoring;
use Inspector\Models\Transaction;

class MiddlewareTest extends BasicTestCase
{
    public function testIsRecording()
    {
        $this->assertTrue(Inspector::isRecording());
        $this->assertTrue(Inspector::needTransaction());

        $this->app->router->get('test', function () {
            // do nothing
        })->middleware(WebRequestMonitoring::class);

        $this->get('test');

        $this->assertFalse(Inspector::needTransaction());
        $this->assertInstanceOf(Transaction::class, Inspector::transaction());
    }

    public function testResult()
    {
        // test the middleware
        $this->app->router->get('test', function () {
            // do nothing
        })->middleware(WebRequestMonitoring::class);

        $response = $this->get('test');

        $this->assertEquals($response->getStatusCode(), Inspector::transaction()->result);

        $this->assertArrayHasKey('Response', Inspector::transaction()->getContext());
    }

    public function testContext()
    {
        // test the middleware
        $this->app->router->get('test', function () {
            // do nothing
        })->middleware(WebRequestMonitoring::class);

        $this->get('test');

        $this->assertArrayHasKey('Request Body', Inspector::transaction()->getContext());
        $this->assertArrayHasKey('Response', Inspector::transaction()->getContext());
    }
}
