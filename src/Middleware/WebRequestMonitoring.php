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
        if (
            Filters::isApprovedRequest(config('inspector.ignore_url'), $request)
            &&
            $this->shouldRecorded($request)
            &&
            !Inspector::isRecording()
        ) {
            $this->startTransaction($request);
        }

        return $next($request);
    }

    /**
     * Determine if Inspector should monitor current request.
     *
     * @param \Illuminate\Http\Request $request
     * @return bool
     */
    protected function shouldRecorded($request): bool
    {
        return true;
    }

    /**
     * Start a transaction for the incoming request.
     *
     * @param \Illuminate\Http\Request $request
     */
    protected function startTransaction($request)
    {
        Inspector::startTransaction(
            $this->buildTransactionName($request)
        );

        if (Auth::check() && config('inspector.user')) {
            Inspector::currentTransaction()
                ->withUser(Auth::user()->getAuthIdentifier());
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
        if (Inspector::isRecording()) {
            Inspector::currentTransaction()->setResult($response->getStatusCode());

            Inspector::currentTransaction()
                ->addContext('Request Body', Filters::hideParameters(
                    $request->request->all(),
                    config('inspector.hidden_parameters')
                ))
                ->addContext('Response', [
                    'status_code' => $response->getStatusCode(),
                    'version' => $response->getProtocolVersion(),
                    'charset' => $response->getCharset(),
                    'headers' => $response->headers->all(),
                ]);
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
        $route = $request->route();

        if($route instanceof \Illuminate\Routing\Route) {
            $uri = $request->route()->uri();
        } else {
            $uri = $_SERVER['REQUEST_URI'];
        }

        return $request->method() . ' ' . $this->normalizeUri($uri);
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
