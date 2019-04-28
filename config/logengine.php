<?php

use Psr\Log\LogLevel;

return [
    /*
    |--------------------------------------------------------------------------
    | API Key
    |--------------------------------------------------------------------------
    |
    | You can find your API key on your LOG Engine project settings page.
    |
    | This api key points the LOG Engine notifier to the project in your account
    | which should receive your application's logs & exceptions.
    |
    */
    'key' => env('LOGENGINE_API_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Hostname
    |--------------------------------------------------------------------------
    |
    | You can set the hostname of your server to something specific for you to
    | identify it by if needed.
    |
    */
    'hostname' => env('LOGENGINE_HOSTNAME'),

    /*
    |--------------------------------------------------------------------------
    | Proxy
    |--------------------------------------------------------------------------
    |
    | This is where you can set the transport option settings you'd like us to use when
    | communicating with LOG Engine.
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
    | Enable this if you'd like us to automatically record all queries executed.
    |
    */
    'query' => env('LOGENGINE_LOG_QUERY', true),

    /*
    |--------------------------------------------------------------------------
    | Bindings
    |--------------------------------------------------------------------------
    |
    | Enable this if you'd like us to include the query bindings in our query
    | breadcrumbs.
    |
    */
    'bindings' => env('LOGENGINE_QUERY_BINDINGS', false),

    /*
    |--------------------------------------------------------------------------
    | User
    |--------------------------------------------------------------------------
    |
    | Enable this if you'd like us to set the current user logged in via
    | Laravel's authentication system.
    |
    */
    'user' => env('LOGENGINE_USER', true),

    /*
    |--------------------------------------------------------------------------
    | Logger Notify Level
    |--------------------------------------------------------------------------
    |
    | This sets the level at which a logged message will trigger a notification
    | to LOG Engine.  By default this level will be 'notice'.
    |
    | Must be one of the Psr\Log\LogLevel levels from the Psr specification.
    |
    */
    'severity_level' => env('LOGENGINE_SEVERITY_LEVEL', LogLevel::DEBUG),
];