<?php

// @todo: organize for default config
return [

    'environments' => [
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

    'aliases' => [
        'production' => 'default',
        'development' => 'default',
        'local' => 'default'
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

        // @todo:
        'clear' => [
            'cc' => true,
            'bbc' => true
        ]
    ]
];