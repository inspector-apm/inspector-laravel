<?php

declare(strict_types=1);

namespace Inspector\Laravel\Middleware;

use Closure;
use Inspector\Laravel\Facades\Inspector;
use Illuminate\Support\Facades\Auth;
use Inspector\Laravel\Filters;
use Inspector\Models\Transaction;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
    public function handle(\Illuminate\Http\Request $request, Closure $next): mixed
    {
        if (
            Inspector::needTransaction()
            &&
            Filters::isApprovedRequest(config('inspector.ignore_url'), $request->decodedPath())
            &&
            $this->shouldRecorded($request)
        ) {
            $this->startTransaction($request);
        }

        return $next($request);
    }

    /**
     * Determine if Inspector should monitor the current request.
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
     * @throws \Exception
     */
    protected function startTransaction($request): void
    {
        $transaction = Inspector::startTransaction(
            $this->buildTransactionName($request)
        )->markAsRequest();

        try {
            $transaction->http
                ->request
                ->headers = Filters::hideParameters($request->headers->all(), config('inspector.hidden_parameters'));
        } catch (\Throwable $e) {
        }

        $transaction->addContext(
            'Request Body',
            Filters::hideParameters($request->all(), config('inspector.hidden_parameters'))
        );

        if (config('inspector.user')) {
            $this->collectUser($transaction);
        }
    }

    public function collectUser(Transaction $transaction): void
    {
        if (Auth::check()) {
            $transaction->withUser(Auth::user()->getAuthIdentifier());
        }
    }

    /**
     * Terminates a request/response cycle.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Illuminate\Http\Response $response
     */
    public function terminate(Request $request, Response $response): void
    {
        if (Inspector::isRecording() && Inspector::hasTransaction()) {
            Inspector::transaction()
                ->addContext('Response', [
                    'status_code' => $response->getStatusCode(),
                    'version' => $response->getProtocolVersion(),
                    'charset' => $response->getCharset(),
                    'headers' => Filters::hideParameters($response->headers->all(), config('inspector.hidden_parameters')),
                ])
                ->addContext('Response Body', \json_decode($response->getContent(), true))
                ->setResult($response->getStatusCode());
        }
    }

    /**
     * Generate readable name.
     *
     * @param \Illuminate\Http\Request $request
     * @return string
     */
    protected function buildTransactionName($request): string
    {
        $route = $request->route();

        if ($route instanceof \Illuminate\Routing\Route) {
            $uri = $request->route()->uri();
        } else {
            $array = \explode('?', $_SERVER["REQUEST_URI"]);
            $uri = \array_shift($array);
        }

        return $request->method() . ' ' . $this->normalizeUri($uri);
    }

    /**
     * Normalize URI string.
     */
    protected function normalizeUri(string $uri): string
    {
        return '/' . \trim($uri, '/');
    }
}
