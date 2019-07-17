# Laravel Inspector

[![Build Status](https://travis-ci.org/inspector-apm/inspector-laravel.svg?branch=master)](https://travis-ci.org/inspector-apm/inspector-laravel)
[![Latest Stable Version](https://poser.pugx.org/inspector-apm/inspector-laravel/v/stable)](https://packagist.org/packages/inspector-apm/inspector-laravel)

- [Install](#install)
- [Enrich your timeline](#timeline)

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
    \Inspector\Laravel\Facades\Inspector::reportException(new Excetpion('Test'));
    return "Inspector works";
})
```

Open this route in you browser to test connection between your app and Inspection API.

<a name="timeline"></a>

## Enrich Your Timeline

You can add custom segments in your timeline to measure the impact that a code block has on a transaction performance.

Suppose to have an artisan command that execute some database checks and data manipulation in background. Queries are reported automatically by Inspector but for data manipulation could be interesting to have a measure of their performance.

Simply use `Inspector` facade to start new `segment`:

```php
use Inspector\Laravel\Facades\Inspector;

class TagUserAsActive extends Command
{
    protected $guzzle;
    
    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $users = Users::all();

        // Measure the impact of entire iteration
        $segmentProcess = Inspector::startSegment('process');

        foreach ($users as $user) {
            // Measure http call
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
