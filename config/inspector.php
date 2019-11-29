<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Enabling
    |--------------------------------------------------------------------------
    |
    | Setting "false" the package stop sending data to Inspector.
    |
    */
    'enable' => env('INSPECTOR_ENABLE', true),

    /*
    |--------------------------------------------------------------------------
    | API Key
    |--------------------------------------------------------------------------
    |
    | You can find your API key on your Inspector project settings page.
    |
    | This API key points the Inspector notifier to the project in your account
    | which should receive your application's events & exceptions.
    |
    */
    'key' => env('INSPECTOR_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Remote URL
    |--------------------------------------------------------------------------
    |
    | You can set the url of the remote endpoint to send data to.
    |
    */
    'url' => env('INSPECTOR_URL', 'https://app.inspector.dev/api'),

    /*
    |--------------------------------------------------------------------------
    | Transport method
    |--------------------------------------------------------------------------
    |
    | This is where you can set the data transport method.
    | Supported options: "sync", "async"
    |
    */
    'transport' => env('INSPECTOR_TRANSPORT', 'sync'),

    /*
    |--------------------------------------------------------------------------
    | Proxy
    |--------------------------------------------------------------------------
    |
    | This is where you can set the transport option settings you'd like us to use when
    | communicating with Inspector.
    |
    */
    'options' => [
        // 'proxy' => 'https://55.88.22.11:3128',
        // 'curlPath' => '/usr/bin/curl',
    ],

    /*
    |--------------------------------------------------------------------------
    | Query
    |--------------------------------------------------------------------------
    |
    | Enable this if you'd like us to automatically add all queries executed in the timeline.
    |
    */
    'query' => env('INSPECTOR_QUERY', true),

    /*
    |--------------------------------------------------------------------------
    | Bindings
    |--------------------------------------------------------------------------
    |
    | Enable this if you'd like us to include the query bindings.
    |
    */
    'bindings' => env('INSPECTOR_QUERY_BINDINGS', false),

    /*
    |--------------------------------------------------------------------------
    | User
    |--------------------------------------------------------------------------
    |
    | Enable this if you'd like us to set the current user logged in via
    | Laravel's authentication system.
    |
    */
    'user' => env('INSPECTOR_USER', true),

    /*
    |--------------------------------------------------------------------------
    | Email
    |--------------------------------------------------------------------------
    |
    | Enable this if you'd like us to monitor email sending.
    |
    */
    'email' => env('INSPECTOR_EMAIL', true),

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    |
    | Enable this if you'd like us to monitor notifications.
    |
    */
    'notifications' => env('INSPECTOR_NOTIFICATIONS', true),

    /*
    |--------------------------------------------------------------------------
    | Job
    |--------------------------------------------------------------------------
    |
    | Enable this if you'd like us to monitor background job processing.
    |
    */
    'job' => env('INSPECTOR_JOB', true),

    /*
    |--------------------------------------------------------------------------
    | Exceptions
    |--------------------------------------------------------------------------
    |
    | Enable this if you'd like us to report unhandled exceptions.
    |
    */
    'unhandled_exceptions' => env('INSPECTOR_UNHANDLED_EXCEPTIONS', true),

    /*
    |--------------------------------------------------------------------------
    | Artisan command to ignore
    |--------------------------------------------------------------------------
    |
    | Add at this list all command signature that you don't want monitoring
    | in your Inspector dashboard.
    |
    */
    'ignore_commands' => [
        'schedule:run',
        'vendor:publish',
        'package:discover',
        'migrate:rollback',
        'migrate:refresh',
        'migrate:fresh',
        'migrate:reset',
        'migrate:install',
        'queue:listen',
        'queue:work',
        'queue:restart',
        'horizon',
        'horizon:work',
        'horizon:supervisor',
        'horizon:terminate',
        'horizon:snapshot',
    ],

    /*
    |--------------------------------------------------------------------------
    | Web request url to ignore
    |--------------------------------------------------------------------------
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
        'nova*'
    ],

    /*
    |--------------------------------------------------------------------------
    | User agents to ignore
    |--------------------------------------------------------------------------
    |
    | Add at this list the user agents which requests you don't want monitoring
    | in your Inspector dashboard.
    |
    */
    'ignore_user_agents' => [],
];
