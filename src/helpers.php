<?php

if (!function_exists('inspector')) {
    function inspector(): \Inspector\Laravel\Inspector
    {
        return app('inspector');
    }
}
