<?php


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
    public function handle($request, Closure $next)
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
        $transaction = Inspector::startTransaction(
            $this->buildTransactionName($request)
        )->markAsRequest();

        $transaction->addContext(
            'Request Body',
            Filters::hideParameters($request->all(), config('inspector.hidden_parameters'))
        );

        if (config('inspector.user')) {
            $this->collectUser($transaction);
        }
    }

    public function collectUser(Transaction $transaction)
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
    protected function buildTransactionName($request)
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
     *
     * @param $uri
     * @return string
     */
    protected function normalizeUri($uri)
    {
        return '/' . \trim($uri, '/');
    }
}
