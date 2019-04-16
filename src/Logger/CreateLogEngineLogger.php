<?php

namespace LogEngine\Laravel\Logger;


use LogEngine\LogEngine;
use Monolog\Handler\PsrHandler;
use Monolog\Logger;

class CreateLogEngineLogger
{
    /**
     * Create a custom Monolog instance.
     *
     * @param  array $config
     * @return \Monolog\Logger
     * @throws \LogEngine\Exceptions\LogEngineException
     */
    public function __invoke(array $config)
    {
        return new Logger(getenv('APP_NAME'), [
            new PsrHandler(
                new LogEngine($config['url'], $config['key']),
                $config['level']
            )
        ]);
    }
}