<?php


namespace Inspector\Laravel;


use Illuminate\Http\Request;
use Symfony\Component\Console\Input\ArgvInput;

class Filters
{
    /**
     * Determine if the current request should be monitored.
     *
     * @param array $notAllowedPatterns
     * @param Request $request
     * @return bool
     */
    public static function isApprovedRequest(array $notAllowedPatterns, Request $request): bool
    {
        foreach ($notAllowedPatterns as $pattern) {
            if ($request->is($pattern)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determine if current command should be monitored.
     *
     * @param array $notAllowedCommands
     * @return bool
     */
    public static function isApprovedArtisanCommand(array $notAllowedCommands = null): bool
    {
        $input = new ArgvInput();

        return is_null($notAllowedCommands)
            ? true
            : !in_array($input->getFirstArgument(), $notAllowedCommands);
    }
}
