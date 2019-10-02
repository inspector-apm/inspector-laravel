<?php

if (!function_exists('inspector')) {
    /**
     * @return \Inspector\Inspector
     */
    function inspector(): \Inspector\Inspector
    {
        return app('inspector');
    }
}
