# Real-Time monitoring package for Laravel

[![Build Status](https://travis-ci.org/inspector-apm/inspector-laravel.svg?branch=master)](https://travis-ci.org/inspector-apm/inspector-laravel)
[![Latest Stable Version](https://poser.pugx.org/inspector-apm/inspector-laravel/v/stable)](https://packagist.org/packages/inspector-apm/inspector-laravel)

- [Requirements](#requirements)
- [Install](#install)
- [Configure the API key](#api-key)
- [Midleware](#middleware)
- [Test everything is working](#test)
- [See official Documentation](https://docs.inspector.dev)

<a name="requirements"></a>

## Requirements

- PHP >= 7.2.0
- Laravel >= 5.5

<a name="install"></a>

## Install

Install the latest version of our package by:

```
composer require inspector-apm/inspector-laravel
```

<a name="api-key"></a>

### Configure the Ingestion Key

First put the Inspector Ingestion Key in your environment file:

```
INSPECTOR_INGESTION_KEY=[ingestion key]
```

You can obtain an `INSPECTOR_INGESTION_KEY` creating a new project in your [Inspector](https://www.inspector.dev) dashboard.

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

Run the command below:

```
php artisan inspector:test
```

Go to [https://app.inspector.dev/home](https://app.inspector.dev/home) to explore your data.

## Official documentation

**[See official documentation](https://docs.inspector.dev/platforms/laravel)**

## LICENSE

This package are licensed under the [MIT](LICENSE) license.
