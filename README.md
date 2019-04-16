# LOG Engine Laravel integration

Package to real-time monitor healthy of your laravel application.

- **Author:** Valerio Barbera - [support@logengine.dev](mailto:support@logengine.dev)
- **Author Website:** [www.logengine.dev](https://www.logengine.dev)


# Installation
Install the latest version with `composer require log-engine/logengine-laravel`

# Configuration

Add a new logging channel in your `config/logging.php` file, and attach it to the stack:

```php
<?php
use LogEngine\Laravel\Logger\CreateLogEngineLogger;

'channels' => [
    'stack' => [
        'driver' => 'stack',
        // Add logengine to the array:
        'channels' => ['single', 'logengine'],
    ],
    
    // ... others channels

    'logengine' => [
        'driver' => 'custom',
        'via' => CreateLogEngineLogger::class,
        'url' => env('LOGENGINE_URL', 'https://www.logengine.dev/api'),
        'key' => env('LOGENGINE_API_KEY'),
        'level' => env('LOGENGINE_SEVERITY_LEVEL', 'debug'),
    ],
],
```

If you want more control of the LOG Engine configuration publish and edit its configuration file:

`php artisan vendor:publish`

# Environment variables

Below there're all environment variables that you can use to keep under control the LOG Engine behaviour:

```
LOGENGINE_URL=
LOGENGINE_API_KEY=
LOGENGINE_SEVERITY_LEVEL=
LOGENGINE_HOSTNAME=
LOGENGINE_LOG_QUERY=
LOGENGINE_QUERY_BINDINGS=
LOGENGINE_USER=
```

By default LOGENGINE_LOG_LEVEL is considered as "debug", so the logger will report all query and job processing events.

# Log an exception

LOG Engine give you the ability to send exceptions intentionally to the platform for better investigation and reporting.

You can use specialized `logException` method inside the `LogEngine` facade:

```php
use LogEngine\Laravel\Facades\LogEngine;

try {
    // Your dangerous code here
    throw new UnauthorizedException("You don't have permission to access.");
    
} catch (UnauthorizedException $exception) {
    LogEngine::logException($exception);
}
```

## LICENSE

This package are licensed under the [MIT](LICENSE) license.
