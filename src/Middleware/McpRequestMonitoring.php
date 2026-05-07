<?php

declare(strict_types=1);

namespace Inspector\Laravel\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inspector\Laravel\Facades\Inspector;
use Inspector\Laravel\Filters;

use function json_decode;

class McpRequestMonitoring extends WebRequestMonitoring
{
    /**
     * Handle an incoming request.
     *
     * Override the parent to also update an existing transaction's name
     * when the global WebRequestMonitoring middleware has already started one.
     *
     * @throws \Exception
     */
    public function handle(Request $request, Closure $next): mixed
    {
        if (
            Filters::isApprovedRequest(config('inspector.ignore_url'), $request->decodedPath())
            && $this->shouldRecorded($request)
        ) {
            if (Inspector::needTransaction()) {
                $this->startTransaction($request);
            } elseif (Inspector::hasTransaction()) {
                Inspector::transaction()->name = $this->buildTransactionName($request);
            }
        }

        return $next($request);
    }

    /**
     * Build transaction name from MCP JSON-RPC method instead of the HTTP route.
     *
     * MCP requests are JSON-RPC 2.0 envelopes. The `method` field holds the
     * operation name (e.g. `initialize`, `tools/list`, `tools/call`).
     * For `tools/call` we append the tool name from `params.name`.
     */
    protected function buildTransactionName(Request $request): string
    {
        $route = $this->normalizeUri($request->route()->uri());

        $body = json_decode($request->getContent(), true);

        $method = $body['method'] ?? null;

        if (! $method) {
            return parent::buildTransactionName($request);
        }

        if ($method === 'tools/call') {
            $toolName = $body['params']['name'] ?? null;

            if ($toolName) {
                return "{$request->method()} {$route}/{$method}/{$toolName}";
            }
        }

        return "{$request->method()} {$route}/{$method}";
    }
}
