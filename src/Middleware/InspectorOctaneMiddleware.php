<?php

namespace Inspector\Laravel\Middleware;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class InspectorOctaneMiddleware extends WebRequestMonitoring
{
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
