<?php

return [
    'mastodon' => [
        'enabled' => env('PF_LOGIN_WITH_MASTODON_ENABLED', false),
        'ignore_closed_state' => env('PF_LOGIN_WITH_MASTODON_ENABLED_SKIP_CLOSED', false),

        'contraints' => [
            /*
             *   Skip email verification
             *
             *   To improve the onboarding experience, you can opt to skip the email
             *   verification process and automatically verify their email
             */
            'skip_email_verification' => env('PF_LOGIN_WITH_MASTODON_SKIP_EMAIL', true),
        ],

        'domains' => [
            'default' => 'mastodon.social,mastodon.online,mstdn.social,mas.to',

            /*
             *   Custom mastodon domains
             *
             *   Define a comma separated list of custom domains to allow
             */
            'custom' => env('PF_LOGIN_WITH_MASTODON_DOMAINS'),

            /*
             *   Use only default domains
             *
             *   Allow Sign-in with Mastodon using only the default domains
             */
            'only_default' => env('PF_LOGIN_WITH_MASTODON_ONLY_DEFAULT', false),

            /*
             *   Use only custom domains
             *
             *   Allow Sign-in with Mastodon using only the custom domains
             *   you define, in comma separated format
             */
            'only_custom' => env('PF_LOGIN_WITH_MASTODON_ONLY_CUSTOM', false),
        ],

        'max_uses' => [
            /*
             *   Max Uses
             *
             *   Using a centralized service operated by pixelfed.org that tracks mastodon imports,
             *   you can set a limit of how many times a mastodon account can be imported across
             *   all known and reporting Pixelfed instances to prevent the same masto account from
             *   abusing this
             */
            'enabled' => env('PF_LOGIN_WITH_MASTODON_ENFORCE_MAX_USES', true),
            'limit' => env('PF_LOGIN_WITH_MASTODON_MAX_USES_LIMIT', 3)
        ]
    ],
];
