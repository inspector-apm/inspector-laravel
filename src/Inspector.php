<?php

declare(strict_types=1);

namespace Inspector\Laravel;

use Inspector\Models\Segment;
use Throwable;

use function is_array;
use function is_string;

class Inspector extends \Inspector\Inspector
{
    /**
     * A wrap to monitor a function execution called by Laravel Container.
     *
     * @throws Throwable
     */
    public function call(string|array|callable $callback, array $parameters = []): mixed
    {
        if (is_string($callback)) {
            $label = $callback;
        } elseif (is_array($callback)) {
            $label = $callback[0]::class.'@'.$callback[1];
        } else {
            $label = 'closure';
        }

        return $this->addSegment(function (Segment $segment) use ($callback, $parameters) {
            $segment->addContext('Parameters', $parameters);

            return app()->call($callback, $parameters);
        }, 'method', $label, true);
    }
}
