<?php


namespace Inspector\Laravel\Tests;


use Inspector\Laravel\Facades\Inspector;
use Inspector\Laravel\Middleware\WebRequestMonitoring;
use Inspector\Models\Transaction;

class MiddlewareTest extends BasicTestCase
{
    public function testIsRecording()
    {
        $this->app->router->get('test', function () {
            return Inspector::isRecording();
        })->middleware(WebRequestMonitoring::class);

        $response = $this->get('test');

        $this->assertInstanceOf(Transaction::class, Inspector::currentTransaction());
        $this->assertTrue($response->getContent() === '1');
    }

    public function testResult()
    {
        // test the middleware
        $this->app->router->get('test', function () {})
            ->middleware(WebRequestMonitoring::class);

        $response = $this->get( 'test');

        $this->assertEquals(
            $response->getStatusCode(),
            Inspector::currentTransaction()->result
        );

        $this->assertArrayHasKey('Response', Inspector::currentTransaction()->context);
    }

    public function testContext()
    {
        // test the middleware
        $this->app->router->get('test', function () {})
            ->middleware(WebRequestMonitoring::class);

        $this->get( 'test');

        $this->assertArrayHasKey('Request Body', Inspector::currentTransaction()->context);
        $this->assertArrayHasKey('Response', Inspector::currentTransaction()->context);
    }
}
