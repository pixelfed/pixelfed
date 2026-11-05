<?php

return [
    'enabled' => (bool) env('PF_C2PA_ENABLED', false),

    'driver' => env('PF_C2PA_DRIVER', 'cli'),

    'cli' => [
        'binary' => env('PF_C2PA_CLI_PATH', 'c2patool'),

        'timeout' => (int) env('PF_C2PA_CLI_TIMEOUT', 30),
    ],

    'trust_anchors' => env('PF_C2PA_TRUST_ANCHORS'),

    'ai_label' => (bool) env('PF_C2PA_AI_LABEL', true),

    'remote' => (bool) env('PF_C2PA_REMOTE', true),

    'sign' => [
        'enabled' => (bool) env('PF_C2PA_SIGN', false),

        'mode' => env('PF_C2PA_SIGN_MODE', 'credentialed'),

        'alg' => env('PF_C2PA_SIGN_ALG', 'es256'),

        'cert' => env('PF_C2PA_SIGN_CERT', storage_path('app/c2pa/signer.pem')),

        'key' => env('PF_C2PA_SIGN_KEY', storage_path('app/c2pa/signer.key')),

        'tsa_url' => env('PF_C2PA_TSA_URL'),

        'redact' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('PF_C2PA_REDACT', 'stds.exif,c2pa.metadata,cawg.metadata,c2pa.thumbnail.claim,c2pa.thumbnail.ingredient'))
        ))),
    ],
];
