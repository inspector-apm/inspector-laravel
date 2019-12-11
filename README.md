# Real-Time monitoring package for Laravel

[![Build Status](https://travis-ci.org/inspector-apm/inspector-laravel.svg?branch=master)](https://travis-ci.org/inspector-apm/inspector-laravel)
[![Latest Stable Version](https://poser.pugx.org/inspector-apm/inspector-laravel/v/stable)](https://packagist.org/packages/inspector-apm/inspector-laravel)

- [Install](#install)
- [Configure the API key](#api-key)
- [Midleware](#middleware)
- [Test everything is working](#test)
- [See official Documentation](https://docs.inspector.dev)

<a name="install"></a>

## Install

Install the latest version of our package by:

```
composer require inspector-apm/inspector-laravel
```

<a name="api-keyt"></a>

### Configure the API Key

First put the Inspector API KEY in your environment file:

```
INSPECTOR_API_KEY=[api key]
```

You can obtain `INSPECTOR_API_KEY` creating a new project in your [Inspector](https://www.inspector.dev) dashboard.

<a name="middleware"></a>

### Attach the Middleware

To monitor web requests you can attach the `WebMonitoringMiddleware` in your http kernel or use in one or more route groups based on your personal needs.

```php
/**
 * The application's route middleware groups.
 *
 * @var array
 */
protected $middlewareGroups = [
    'web' => [
        ...,
        \Inspector\Laravel\Middleware\WebRequestMonitoring::class,
    ],

    'api' => [
        ...,
        \Inspector\Laravel\Middleware\WebRequestMonitoring::class,
    ]
```

<a name="test"></a>

### Test everything is working

Create a test route using the code below:

```php
Route::get('test', function () {
    throw new Excetpion('First exception detected'));
});
```

Open this route in you browser to test connection between your app and Inspection API.

## Official documentartion

**[See official documentation](https://docs.inspector.dev)**

## LICENSE

This package are licensed under the [MIT](LICENSE) license.
