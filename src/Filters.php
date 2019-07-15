<?php


namespace Inspector\Laravel;


use Illuminate\Http\Request;
use Symfony\Component\Console\Input\ArgvInput;

class Filters
{
    /**
     * Determine if the current request should be monitored.
     *
     * @param Request $request
     * @return bool
     */
    public static function isApprovedRequest(Request $request): bool
    {
        foreach (config('inspector.ignore_url') as $pattern) {
            if ($request->is($pattern)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determine if current command should be monitored.
     *
     * @return bool
     */
    public static function isApprovedArtisanCommand(): bool
    {
        $input = new ArgvInput();

        return ! in_array($input->getFirstArgument(), config('inspector.ignore_commands'));
    }
}