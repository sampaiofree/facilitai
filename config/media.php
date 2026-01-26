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
];
