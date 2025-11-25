<?php

declare(strict_types=1);

if (!\function_exists('inspector')) {
    function inspector(): \Inspector\Laravel\Inspector
    {
        return app('inspector');
    }
}
