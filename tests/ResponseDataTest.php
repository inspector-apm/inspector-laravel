<?php


namespace Inspector\Laravel\Tests;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inspector\Laravel\Facades\Inspector;
use Inspector\Laravel\Middleware\WebRequestMonitoring;
use Inspector\Models\Transaction;

class ResponseDataTest extends BasicTestCase
{
    protected function resolveApplicationConfiguration($app)
    {
        parent::resolveApplicationConfiguration($app);

        $app['config']->set('inspector.key', 'test');
    }

    public function testResult()
    {
        // test the middleware
        $this->app->router->get('test/{param}', function () {
            $this->assertTrue(Inspector::isRecording());
            $this->assertEquals('request', Inspector::currentTransaction()->type);
            $this->assertStringContainsString('GET /test/{param}', Inspector::currentTransaction()->name);
        })->middleware(WebRequestMonitoring::class);

        $response = $this->call('GET', 'test/param');

        // Test result
        $this->assertEquals(
            $response->getStatusCode(),
            Inspector::currentTransaction()->result
        );

        //Test response
        $this->assertArrayHasKey('Response', Inspector::currentTransaction()->context);
    }
}
