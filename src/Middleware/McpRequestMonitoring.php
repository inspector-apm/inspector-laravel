<?php

namespace Inspector\Laravel\Middleware;

use Illuminate\Http\Request;

class McpRequestMonitoring extends WebRequestMonitoring
{
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
