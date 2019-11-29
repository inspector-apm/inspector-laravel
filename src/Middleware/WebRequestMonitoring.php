<?php


namespace Inspector\Laravel\Middleware;


use Closure;
use Symfony\Component\HttpFoundation\Request as TerminableRequest;
use Symfony\Component\HttpFoundation\Response as TerminableResponse;
use Illuminate\Http\Request;
use Inspector\Laravel\Facades\Inspector;
use Illuminate\Support\Facades\Auth;
use Inspector\Laravel\Filters;
use Symfony\Component\HttpKernel\TerminableInterface;

class WebRequestMonitoring implements TerminableInterface
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
        if($this->shouldRecorded($request)){
            $this->startTransaction($request);
        }

        return $next($request);
    }

    /**
     * Determine if the middleware should record current request.
     *
     * @param $request
     * @return bool
     */
    protected function shouldRecorded($request): bool
    {
        return config('inspector.enable') && Filters::isApprovedRequest(
            config('inspector.ignore_url'),
            config('inspector.ignore_user_agents'),
            $request
        );
    }

    /**
     * Start a transaction for the incoming request.
     *
     * @param \Illuminate\Http\Request $request
     */
    protected function startTransaction($request)
    {
        $transaction = Inspector::startTransaction(
            $this->buildTransactionName($request)
        );

        if (Auth::check() && config('inspector.user')) {
            $transaction->withUser(Auth::user()->getAuthIdentifier());
        }
    }

    /**
     * Terminates a request/response cycle.
     *
     * @param TerminableRequest $request
     * @param TerminableResponse $response
     */
    public function terminate(TerminableRequest $request, TerminableResponse $response)
    {
        if(Inspector::isRecording()){
            Inspector::currentTransaction()->setResult($response->getStatusCode());
            Inspector::currentTransaction()->getContext()->getResponse()->setHeaders($response->headers->all());
            Inspector::currentTransaction()->getContext()->getResponse()->setStatusCode($response->getStatusCode());
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
