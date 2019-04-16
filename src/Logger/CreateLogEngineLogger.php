<?php

namespace LogEngine\Laravel\Logger;


use Illuminate\Contracts\Container\Container;
use LogEngine\LogEngine;
use Monolog\Handler\PsrHandler;
use Monolog\Logger;

class CreateLogEngineLogger
{
    /**
     * @var Container
     */
    public $app;

    /**
     * CreateLogEngineLogger constructor.
     *
     * @param Container $app
     */
    public function __construct(Container $app)
    {
        $this->app = $app;
    }

    /**
     * Create a custom Monolog instance.
     *
     * @param  array $config
     * @return \Monolog\Logger
     */
    public function __invoke(array $config)
    {
        return new Logger(getenv('APP_NAME'), [
            new PsrHandler($this->app->logengine)
        ]);
    }
}