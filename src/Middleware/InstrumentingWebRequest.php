<?php


namespace LogEngine\Laravel\Middleware;


use Closure;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

class InstrumentingWebRequest
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $name = Route::current()->getActionMethod() . ' ' . Route::current()->uri();

        $transaction = App::make('logengine')->startTransaction($name);

        if(Auth::check()){
            $transaction->withUser(
                Auth::user()->getAuthIdentifier(),
                Auth::user()->getAuthIdentifierName()
            );
        }

        return $next($request);
    }

    /**
     * Called before release the response.
     *
     * @param $request
     * @param $response
     */
    public function terminate($request, $response)
    {
        App::make('logengine')->currentTransaction()->setResult($response->status());
        App::make('logengine')->currentTransaction()->getContext()->getResponse()->setHeaders($response->headers);
        App::make('logengine')->currentTransaction()->getContext()->getResponse()->setStatusCode($response->status());
    }
}