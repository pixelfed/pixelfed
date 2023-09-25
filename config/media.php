<?php

return [
    'delete_local_after_cloud' => env('MEDIA_DELETE_LOCAL_AFTER_CLOUD', true),

    'exif' => [
        'database' => env('MEDIA_EXIF_DATABASE', false),
    ],

    'storage' => [
        'remote' => [
            /*
            |--------------------------------------------------------------------------
            | Store remote media on cloud/S3
            |--------------------------------------------------------------------------
            |
            | Set this to cache remote media on cloud/S3 filesystem drivers.
            | Disabled by default.
            |
            */
            'cloud' => env('MEDIA_REMOTE_STORE_CLOUD', false),

            'resilient_mode' => env('ALT_PRI_ENABLED', false) || env('ALT_SEC_ENABLED', false),
        ],
    ]
];
