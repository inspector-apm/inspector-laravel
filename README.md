# Real-Time monitoring package for Laravel

[![Build Status](https://travis-ci.org/inspector-apm/inspector-laravel.svg?branch=master)](https://travis-ci.org/inspector-apm/inspector-laravel)
[![Latest Stable Version](https://poser.pugx.org/inspector-apm/inspector-laravel/v/stable)](https://packagist.org/packages/inspector-apm/inspector-laravel)

- [Version Compatibility](#versions)
- [Install](#install)
- [Midleware](#middleware)

<a name="versions"></a>

## Version Compatibility

| Laravel | Inspector package |
| ------- | ----------------- |
| 5.x     | 2.x               |
| 6.x     | 3.x               |

<a name="install"></a>

## Install

Install the latest version of our `Laravel 6.x` package by:

```sehll
composer require inspector-apm/inspector-laravel
```

If your application is  using a `5.x` version of the Laravel framework use the command below:

```shell
composer require "inspector-apm/inspector-laravel=^2.0"
```



### Configure the API Key

First put the Inspector API KEY in your environment file:

```bash
INSPECTOR_API_KEY=[api key]
```

You can obtain `INSPECTOR_API_KEY` creating a new project in your [Inspector](https://www.inspector.dev) dashboard.

<a name="middleware"></a>

### Attach the Middleware

To monitor web requests you can attach the `WebMonitoringMiddleware` in your http kernel or use in one or more route groups based on your personal needs.

```php
use Inspector\Laravel\Middleware\WebRequestMonitoring;

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

### Test everything is working

Create a test route using the code below:

```php
Route::get('test', function () {
    throw new Excetpion('Test'));
})
```

Open this route in you browser to test connection between your app and Inspection API.

## Official documentartion

**[See official documentation](https://app.inspector.dev/docs)**

## LICENSE

This package are licensed under the [MIT](LICENSE) license.
