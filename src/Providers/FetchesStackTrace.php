<?php


namespace Inspector\Laravel\Providers;


use Illuminate\Support\Str;

trait FetchesStackTrace
{
    /**
     * Find the first frame in the stack trace outside of Telescope/Laravel.
     *
     * @return array
     */
    protected function getCallerFromStackTrace()
    {
        $trace = collect(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS))->forget(0);

        return $trace->first(function ($frame) {
            if (! isset($frame['file'])) {
                return false;
            }

            return ! Str::contains($frame['file'], base_path('vendor'));
        });
    }
}
