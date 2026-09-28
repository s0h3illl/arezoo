<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Mailpit
    |--------------------------------------------------------------------------
    |
    | The development mail service (docker/compose.yaml). It is not a third party
    | the application talks to — it never sends anything anywhere — so what belongs
    | here is only the address a test uses to ask the service what it received, and
    | the SMTP coordinates the application itself reaches it on.
    |
    | The two are the same service reached two ways, and they are kept apart
    | deliberately: SMTP is where mail is handed over, the API is where a test reads
    | it back, and a test that got them confused would prove nothing. Compose points
    | both at the container's service name; a run outside Docker uses loopback.
    |
    */

    'mailpit' => [
        'api_url' => env('MAILPIT_API_URL', 'http://127.0.0.1:8025'),
        'smtp_host' => env('MAILPIT_SMTP_HOST', '127.0.0.1'),
        'smtp_port' => (int) env('MAILPIT_SMTP_PORT', 1025),
    ],

];
