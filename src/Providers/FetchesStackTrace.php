<?php

declare(strict_types=1);

namespace Inspector\Laravel\Providers;

use Illuminate\Support\Str;

use function debug_backtrace;

use const DEBUG_BACKTRACE_IGNORE_ARGS;

trait FetchesStackTrace
{
    /**
     * Find the first frame in the stack trace outside of Telescope/Laravel.
     */
    protected function getCallerFromStackTrace(): array
    {
        $trace = collect(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS))->forget(0);

        return $trace->first(function (array $frame): bool {
            if (! isset($frame['file'])) {
                return false;
            }

            return ! Str::contains($frame['file'], base_path('vendor'));
        });
    }
}
