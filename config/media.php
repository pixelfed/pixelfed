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
    ],

    'hls' => [
        /*
        |--------------------------------------------------------------------------
        | Enable HLS
        |--------------------------------------------------------------------------
        |
        | Enable optional HLS support, required for video p2p support. Requires FFMPEG
        | Disabled by default.
        |
        */
        'enabled' => env('MEDIA_HLS_ENABLED', false),

        'debug' => env('MEDIA_HLS_DEBUG', false),

        /*
        |--------------------------------------------------------------------------
        | Enable Video P2P support
        |--------------------------------------------------------------------------
        |
        | Enable optional video p2p support. Requires FFMPEG + HLS
        | Disabled by default.
        |
        */
        'p2p' => env('MEDIA_HLS_P2P', false),

        'p2p_debug' => env('MEDIA_HLS_P2P_DEBUG', false),

        'bitrate' => env('MEDIA_HLS_BITRATE', 1000),

        'tracker' => env('MEDIA_HLS_P2P_TRACKER', 'wss://tracker.webtorrent.dev'),

        'ice' => env('MEDIA_HLS_P2P_ICE_SERVER', 'stun:stun.l.google.com:19302'),
    ]
];
