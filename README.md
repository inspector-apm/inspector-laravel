# Laravel APM

[![Build Status](https://travis-ci.org/log-engine/logengine-laravel.svg?branch=master)](https://travis-ci.org/log-engine/logengine-laravel)
[![Latest Stable Version](https://poser.pugx.org/log-engine/logengine-laravel/v/stable)](https://packagist.org/packages/log-engine/logengine-laravel)

- [Install](#install)
- [Report Exception](#exception)
- [Full configuration](#config)
- [Laravel >= 5.0, < 5.1](#compatibility)

<a name="install"></a>

## Install

Install the latest version of our Laravel package by:

```
composer require log-engine/logengine-laravel
```

### Configure the API Key

First put the LOG Engine API KEY in your environment file:

```bash
LOGENGINE_API_KEY=[api key]
```

You can obtain `LOGENGINE_API_KEY` creating a new project in your [LOG Engine](https://www.logengine.dev) dashboard.

<a name="middleware"></a>

### Attach the Middleware

To monitor web requests you can attach the WebMonitoringMiddleware in your http kernel or use in one or more route groups.

```php
use LogEngine\Laravel\Middleware\WebRequestMonitoring;

/**
 * The application's route middleware groups.
 *
 * @var array
 */
protected $middlewareGroups = [
    'web' => [
        ...,
        WebRequestMonitoring::class,
    ],
    
    'api' => [
        ...,
        WebRequestMonitoring::class,
    ]
```

<a name="exception"></a>

## Report Exception intentionally

By default every exception fired in your laravel app will be reported automatically.

You can also report exceptions programmatically for which you will be able to access detailed information gathered by LOG Engine in real time:

```php
use LogEngine\Laravel\Facades\ApmAgent;

try {
  	
    // Your dangerous code...
    
} catch(LogicException $exception) {
    // Log an exception intentionally to report diagnostics data to your LOG Engine dashboard
    ApmAgent::reportException($excetion);
}
```

<a name="config"></a>

## Full configuration

If you want full control of the package behaviour publish the configuration file:

```bash
php artisan vendor:publish --provider="LogEngine\LogEngineServiceProvider"
```

That will add `config/logengine.php` in your Laravel configuration directory.

You can set the environment variables below:

```bash
LOGENGINE_API_KEY=[api key]
LOGENGINE_ENABLE=
LOGENGINE_LOG_QUERY=
LOGENGINE_QUERY_BINDINGS=
LOGENGINE_USER=
```

<a name="compatibility"></a>

## Laravel >= 5.0, < 5.1

Laravel's (`>= 5.0, < 5.1`) exception logger doesn't use event dispatcher (<https://github.com/laravel/framework/pull/10922>) and that's why you need to add the following line to your `App\Exceptions\Handler.php` class (otherwise Laravel's exceptions will not be sent to Log Engine):

```php
public function report(Exception $e)
{
    \ApmAgent::reportException($e);

    return parent::report($e);
}
```



![](<https://app.logengine.dev/images/frontend/demo.gif>)

**[See official documentation](https://www.logengine.dev/docs/1.0/platforms/laravel)**

## LICENSE

This package are licensed under the [MIT](LICENSE) license.