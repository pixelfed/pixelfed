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

    'oidc' => [
        /*
         *   Enable OIDC authentication
         *
         *   Enable Sign-in with OpenID Connect (OIDC) authentication providers
         */
        'enabled' => env('PF_OIDC_ENABLED', false),

        /*
         *   Client ID
         *
         *   The client ID provided by your OIDC provider
         */
        'clientId' => env('PF_OIDC_CLIENT_ID', false),

        /*
         *   Client Secret
         *
         *   The client secret provided by your OIDC provider
         */
        'clientSecret' => env('PF_OIDC_CLIENT_SECRET', false),

        /*
         *   OAuth Scopes
         *
         *   The scopes to request from the OIDC provider, typically including
         *   'openid' (required), 'profile', and 'email' for basic user information
         */
        'scopes' =>  env('PF_OIDC_SCOPES', 'openid profile email'),

        /*
         *   Authorization URL
         *
         *   The endpoint used to start the OIDC authentication flow
         */
        'authorizeURL' => env('PF_OIDC_AUTHORIZE_URL', ''),

        /*
         *   Token URL
         *
         *   The endpoint used to exchange the authorization code for an access token
         */
        'tokenURL' => env('PF_OIDC_TOKEN_URL', ''),

        /*
         *   Profile URL
         *
         *   The endpoint used to retrieve user information with a valid access token
         */
        'profileURL' => env('PF_OIDC_PROFILE_URL', ''),

        /*
         *   Logout URL
         *
         *   The endpoint used to log the user out of the OIDC provider
         */
        'logoutURL' => env('PF_OIDC_LOGOUT_URL', ''),

        /*
         *   Username Field
         *
         *   The field from the OIDC profile response to use as the username
         *   Default is 'preferred_username' but can be changed based on your provider
         */
        'field_username' => env('PF_OIDC_USERNAME_FIELD', "preferred_username"),

        /*
         *   ID Field
         *
         *   The field from the OIDC profile response to use as the unique identifier
         *   Default is 'sub' (subject) which is standard in OIDC implementations
         */
        'field_id' => env('PF_OIDC_FIELD_ID', 'sub'),
    ],
];
