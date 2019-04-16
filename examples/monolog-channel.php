<?php

use LogEngine\Laravel\Logger\CreateLogEngineLogger;

return [
    'logengine' => [
        'driver' => 'custom',
        'via' => CreateLogEngineLogger::class,
        'level' => env('LOGENGINE_SEVERITY_LEVEL', 'debug'),
        'url' => env('LOGENGINE_URL', 'https://www.logengine.dev/api'),
        'key' => env('LOGENGINE_API_KEY'),
    ],
];