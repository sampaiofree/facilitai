<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Media Storage Disk
    |--------------------------------------------------------------------------
    |
    | Disk used to persist provider media (audio/image/document/video) when
    | base64 payload exceeds the inline limit or when video is received.
    |
    */
    'disk' => env('MEDIA_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Raw Media Metadata Flag
    |--------------------------------------------------------------------------
    |
    | When enabled, provider raw metadata is included in the payload (`media.raw`).
    | Keep disabled in production to reduce Redis payload size.
    |
    */
    'raw_enabled' => env('FEATURE_MEDIA_RAW', false),

    /*
    |--------------------------------------------------------------------------
    | Evolution API Oficial (Inbound Media)
    |--------------------------------------------------------------------------
    |
    | Controls for media download performed by EvolutionApiOficialJob.
    |
    */
    'evolution_oficial' => [
        'download_timeout_seconds' => (int) env('EVOLUTION_OFICIAL_MEDIA_DOWNLOAD_TIMEOUT', 30),
        'download_retry_times' => (int) env('EVOLUTION_OFICIAL_MEDIA_DOWNLOAD_RETRY_TIMES', 1),
        'download_retry_sleep_ms' => (int) env('EVOLUTION_OFICIAL_MEDIA_DOWNLOAD_RETRY_SLEEP_MS', 500),
        'max_download_bytes' => (int) env('EVOLUTION_OFICIAL_MEDIA_MAX_DOWNLOAD_BYTES', 15000000),
        'use_conexao_proxy' => env('EVOLUTION_OFICIAL_MEDIA_USE_CONEXAO_PROXY', true),
        'validate_response_content_type' => env('EVOLUTION_OFICIAL_MEDIA_VALIDATE_RESPONSE_CONTENT_TYPE', true),
    ],
];
