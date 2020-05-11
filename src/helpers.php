<?php

if (!function_exists('inspector')) {
    /**
     * @return \Inspector\Laravel\Inspector
     */
    function inspector(): \Inspector\Laravel\Inspector
    {
        return app('inspector');
    }
}
