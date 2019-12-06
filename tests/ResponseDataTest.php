<?php


namespace Inspector\Laravel\Tests;


use Inspector\Laravel\Facades\Inspector;
use Inspector\Laravel\Middleware\WebRequestMonitoring;

class ResponseDataTest extends BasicTestCase
{
    public function testResult()
    {
        $this->app->router->get('approved', function () {
            $this->assertTrue(Inspector::isRecording());
        })->middleware(WebRequestMonitoring::class);

        $response = $this->call('GET', 'approved');

        $this->assertEquals(
            $response->getStatusCode(),
            Inspector::currentTransaction()->result
        );
    }
}
