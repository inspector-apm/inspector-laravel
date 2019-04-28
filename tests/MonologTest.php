<?php

namespace LogEngine\Laravel\Tests;


use LogEngine\Laravel\Logger\CreateLogEngineLogger;
use Psr\Log\LoggerInterface;

class MonologTest extends BasicTestCase
{
    public function testMonologLoggerInstanceOf()
    {
        $this->assertInstanceOf(LoggerInterface::class, (new CreateLogEngineLogger($this->app))([]));
    }
}