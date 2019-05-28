<?php


namespace LogEngine\Laravel\Middleware;


use Closure;
use LogEngine\Laravel\Facades\ApmAgent;
use Illuminate\Support\Facades\Auth;

class WebRequestMonitoring
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     * @throws \Exception
     */
    public function handle($request, Closure $next)
    {
        $name = $request->method() . ' ' . $request->route()->uri();

        $transaction = ApmAgent::startTransaction($name);

        if (Auth::check()) {
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
        ApmAgent::currentTransaction()->setResult($response->status());
        ApmAgent::currentTransaction()->getContext()->getResponse()->setHeaders($response->headers->all());
        ApmAgent::currentTransaction()->getContext()->getResponse()->setStatusCode($response->status());
    }
}