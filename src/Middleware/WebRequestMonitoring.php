<?php


namespace Inspector\Laravel\Middleware;


use Closure;
use Illuminate\Http\Request;
use Inspector\Laravel\Facades\Inspector;
use Illuminate\Support\Facades\Auth;
use Inspector\Laravel\Filters;

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
        if(Filters::isApprovedRequest($request)){
            $this->recordRequest($request);
        }

        return $next($request);
    }

    /**
     * Start a transaction for the incoming request.
     *
     * @param \Illuminate\Http\Request $request
     */
    protected function recordRequest($request)
    {
        $transaction = Inspector::startTransaction(
            $this->buildTransactionName($request)
        );

        if (Auth::check() && config('inspector.user')) {
            $transaction->withUser(
                Auth::user()->getAuthIdentifier(),
                Auth::user()->getAuthIdentifierName()
            );
        }
    }

    /**
     * Generate readable name.
     *
     * @param \Illuminate\Http\Request $request
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