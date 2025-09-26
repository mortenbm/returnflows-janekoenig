<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'shopify' => [
        'domain' => env('SHOPIFY_DOMAIN'),
        'client' => env('SHOPIFY_CLIENT_ID'),
        'client_secret' => env('SHOPIFY_CLIENT_SECRET'),
        'token' => env('SHOPIFY_ACCESS_TOKEN'),
    ],

    'bc' => [
        'token_host' => env('BC_TOKEN_HOST'),
        'domain' => env('BC_DOMAIN'),
        'scopes' => env('BC_SCOPES'),
        'env' => env('BC_ENV'),
        'dev' => [
            'company_name' => env('BC_COMPANY_NAME'),
            'company_id' => env('BC_COMPANY_ID'),
            'tenant_id' => env('BC_TENANT_ID'),
            'client_id' => env('BC_CLIENT_ID'),
            'client_secret' => env('BC_CLIENT_SECRET'),
        ],
        'live' => [
            'company_name' => env('BC_LIVE_COMPANY_NAME'),
            'company_id' => env('BC_LIVE_COMPANY_ID'),
            'tenant_id' => env('BC_LIVE_TENANT_ID'),
            'client_id' => env('BC_LIVE_CLIENT_ID'),
            'client_secret' => env('BC_LIVE_CLIENT_SECRET'),
        ]

    ],
];
