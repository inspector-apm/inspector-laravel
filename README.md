# Inspector | Code Execution Monitoring Tool

[![Total Downloads](https://poser.pugx.org/inspector-apm/inspector-laravel/downloads)](//packagist.org/packages/inspector-apm/inspector-laravel)
[![Latest Stable Version](https://poser.pugx.org/inspector-apm/inspector-laravel/v/stable)](https://packagist.org/packages/inspector-apm/inspector-laravel)
[![License](https://poser.pugx.org/inspector-apm/inspector-laravel/license)](//packagist.org/packages/inspector-apm/inspector-laravel)
[![Contributor Covenant](https://img.shields.io/badge/Contributor%20Covenant-2.1-4baaaa.svg)](code_of_conduct.md)

Simple code execution monitoring for Laravel applications.

- [Requirements](#requirements)
- [Install](#install)
- [Configure the Ingestion Key](#key)
- [Middleware](#middleware)
- [Test everything is working](#test)
- [Official Documentation](https://docs.inspector.dev)
- [Contribution Guidelines](#contribution)

<a name="requirements"></a>

## Requirements

- PHP >= 7.2.0
- Laravel >= 5.5

<a name="install"></a>

## Install

Install the latest version by:

```
composer require inspector-apm/inspector-laravel
```

## For Lumen
If your application is based on Lumen you need to manually register the `InspectorServiceProvider`:

```php
$app->register(\Inspector\Laravel\InspectorServiceProvider::class);
```


<a name="key"></a>

### Configure the Ingestion Key

First put the Ingestion Key in your environment file:

```
INSPECTOR_INGESTION_KEY=[ingestion key]
```

You can obtain an `INSPECTOR_INGESTION_KEY` creating a new project in your [Inspector](https://www.inspector.dev) account.

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

<a name="contribution"></a>

## Contributing

We encourage you to contribute to Inspector! Please check out the [Contribution Guidelines](CONTRIBUTING.md) about how to proceed. Join us!

## LICENSE

This package is licensed under the [MIT](LICENSE) license.
