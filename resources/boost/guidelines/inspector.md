## Inspector Code Execution Monitoring

This package automatically instruments a Laravel application and records performance metrics about HTTP requests, 
database queries, background Jobs, artisan Commands and, more. It also has a simple API which allows you to 
monitor any code block in your application.

The package wraps application execution cycles into an object called "Transaction" to measure their duration and metadata, 
as well as HTTP-related information (like the URL, parameters, headers, etc.). Each transaction can have multiple segments associated 
that represent subtasks within the transaction (DB queries, HTTP requests with Laravel's built-in HTTP client, Cache operations, and more).

These events are sent to the Inspector ingestion API where they will be processed and stored to provide developers 
with insights into latency issues and error culprits within the application.

## Configuration file

To customize the package behaviour, you need to publish the `inspector.php` configuration file. You can do it with the command below:

```
php artisan vendor:publish --provider="Inspector\Laravel\InspectorServiceProvider"
```

## Helper function and Facade

The package provides a helper function and a Facade to access the Inspector service class:

```php
inspector()->addSegment(function () {
    // Your code here...
}, 'segment-type');

\Inspector\Laravel\Facades\Inspector::addSegment(function () {
    // Your code here...
}, 'segment-type'),
```

## Standard Installation

You should append the Inspector middleware in the `bootstrap/app.php` file for web and api middleware groups, so in two lines of code you'll intercept all incoming http requests:

```php
use \Inspector\Laravel\Middleware\WebRequestMonitoring;

return Application::configure(basePath: dirname(__DIR__))
    ->withMiddleware(function (Middleware $middleware) {
        // Append the middleware
        $middleware->appendToGroup('web', WebRequestMonitoring::class)
            ->appendToGroup('api', WebRequestMonitoring::class);
    })
    ->create();
```

## Installation for Laravel Octane

By default, Inspector registers a shutdown function to transfer data from your application to Inspector at the end of each request lifecycle.
Since Octane runs the application in a long-running process, the data transfer must be performed at the end of the HTTP request life cycle manually.
So instead of using the normal WebRequestMonitoring middleware, you should attach to your routes the Octane specialized middleware.

```php
use \Inspector\Laravel\Middleware\InspectorOctaneMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        // routes
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Append the middleware
        $middleware->appendToGroup('web', InspectorOctaneMiddleware::class)
            ->appendToGroup('api', InspectorOctaneMiddleware::class);
    })
    ->create();
```

## Ignore Transactions

Not all transactions need to be monitored in your application. Think about a Laravel Nova admin panel that is built for internal use and isn't reachable by users. Or the Livewire internal HTTP requests that arent related to your business logic.
You have many options to keep off the noise and only monitor what metter.

### Ignore URLs
It could be necessary to turn off monitoring based on url. Think about paths like `/nova`, `/admin`, or other parts of your app that don't affect the user experience.
You can also use the wildcard character * to exclude all sub-paths.

```php
/*
 |---------------------------------------------------------------------
 | Web request url to ignore
 |---------------------------------------------------------------------
 |
 | Add at this list the url schemes that you don't want monitoring
 | in your Inspector dashboard. You can also use wildcard expression (*).
 |
 */
 
'ignore_url' => [
    'telescope*',
    'vendor/telescope*',
    'horizon*',
    'vendor/horizon*',
    // Other paths you want to ignore
],
```

### Ignore Commands

You can ignore artisan commands by adding the command signature to the ignore_commands parameter in the `config/inspector.php` configuration file.

```php
/*
 |---------------------------------------------------------------------
 | Artisan command to ignore
 |---------------------------------------------------------------------
 |
 | Add at this list all command signature that you don't want monitoring
 | in your Inspector dashboard.
 |
 */
 
'ignore_commands' => [
    'queue:work',
    'horizon',
    'horizon:work',
    'horizon:supervisor',
    // Other command signatures you want to ignore
],
```

## Ignore Jobs

You can also ignore background jobs adding classes to the `ignore_jobs` property:

```php
/*
|--------------------------------------------------------------------------
| Job classes to ignore
|--------------------------------------------------------------------------
|
| Add at this list the job classes that you don't want monitor.
|
*/

'ignore_jobs' => [
    //\App\Jobs\MyJob::class
],
```

## Exception Monitoring

> By default, every unhandled exception will be reported automatically to be sure users will be alerted to unpredictable errors in real time.

Inspector also allows you to report exceptions manually if you want to be aware of it, but you don't want to block the execution of your code:

```php
try {

    // Your code statements here...

} catch(LogicException $exception) {
    // Report an exception intentionally to fire an alert and collect diagnostics data
    inspector()->reportException($exception);
}
```

## Reporting Out Of Memory Errors

When your app runs out of memory, it needs to temporarily increase the PHP memory limit to ensure Inspector can report the current transaction. 
To do this, a "bootstrapper" class must be registered in both the `app/Http/Kernel.php` and `app/Console/Kernel.php` files:

```php
protected function bootstrappers()
{
    return array_merge(
        [\Inspector\Laravel\OutOfMemoryBootstrapper::class],
        parent::bootstrappers(),
    );
}
```

The `OutOfMemoryBootstrapper` must be the first registered bootstrapper, or it may not be called before the out-of-memory exception crashes the app.

## Monitor custom code blocks

You can also monitor any code block in your application by using the `inspector()->addSegment()` method.
You can wrap calls to internal business logic in segments to get a better understanding of the performance of your application. 
This is an example of a segment that measures the execution time of a PDF creation in a Laravel controller:

```php
class InvoiceController extends Controller
{
    public function downloadPdf(Invoice $invoice)
    {
        $pdfView = inspector()->addSegment(function () {
            return PDF::loadView('pdf.invoice', compact('invoice'))
        }, 'pdf', 'view::pdf.invoice');
        
        return $pdfView->download('invoice.pdf');
    }
}
```

If the wrapped code fires an exception, it will be reported automatically to Inspector and escalated to the controller as usual. 

As showed in the code example above, a new segment is built with two input parameters other than the callback function:

- `pdf`: This is the master category of your segment
- `view::pdf.invoice`: Human readable label or specific task's name that will be used as label during visualization. Otherwise, type is used.

