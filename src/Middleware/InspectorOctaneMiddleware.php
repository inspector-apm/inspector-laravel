<?php

namespace Inspector\Laravel\Middleware;

use Closure;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class InspectorOctaneMiddleware extends WebRequestMonitoring
{
    public function handle(\Illuminate\Http\Request $request, Closure $next): mixed
    {
        // https://github.com/inspector-apm/inspector-laravel/issues/45
        $_SERVER['REQUEST_METHOD'] = $request->getMethod();
        $_SERVER['REMOTE_ADDR'] = $request->getClientIp();
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/' . $request->getProtocolVersion();
        $_SERVER['SERVER_PORT'] = $request->getPort();
        $_SERVER['SCRIPT_NAME'] = $request->server('SCRIPT_NAME');
        $_SERVER['HTTP_HOST'] = $request->getHost();
        $_SERVER['QUERY_STRING'] = $request->getQueryString();
        $_SERVER['REQUEST_URI'] = $request->getRequestUri();

        return parent::handle($request, $next);
    }

    /**
     * Terminates a request/response cycle.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Illuminate\Http\Response $response
     * @throws \Exception
     */
    public function terminate(Request $request, Response $response): void
    {
        parent::terminate($request, $response);

        // Using Octane headers and cookies are not available with native apache functions.
        // We need to retrieve them using the Laravel Request class.
        try {
            inspector()->transaction()->http->request->headers = \array_merge(
                inspector()->transaction()->http->request->headers??[],
                $request->header()
            );
        } catch (\Throwable $exception) {
            // nothing to do
        }

        /*
         * Manually flush because of the long-running process.
         */
        inspector()->flush();
    }
}
