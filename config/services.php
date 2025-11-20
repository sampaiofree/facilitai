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

    'resend' => [
        'key' => env('RESEND_KEY'),
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

    'webshare' => [
        'key' => env('WEBSHARE_API_KEY'),
    ],

    'evolution' => [
        'key' => env('EVOLUTION_GLOBAL_API_KEY'),
        'url' => env('EVOLUTION_URL', 'https://evolution.3f7.org'), // Adicionando a URL também!
    ],
    'hotmart' => [
        'hottok' => env('HOTMART_HOTTOK'),
    ], 

    'openai' =>[
        'key' =>env('OPENAI_API_KEY'),
    ],
    'asaas' =>[
        'token' =>env('ASAAS_ACCESS_TOKEN'),
        'url' =>env('ASAAS_BASE_URL'),
    ],
    'google' => [
        'api_key' => env('GOOGLE_API_KEY'),
    ],

];
