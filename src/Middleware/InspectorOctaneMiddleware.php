<?php

namespace Inspector\Laravel\Middleware;

use Closure;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class InspectorOctaneMiddleware extends WebRequestMonitoring
{
    public function handle($request, Closure $next)
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

        /*
         * Manually flush due to the long-running process.
         */
        inspector()->flush();
    }
}
