<?php

namespace LogEngine\Laravel\Tests;


use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use LogEngine\Laravel\Facades\ApmAgent;
use LogEngine\Laravel\Middleware\WebRequestMonitoring;
use LogEngine\Models\Transaction;

class MiddlewareTest extends BasicTestCase
{
    public function testTransactionCreation()
    {
        $request = new Request();
        $request->setRouteResolver(function () use ($request) {
            return (new Route('GET', 'testing/{name}', []))->bind($request);
        });

        $this->assertInstanceOf(Route::class, $request->route());
        $this->assertSame('testing/{name}', $request->route()->uri());

        $middleware = new WebRequestMonitoring();

        $middleware->handle($request, function ($req){
            $this->assertInstanceOf(Transaction::class, ApmAgent::currentTransaction());
        });
    }
}