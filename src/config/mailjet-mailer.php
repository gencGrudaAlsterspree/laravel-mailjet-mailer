<?php

return [

    'environment' => env('APP_ENV', 'local'),
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
    ]
];