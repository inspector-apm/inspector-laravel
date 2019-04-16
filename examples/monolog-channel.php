<?php

use LogEngine\Laravel\Logger\CreateLogEngineLogger;

return [
    'logengine' => [
        'driver' => 'custom',
        'via' => CreateLogEngineLogger::class,
    ],
];