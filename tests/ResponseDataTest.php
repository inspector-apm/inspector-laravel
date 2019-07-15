<?php


namespace Inspector\Laravel\Tests;


use Inspector\Laravel\Facades\Inspector;
use Inspector\Laravel\Middleware\WebRequestMonitoring;

class ResponseDataTest extends BasicTestCase
{
    public function testResponseData()
    {
        $this->app->router->get('test', function () {
            $this->assertTrue(Inspector::isRecording());
        })->middleware(WebRequestMonitoring::class);

        $response = $this->call('GET', 'test');

        $this->assertEquals(
            $response->getStatusCode(),
            Inspector::currentTransaction()->getContext()->getResponse()->getStatusCode()
        );
    }
}