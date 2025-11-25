<?php

declare(strict_types=1);

namespace Inspector\Laravel;

use Illuminate\Support\Arr;

class Filters
{
    /**
     * Determine if the given request path info should be monitored.
     *
     * @param string[] $notAllowed
     * @param string $path
     * @return bool
     */
    public static function isApprovedRequest(array $notAllowed, string $path): bool
    {
        foreach ($notAllowed as $pattern) {
            if (self::matchWithWildcard($path, $pattern)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determine if the current command should be monitored.
     *
     * @param string $command
     * @param null|string[] $notAllowed
     * @return bool
     */
    public static function isApprovedArtisanCommand(string $command, ?array $notAllowed): bool
    {
        if (\is_null($notAllowed)) {
            return true;
        }

        foreach ($notAllowed as $pattern) {
            if (self::matchWithWildcard($command, $pattern)) {
                return false;
            }
        }

        return true;
    }

    public static function matchWithWildcard(string $value, string $pattern): bool
    {
        // Escape special regex characters in the pattern, except for '*'.
        $escapedPattern = \preg_quote($pattern, '/');

        // Replace '*' in the pattern with '.*' for regex matching.
        $regex = '/^' . \str_replace('\*', '.*', $escapedPattern) . '$/';

        // Perform regex match.
        return (bool)\preg_match($regex, $value);
    }

    /**
     * Determine if the given Job class should be monitored.
     *
     * @param null|string[] $notAllowed
     */
    public static function isApprovedClass(string $class, ?array $notAllowed): bool
    {
        return !\is_array($notAllowed) || !\in_array($class, $notAllowed);
    }

    /**
     * Hide the given request parameters.
     */
    public static function hideParameters(array $data, array $hidden): array
    {
        foreach ($hidden as $parameter) {
            if (Arr::get($data, $parameter)) {
                Arr::set($data, $parameter, '********');
            }
        }

        return $data;
    }
}
