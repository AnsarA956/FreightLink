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
        'stripe' => [
        'secret'   => env('STRIPE_SECRET'),
        'currency' => env('STRIPE_CURRENCY', 'pkr'),
        'usd_rate' => (float) env('STRIPE_USD_RATE', 280),
    ],
        'openrouteservice' => [
        'key'    => env('ORS_API_KEY'),
        'verify' => env('ORS_VERIFY_SSL', true),
    ],
        'sms' => [
        'driver' => env('SMS_DRIVER', 'log'),   // "log" = write to a file only, "twilio" = really send
        'verify' => env('SMS_VERIFY_SSL', true),
        'twilio' => [
            'sid'   => env('TWILIO_SID'),
            'token' => env('TWILIO_TOKEN'),
            'from'  => env('TWILIO_FROM'),
        ],
    ],
        'sms' => [
        'driver'       => env('SMS_DRIVER', 'log'),
        'country_code' => env('SMS_COUNTRY_CODE', '92'),
        'twilio' => [
            'sid'   => env('TWILIO_SID'),
            'token' => env('TWILIO_TOKEN'),
            'from'  => env('TWILIO_FROM'),
        ],
        'http' => [
            'url'      => env('SMS_HTTP_URL'),
            'user'     => env('SMS_HTTP_USER'),
            'password' => env('SMS_HTTP_PASSWORD'),
            'sender'   => env('SMS_HTTP_SENDER', 'FreightLink'),
        ],
    ],
        'slack' => [
        // ... your existing settings ...
    ],

    'sms' => [
        // ... the block above ...
    ],

];

