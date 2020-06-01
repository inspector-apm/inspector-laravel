<?php


namespace Inspector\Laravel;


use Illuminate\Http\Request;
use Symfony\Component\Console\Input\ArgvInput;

class Filters
{
    /**
     * Determine if the given request should be monitored.
     *
     * @param string[] $notAllowed
     * @param Request $request
     * @return bool
     */
    public static function isApprovedRequest(array $notAllowed, Request $request): bool
    {
        foreach ($notAllowed as $pattern) {
            if ($request->is($pattern)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determine if current command should be monitored.
     *
     * @param string[] $notAllowed
     * @return bool
     */
    public static function isApprovedArtisanCommand(array $notAllowed = null): bool
    {
        $input = new ArgvInput();

        return is_null($notAllowed)
            ? true
            : !in_array($input->getFirstArgument(), $notAllowed);
    }

    /**
     * Determine if the given Job class should be monitored.
     *
     * @param string[] $notAllowed
     * @param string $class
     * @return bool
     */
    public static function isApprovedJobClass(array $notAllowed, string $class)
    {
        return !in_array($class, $notAllowed);
    }
}
