<?php

return [

    /*
    |--------------------------------------------------------------------------
    | ActivityPub
    |--------------------------------------------------------------------------
    |
    | ActivityPub configuration
    |
    */
    'activitypub' => [
        'enabled' => env('ACTIVITY_PUB', false),
        'outbox' => env('AP_OUTBOX', true),
        'inbox' => env('AP_INBOX', true),
        'sharedInbox' => env('AP_SHAREDINBOX', true),

        'remoteFollow' => env('AP_REMOTE_FOLLOW', true),

        'delivery' => [
            'timeout' => env('ACTIVITYPUB_DELIVERY_TIMEOUT', 30),
            'concurrency' => env('ACTIVITYPUB_DELIVERY_CONCURRENCY', 10),
            'logger' => [
                'enabled' => env('AP_LOGGER_ENABLED', false),
                'driver' => 'log',
            ],
            'failure_threshold' => env('AP_DELIVERY_FAILURE_THRESHOLD', 5),
            'max_backoff' => env('AP_DELIVERY_MAX_BACKOFF', 604800),
        ],

        'ingest' => [
            'store_notes_without_followers' => env('AP_INGEST_STORE_NOTES_WITHOUT_FOLLOWERS', false),
        ],

        'authorized_fetch' => env('AUTHORIZED_FETCH', false),

        /*
         * FEP-8fcf: Followers collection synchronization across servers
         *
         * When enabled, followers-only posts are delivered with a signed
         * Collection-Synchronization header, the partial followers
         * collection is served to authenticated remote instances, and
         * incoming Collection-Synchronization headers are reconciled.
         */
        'followers_sync' => [
            'enabled' => env('AP_FOLLOWERS_SYNC', true),

            // Minimum seconds between two synchronizations of the same remote actor
            'cooldown' => env('AP_FOLLOWERS_SYNC_COOLDOWN', 900),

            // Maximum collection pages fetched from a remote server per synchronization
            'max_pages' => env('AP_FOLLOWERS_SYNC_MAX_PAGES', 10),
        ],
    ],

    'atom' => [
        'enabled' => env('ATOM_FEEDS', true),
    ],

    'avatars' => [
        'store_local' => env('REMOTE_AVATARS', true),
    ],

    'nodeinfo' => [
        'enabled' => env('NODEINFO', true),
    ],

    'webfinger' => [
        'enabled' => env('WEBFINGER', true),
    ],

    'network_timeline' => env('PF_NETWORK_TIMELINE', true),
    'network_timeline_days_falloff' => env('PF_NETWORK_TIMELINE_DAYS_FALLOFF', 90),

    'custom_emoji' => [
        'enabled' => env('CUSTOM_EMOJI', false),

        // max size in bytes, default is 2mb
        'max_size' => env('CUSTOM_EMOJI_MAX_SIZE', 2000000),
    ],

    'migration' => env('PF_ACCT_MIGRATION_ENABLED', true),

    'url_validation' => [

        /*
         * Skip resolution and domain ban checks for urls that belong to this
         * instance. Local urls are trusted by definition, and the app domain
         * often does not resolve to a globally routable address from inside
         * the app container (docker networks, split-horizon dns, CGNAT).
         * Failing those urls breaks local audience normalization and local
         * actor resolution on otherwise healthy instances.
         *
         * Scheme, userinfo, port and length checks still apply. Only the ban
         * list and address resolution are bypassed.
         */
        'skip_local_checks' => env('AP_SKIP_LOCAL_URL_CHECKS', true),

        /*
         * Accept urls whose host returned no A/AAAA records at all.
         *
         * Defaults to true. An empty answer is ambiguous (NXDOMAIN, SERVFAIL,
         * a container with no usable resolver, a libc whose res_* functions
         * PHP cannot use) and is far more often a local misconfiguration than
         * a hostile url. Failing closed there takes the whole instance off
         * the network.
         *
         * Hosts that positively resolve into non-global address space are
         * rejected regardless of this setting, and ip literals, single label
         * hosts and the loopback names are refused before resolution is ever
         * attempted.
         */
        'allow_unresolved' => env('AP_ALLOW_UNRESOLVED_HOSTS', true),

    ],
];
