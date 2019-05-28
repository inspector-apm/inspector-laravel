<?php

namespace LogEngine\Laravel\Facades;


use Illuminate\Support\Facades\Facade;

class ApmAgent extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'logengine';
    }
}