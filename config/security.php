<?php

return [
    'url' => [
        'verify_dns' => env('PF_SECURITY_URL_VERIFY_DNS', false),

        'trusted_domains' => env('PF_SECURITY_URL_TRUSTED_DOMAINS', 'pixelfed.social,pixelfed.art,mastodon.social'),
    ],

    'forgot-email' => [
        'enabled' => env('PF_AUTH_ALLOW_EMAIL_FORGOT', true),

        'limits' => [
            'max' => [
                'hourly' => env('PF_AUTH_FORGOT_EMAIL_MAX_HOURLY', 50),
                'daily' => env('PF_AUTH_FORGOT_EMAIL_MAX_DAILY', 100),
                'weekly' => env('PF_AUTH_FORGOT_EMAIL_MAX_WEEKLY', 200),
                'monthly' => env('PF_AUTH_FORGOT_EMAIL_MAX_MONTHLY', 500),
            ]
        ]
    ]
];
