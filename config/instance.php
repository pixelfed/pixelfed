<?php

return [
    'force_https_urls' => env('FORCE_HTTPS_URLS', true),

    'description' => env('INSTANCE_DESCRIPTION', 'Pixelfed - Photo sharing for everyone'),

    'contact' => [
        'enabled' => env('INSTANCE_CONTACT_FORM', false),
        'max_per_day' => env('INSTANCE_CONTACT_MAX_PER_DAY', 1),
    ],

    'discover' => [
        'public' => env('INSTANCE_DISCOVER_PUBLIC', false),
        'loops' => [
            'enabled' => env('EXP_LOOPS', false),
        ],
        'tags' => [
            'is_public' => env('INSTANCE_PUBLIC_HASHTAGS', false),
        ],
        'beagle_api' => env('PF_INSTANCE_USE_BEAGLE_API', true),
    ],

    'email' => env('INSTANCE_CONTACT_EMAIL'),

    'timeline' => [
        'home' => [
            'cached' => env('PF_HOME_TIMELINE_CACHE', false),
            'cache_ttl' => env('PF_HOME_TIMELINE_CACHE_TTL', 900),
        ],

        'local' => [
            'cached' => env('INSTANCE_PUBLIC_TIMELINE_CACHED', false),
            'is_public' => env('INSTANCE_PUBLIC_LOCAL_TIMELINE', false),
        ],

        'network' => [
            'cached' => env('PF_NETWORK_TIMELINE') ? env('INSTANCE_NETWORK_TIMELINE_CACHED', false) : false,
            'cache_dropoff' => env('INSTANCE_NETWORK_TIMELINE_CACHE_DROPOFF', 100),
            'max_hours_old' => env('INSTANCE_NETWORK_TIMELINE_CACHE_MAX_HOUR_INGEST', 2160),
        ],
    ],

    'page' => [
        '404' => [
            'header' => env('PAGE_404_HEADER', 'Sorry, this page isn\'t available.'),
            'body' => env('PAGE_404_BODY', 'The link you followed may be broken, or the page may have been removed. <a href="/">Go back to Pixelfed.</a>'),
        ],
        '503' => [
            'header' => env('PAGE_503_HEADER', 'Service Unavailable'),
            'body' => env('PAGE_503_BODY', 'Our service is in maintenance mode, please try again later.'),
        ],
    ],

    'username' => [
        'banned' => env('BANNED_USERNAMES'),
        'remote' => [
            'formats' => ['@', 'from', 'custom'],
            'format' => in_array(env('USERNAME_REMOTE_FORMAT', '@'), ['@', 'from', 'custom']) ? env('USERNAME_REMOTE_FORMAT', '@') : '@',
            'custom' => env('USERNAME_REMOTE_CUSTOM_TEXT', null),
        ],
    ],

    'polls' => [
        'enabled' => false,
    ],

    'stories' => [
        'enabled' => env('STORIES_ENABLED', false),
    ],

    'restricted' => [
        'enabled' => env('RESTRICTED_INSTANCE', false),
        'level' => 1,
    ],

    'oauth' => [
        'token_expiration' => env('OAUTH_TOKEN_DAYS', 365),
        'refresh_expiration' => env('OAUTH_REFRESH_DAYS', 400),
        'pat' => [
            'enabled' => env('OAUTH_PAT_ENABLED', false),
            'id' => env('OAUTH_PAT_ID'),
        ],
    ],

    'label' => [
        'covid' => [
            'enabled' => env('ENABLE_COVID_LABEL', true),
            'url' => env('COVID_LABEL_URL', 'https://www.who.int/emergencies/diseases/novel-coronavirus-2019/advice-for-public'),
            'org' => env('COVID_LABEL_ORG', 'visit the WHO website'),
        ],
    ],

    'enable_cc' => env('ENABLE_CONFIG_CACHE', true),

    'has_legal_notice' => env('INSTANCE_LEGAL_NOTICE', false),

    'embed' => [
        'profile' => env('INSTANCE_PROFILE_EMBEDS', true),
        'post' => env('INSTANCE_POST_EMBEDS', true),
    ],

    'hide_nsfw_on_public_feeds' => env('PF_HIDE_NSFW_ON_PUBLIC_FEEDS', false),

    'avatar' => [
        'local_to_cloud' => env('PF_LOCAL_AVATAR_TO_CLOUD', false),
    ],

    'admin_invites' => [
        'enabled' => env('PF_ADMIN_INVITES_ENABLED', true),
    ],

    'user_filters' => [
        'max_user_blocks' => env('PF_MAX_USER_BLOCKS', 200),
        'max_user_mutes' => env('PF_MAX_USER_MUTES', 500),
        'max_domain_blocks' => env('PF_MAX_DOMAIN_BLOCKS', 100),
    ],

    'reports' => [
        'email' => [
            'enabled' => env('INSTANCE_REPORTS_EMAIL_ENABLED', false),
            'to' => env('INSTANCE_REPORTS_EMAIL_ADDRESSES'),
            'autospam' => env('INSTANCE_REPORTS_EMAIL_AUTOSPAM', false),
        ],
    ],

    'landing' => [
        'show_directory' => env('INSTANCE_LANDING_SHOW_DIRECTORY', true),
        'show_explore' => env('INSTANCE_LANDING_SHOW_EXPLORE', true),
    ],

    'banner' => [
        'blurhash' => env('INSTANCE_BANNER_BLURHASH', 'UzJR]l{wHZRjM}R%XRkCH?X9xaWEjZj]kAjt'),
    ],

    'parental_controls' => [
        'enabled' => env('INSTANCE_PARENTAL_CONTROLS', false),

        'limits' => [
            'respect_open_registration' => env('INSTANCE_PARENTAL_CONTROLS_RESPECT_OPENREG', true),
            'max_children' => env('INSTANCE_PARENTAL_CONTROLS_MAX_CHILDREN', 1),
            'auto_verify_email' => true,
        ],
    ],

    'software-update' => [
        'disable_failed_warning' => env('INSTANCE_SOFTWARE_UPDATE_DISABLE_FAILED_WARNING', false),
    ],

    'notifications' => [
        'gc' => [
            'enabled' => env('INSTANCE_NOTIFY_AUTO_GC', false),
            'delete_after_days' => env('INSTANCE_NOTIFY_AUTO_GC_DEL_AFTER_DAYS', 365),
        ],

        'nag' => [
            'enabled' => (bool) env('INSTANCE_NOTIFY_APP_GATEWAY', true),
            'api_key' => env('PIXELFED_PUSHGATEWAY_KEY', false),
            'endpoint' => 'push.pixelfed.net',
        ],
    ],

    'curated_registration' => [
        'enabled' => env('INSTANCE_CUR_REG', false),

        'resend_confirmation_limit' => env('INSTANCE_CUR_REG_RESEND_LIMIT', 5),

        'captcha_enabled' => env('INSTANCE_CUR_REG_CAPTCHA', env('CAPTCHA_ENABLED', false)),

        'state' => [
            'fallback_on_closed_reg' => true,
            'only_enabled_on_closed_reg' => env('INSTANCE_CUR_REG_STATE_ONLY_ON_CLOSED', true),
        ],

        'notify' => [
            'admin' => [
                'on_verify_email' => [
                    'enabled' => env('INSTANCE_CUR_REG_NOTIFY_ADMIN_ON_VERIFY', false),
                    'bundle' => env('INSTANCE_CUR_REG_NOTIFY_ADMIN_ON_VERIFY_BUNDLE', false),
                    'max_per_day' => env('INSTANCE_CUR_REG_NOTIFY_ADMIN_ON_VERIFY_MPD', 10),
                    'cc_addresses' => env('INSTANCE_CUR_REG_NOTIFY_ADMIN_ON_VERIFY_CC'),
                ],
                'on_user_response' => env('INSTANCE_CUR_REG_NOTIFY_ADMIN_ON_USER_RESPONSE', false),
            ],
        ],
    ],

    'show_peers' => env('INSTANCE_SHOW_PEERS', false),

    'allow_new_account_dms' => env('INSTANCE_ALLOW_NEW_DMS', true),

    'total_count_estimate' => env('INSTANCE_TOTAL_POSTS_COUNT_ESTIMATE', false),

    'admin' => [
        'pid' => env('PF_ADMIN_PID', null),
    ],

    'custom_filters' => [
        /*
         * The maximum number of characters from a status that will be scanned
         * for filter matching. Scanning too many characters can hurt performance,
         * so this limit ensures that only the most relevant portion of a status is processed.
         *
         * For remote statuses, you might want to increase this value if you expect
         * important content to appear later in long posts.
         */
        'max_content_scan_limit' => env('PF_CF_CONTENT_SCAN_LIMIT', 2500),

        /*
         * The maximum number of filters a single user can create.
         * Limiting the number of filters per user helps prevent abuse and
         * ensures that the filtering system remains performant.
         */
        'max_filters_per_user' => env('PF_CF_MAX_FILTERS_PER_USER', 20),

        /*
         * The maximum number of keywords that can be associated with a single filter.
         * This limit helps control the complexity of the generated regular expressions
         * and protects against potential performance issues during content scanning.
         */
        'max_keywords_per_filter' => env('PF_CF_MAX_KEYWORDS_PER_FILTER', 10),

        /*
         * The maximum length allowed for each keyword in a filter.
         * Limiting keyword length not only curtails the size of the regex patterns created,
         * but also guards against potential abuse where excessively long keywords might
         * negatively impact matching performance or lead to unintended behavior.
         */
        'max_keyword_length' => env('PF_CF_MAX_KEYWORD_LENGTH', 40),

        /*
         * The maximum allowed length for the combined regex pattern.
         * When constructing a regex that matches multiple filter keywords, each keyword
         * (after escaping and adding boundaries) contributes to the total pattern length.
         *
         * This value is set to 10000 by default. If you increase either the number of keywords
         * per filter or the maximum length allowed for each keyword, consider increasing this
         * limit accordingly so that the final regex pattern can accommodate the additional length
         * without being truncated or causing performance issues.
         */
        'max_pattern_length' => env('PF_CF_MAX_PATTERN_LENGTH', 10000),

        /*
         * The maximum number of keyword matches to report for a given status.
         * When a filter is applied to a status, the matching process may find multiple occurrences
         * of a keyword. This value limits the number of matches that are reported back,
         * which helps manage output volume and processing overhead.
         *
         * The default is set to 10, but you can adjust this value through your environment configuration.
         */
        'max_reported_matches' => env('PF_CF_MAX_REPORTED_MATCHES', 10),

        /*
         * The maximum number of filter creation operations allowed per hour for a non-admin user.
         * This rate limit prevents abuse by restricting how many filters a normal user can create
         * within one hour. Admin users are exempt from this limit.
         *
         * Default is 20 creations per hour.
         */
        'max_create_per_hour' => env('PF_CF_MAX_CREATE_PER_HOUR', 20),

        /*
         * The maximum number of filter update operations allowed per hour for a non-admin user.
         * This rate limit is designed to prevent abuse by limiting how many times a normal user
         * can update their filters within one hour. Admin users are not subject to these limits.
         *
         * Default is 40 updates per hour.
         */
        'max_updates_per_hour' => env('PF_CF_MAX_UPDATES_PER_HOUR', 40),
    ],
];
