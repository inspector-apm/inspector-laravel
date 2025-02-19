<?php

namespace Inspector\Laravel;

class Inspector extends \Inspector\Inspector
{
    /**
     * A wrap to monitor a function execution called by Laravel Container.
     *
     * @param mixed $callback
     * @param array $parameters
     * @return mixed|void
     * @throws \Throwable
     */
    public function call($callback, array $parameters = [])
    {
        if (\is_string($callback)) {
            $label = $callback;
        } elseif (\is_array($callback)) {
            $label = get_class($callback[0]).'@'.$callback[1];
        } else {
            $label = 'closure';
        }

        return $this->addSegment(function ($segment) use ($callback, $parameters) {
            $segment->addContext('Parameters', $parameters);

            return app()->call($callback, $parameters);
        }, 'method', $label, true);
    }
}
