<?php


namespace Inspector\Laravel\Middleware;


use Closure;
use Illuminate\Http\Request;
use Inspector\Laravel\Facades\ApmAgent;
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
        $transaction = ApmAgent::startTransaction(
            $this->buildTransactionName($request)
        );

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
     * @param \Illuminate\Http\Request $request
     * @param $response
     */
    public function terminate($request, $response)
    {
        ApmAgent::currentTransaction()->setResult('HTTP ' . substr($response->status(), 0, 1) . 'XX');
        ApmAgent::currentTransaction()->getContext()->getResponse()->setHeaders($response->headers->all());
        ApmAgent::currentTransaction()->getContext()->getResponse()->setStatusCode($response->status());
    }

    /**
     * Generate readable name.
     *
     * @param Request $request
     * @return string
     */
    protected function buildTransactionName(Request $request)
    {
        return $request->method() . ' ' . $this->normalizeUri($request->route()->uri());
    }

    /**
     * Normalize URI string.
     *
     * @param $uri
     * @return string
     */
    protected function normalizeUri($uri)
    {
        return '/' . trim($uri, '/');
    }
}