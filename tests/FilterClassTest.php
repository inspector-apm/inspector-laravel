<?php

declare(strict_types=1);

namespace Inspector\Laravel\Tests;

use Illuminate\Http\Request;
use Inspector\Laravel\Filters;
use Inspector\Laravel\Tests\Jobs\TestJob;

class FilterClassTest extends BasicTestCase
{
    public function testRequestApproved()
    {
        $this->app->router->get('test', function (Request $request) {
            return Filters::isApprovedRequest([], $request->path());
        });

        $response = $this->get('test');

        $this->assertTrue($response->getContent() === '1');
    }

    public function testRequestNotApproved()
    {
        $this->app->router->get('test/dashboard', function (Request $request) {
            return Filters::isApprovedRequest(['test*'], $request->decodedPath());
        });

        $response = $this->get('test/dashboard');

        $this->assertEmpty($response->getContent());
    }

    public function testJobApproved()
    {
        $this->assertTrue(Filters::isApprovedClass(TestJob::class, []));
    }

    public function testJobNotApproved()
    {
        $this->assertFalse(Filters::isApprovedClass(TestJob::class, [TestJob::class]));
    }
}
