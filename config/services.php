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
        'oficial_integration' => env('EVOLUTION_OFICIAL_INTEGRATION', 'WHATSAPP-BUSINESS'),
    ],
    'whatsapp_cloud' => [
        'base_url' => env('WHATSAPP_CLOUD_BASE_URL', 'https://graph.facebook.com'),
        'version' => env('WHATSAPP_CLOUD_VERSION', 'v23.0'),
        'phone_number_id' => env('WHATSAPP_CLOUD_PHONE_NUMBER_ID'),
        'access_token' => env('WHATSAPP_CLOUD_ACCESS_TOKEN'),
        'timeout' => (int) env('WHATSAPP_CLOUD_TIMEOUT', 15),
        'retry_times' => (int) env('WHATSAPP_CLOUD_RETRY_TIMES', 2),
        'retry_sleep_ms' => (int) env('WHATSAPP_CLOUD_RETRY_SLEEP_MS', 300),
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
        'webhook_access_token' => env('ASAAS_WEBHOOK_ACCESS_TOKEN'),
        'webhook_allowed_ips' => array_values(array_filter(array_map(
            fn ($ip) => trim((string) $ip),
            explode(',', (string) env('ASAAS_WEBHOOK_ALLOWED_IPS', ''))
        ))),
    ],
    'google' => [
        'api_key' => env('GOOGLE_API_KEY'),
    ],
    'uazapi' =>[
        'token' =>env('UAZAPI_TOKEN'),
        'url' =>env('UAZAPI_BASE_URL'),
    ],
    'dev' => [
        'whatsapp' => env('DEV_WHATSAPP'),
    ],
    'marketing' => [
        'whatsapp' => env('WHATSAPP_MARKETING'),
    ],
    'meta' => [
        'pixel_id' => env('META_PIXELID', env('META_PIXEL_ID')),
    ],

];
