<?php

namespace LogEngine\Laravel\Facades;


use Illuminate\Support\Facades\Facade;

class LogEngine extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'logengine';
    }
}