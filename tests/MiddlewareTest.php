<?php

namespace Inspector\Laravel\Tests;


use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Inspector\Laravel\Facades\Inspector;
use Inspector\Laravel\Middleware\WebRequestMonitoring;
use Inspector\Models\Transaction;

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

        $middleware->handle($request, function ($req) {
            $this->assertInstanceOf(Transaction::class, Inspector::currentTransaction());
            $this->assertSame('GET /' . $req->route()->uri(), Inspector::currentTransaction()->name);
        });
    }
}
