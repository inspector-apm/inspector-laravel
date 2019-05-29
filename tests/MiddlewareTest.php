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
            return (new Route('GET', 'testing', []))->bind($request);
        });

        $this->assertInstanceOf(Route::class, $request->route());

        $middleware = new WebRequestMonitoring();

        $middleware->handle($request, function ($req){
            $this->assertInstanceOf(Transaction::class, ApmAgent::currentTransaction());
        });
    }
}