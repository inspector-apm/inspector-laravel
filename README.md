# Laravel Inspector

[![Build Status](https://travis-ci.org/inspector-apm/inspector-laravel.svg?branch=master)](https://travis-ci.org/inspector-apm/inspector-laravel)
[![Latest Stable Version](https://poser.pugx.org/inspector-apm/inspector-laravel/v/stable)](https://packagist.org/packages/inspector-apm/inspector-laravel)

- [Install](#install)
- [Report Exception](#exception)
- [Full configuration](#config)
- [Laravel >= 5.0, < 5.1](#compatibility)
- [Enrich your timeline](#timeline)

![](<https://app.inspector.dev/images/frontend/demo.gif>)

<a name="install"></a>

## Install

Install the latest version of our Laravel package by:

```sehll
composer require inspector-apm/inspector-laravel
```

### Configure the API Key

First put the Inspector API KEY in your environment file:

```bash
INSPECTOR_API_KEY=[api key]
```

You can obtain `INSPECTOR_API_KEY` creating a new project in your [Inspector](https://www.inspector.dev) dashboard.

<a name="middleware"></a>

### Attach the Middleware

To monitor web requests you can attach the `WebMonitoringMiddleware` in your http kernel or use in one or more route groups.

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

<a name="exception"></a>

## Report Exception intentionally

By default every exception fired in your laravel app will be reported automatically.

You can also report exceptions programmatically for which you will be able to access detailed information gathered by LOG Engine in real time:

```php
use Inspector\Laravel\Facades\Inspector;

try {
  	
    // Your dangerous code...
    
} catch(LogicException $exception) {
    // report an exception intentionally without blocking the application flow
    Inspector::reportException($excetion);
}
```

<a name="config"></a>

## Full configuration

If you want full control of the package behaviour publish the configuration file:

```bash
php artisan vendor:publish --provider="Inspector\InspectorServiceProvider"
```

That will add `config/inspector.php` in your Laravel configuration directory.

You can set the environment variables below:

```bash
INSPECTOR_API_KEY=[api key]
INSPECTOR_ENABLE=
INSPECTOR_LOG_QUERY=
INSPECTOR_QUERY_BINDINGS=
INSPECTOR_USER=
```

<a name="compatibility"></a>

## Laravel >= 5.0, < 5.1

Laravel's (`>= 5.0, < 5.1`) exception logger doesn't use event dispatcher (<https://github.com/laravel/framework/pull/10922>) and that's why you need to add the following line to your `App\Exceptions\Handler.php` class (otherwise Laravel's exceptions will not be sent to Inspector):

```php
public function report(Exception $e)
{
    \Inspector::reportException($e);

    return parent::report($e);
}
```

<a name="timeline"></a>

## Enrich Your Timeline

You can add custom segments in your timeline to measure the impact that a code block has on a transaction performance.

Suppose to have an artisan command that execute some database checks and data manipulation in background. Queries are reported automatically by Inspector but for data manipulation could be interesting to have a measure of their performance.

Simply use `Inspector` facade to start new `segment`:

```php
use Inspector\Laravel\Facades\Inspector;

class TagUserAsActive extends Command
{
    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $users = Users::whereHas('project')->get();
        
        // Measure the impact of entire iteration
        $segmentProcess = Inspector::startSegment('process');
        
        foreach ($users as $user) {
            // Measure http post
            $segment = Inspector::startSegment('http');
            $this->guzzle->post('/mail-marketing/add_tag', [
                'email' => $user->email,
                'tag' => 'active',
            ]);
            $segment->end();
        }
        
        $segmentProcess->end();
    }
}
```

**[See official documentation](https://app.inspector.dev/docs/2.0/platforms/laravel)**

## LICENSE

This package are licensed under the [MIT](LICENSE) license.