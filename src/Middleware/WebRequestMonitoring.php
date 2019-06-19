<?php


namespace Inspector\Laravel\Middleware;


use Closure;
use Illuminate\Http\Request;
use Inspector\Laravel\Facades\Inspector;
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
        $transaction = Inspector::startTransaction(
            $this->buildTransactionName($request)
        )->start(LARAVEL_START);

        if (Auth::check() && config('inspector.user')) {
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
        Inspector::currentTransaction()->setResult('HTTP ' . $response->status());
        Inspector::currentTransaction()->getContext()->getResponse()->setHeaders($response->headers->all());
        Inspector::currentTransaction()->getContext()->getResponse()->setStatusCode($response->status());
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