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
        if($this->handlingApprovedRequest($request)){
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
     * Called before release the response.
     *
     * @param \Illuminate\Http\Request $request
     * @param $response
     */
    public function terminate($request, $response)
    {
        if(Inspector::isRecording()) {
            Inspector::currentTransaction()->setResult('HTTP ' . $response->status());
            Inspector::currentTransaction()->getContext()->getResponse()->setHeaders($response->headers->all());
            Inspector::currentTransaction()->getContext()->getResponse()->setStatusCode($response->status());
        }
    }

    /**
     * Determine if the incoming request should be reported.
     *
     * @param \Illuminate\Http\Request $request
     * @return bool
     */
    protected function handlingApprovedRequest(Request $request)
    {
        foreach (config('inspector.ignore_url') as $pattern) {
            if ($request->is($pattern)) {
                return false;
            }
        }

        return true;
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