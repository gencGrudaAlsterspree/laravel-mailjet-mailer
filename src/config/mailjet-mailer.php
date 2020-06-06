<?php

return [

    'environment' => env('APP_ENV', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Account(s)
    |--------------------------------------------------------------------------
    |
    | Which account to use and which accounts available at Mailjet.
    |
    */

    'account' => env('MAILJET_MAILER_ACCOUNT', 'default'),
    'accounts' => [
        'default' => [
            'key' => env('MAILJET_APIKEY'),
            'secret' => env('MAILJET_APISECRET'),
            'templates' => [],
            'version' => 'v3.1',
            'sender' => [
                'email' => 'noreply@example.local',
                'name' => 'no-reply'
            ]
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | E-mail Interceptor
    |--------------------------------------------------------------------------
    |
    | The e-mail interceptor will intercepts all e-mail addresses and change
    | them to the corresponding MAILJET_MAILER_INTERCEPT_TO and
    | MAILJET_MAILER_INTERCEPT_TO_NAME.
    |
    | E-mails can be whitelisted or all e-mails per domain.
    |
    */

    'interceptor' => [
        'enabled' => env('MAILJET_MAILER_INTERCEPT', false),

        'to' => [
            'email' => env('MAILJET_MAILER_INTERCEPT_TO', null),
            'name' => env('MAILJET_MAILER_INTERCEPT_TO_NAME', 'Mailjet Interceptor'),
        ],

        'whitelist' => [
            'emails' => [],
            'domains' => []
        ],

        // @todo: clear cc and bcc
        'clear' => [
            'cc' => true,
            'bbc' => true
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Events
    |--------------------------------------------------------------------------
    |
    */

    'webhook' => [
        'enabled' => false,
        'middleware' => ['api'],
        'prefix' => 'api',
        'endpoint' => 'mailjet/webhook'
    ]
];