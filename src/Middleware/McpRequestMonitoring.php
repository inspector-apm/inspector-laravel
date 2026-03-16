<?php

namespace Inspector\Laravel\Middleware;

use Illuminate\Http\Request;
use Inspector\Laravel\Middleware\WebRequestMonitoring;
use Inspector\Models\Transaction;

class McpRequestMonitoring extends WebRequestMonitoring
{
    protected function buildTransactionName(Request $request): string
    {
        $body = json_decode($request->getContent(), true);

        $method = $body['method'] ?? null;

        if (! $method) {
            return parent::buildTransactionName($request);
        }

        if (in_array($method, ['tools/call', 'prompts/get'])) {
            $name = $body['params']['name'] ?? null;

            if ($name) {
                return 'MCP /'.$method.'/'.$name;
            }
        }

        return 'MCP /'.$method;
    }
}
