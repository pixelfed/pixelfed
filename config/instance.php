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
			'is_public' => env('INSTANCE_PUBLIC_HASHTAGS', false)
		],
	],

	'email' => env('INSTANCE_CONTACT_EMAIL'),

	'timeline' => [
		'home' => [
			'cached' => env('PF_HOME_TIMELINE_CACHE', false),
			'cache_ttl' => env('PF_HOME_TIMELINE_CACHE_TTL', 900)
		],

		'local' => [
			'cached' => env('INSTANCE_PUBLIC_TIMELINE_CACHED', false),
			'is_public' => env('INSTANCE_PUBLIC_LOCAL_TIMELINE', false)
		],

		'network' => [
			'cached' => env('PF_NETWORK_TIMELINE') ? env('INSTANCE_NETWORK_TIMELINE_CACHED', false) : false,
			'cache_dropoff' => env('INSTANCE_NETWORK_TIMELINE_CACHE_DROPOFF', 100),
			'max_hours_old' => env('INSTANCE_NETWORK_TIMELINE_CACHE_MAX_HOUR_INGEST', 6)
		]
	],

	'page' => [
		'404' => [
			'header' => env('PAGE_404_HEADER', 'Sorry, this page isn\'t available.'),
			'body' => env('PAGE_404_BODY', 'The link you followed may be broken, or the page may have been removed. <a href="/">Go back to Pixelfed.</a>')
		],
		'503' => [
			'header' => env('PAGE_503_HEADER', 'Service Unavailable'),
			'body' => env('PAGE_503_BODY', 'Our service is in maintenance mode, please try again later.')
		]
	],

	'username' => [
		'banned' => env('BANNED_USERNAMES'),
		'remote' => [
			'formats' => ['@', 'from', 'custom'],
			'format' => in_array(env('USERNAME_REMOTE_FORMAT', '@'), ['@','from','custom']) ? env('USERNAME_REMOTE_FORMAT', '@') : '@',
			'custom' => env('USERNAME_REMOTE_CUSTOM_TEXT', null)
		]
	],

	'polls' => [
		'enabled' => false
	],

	'stories' => [
		'enabled' => env('STORIES_ENABLED', false),
	],

	'restricted' => [
		'enabled' => env('RESTRICTED_INSTANCE', false),
		'level' => 1
	],

	'oauth' => [
		'token_expiration' => env('OAUTH_TOKEN_DAYS', 365),
		'refresh_expiration' => env('OAUTH_REFRESH_DAYS', 400),
		'pat' => [
			'enabled' => env('OAUTH_PAT_ENABLED', false),
			'id' 	  => env('OAUTH_PAT_ID'),
		]
	],

	'label' => [
		'covid' => [
			'enabled' => env('ENABLE_COVID_LABEL', true),
			'url' => env('COVID_LABEL_URL', 'https://www.who.int/emergencies/diseases/novel-coronavirus-2019/advice-for-public'),
			'org' => env('COVID_LABEL_ORG', 'visit the WHO website')
		]
	],

	'enable_cc' => env('ENABLE_CONFIG_CACHE', true),

	'has_legal_notice' => env('INSTANCE_LEGAL_NOTICE', false),

	'embed' => [
		'profile' => env('INSTANCE_PROFILE_EMBEDS', true),
		'post' => env('INSTANCE_POST_EMBEDS', true),
	],

	'hide_nsfw_on_public_feeds' => env('PF_HIDE_NSFW_ON_PUBLIC_FEEDS', false),

	'avatar' => [
		'local_to_cloud' => env('PF_LOCAL_AVATAR_TO_CLOUD', false)
	],

	'admin_invites' => [
		'enabled' => env('PF_ADMIN_INVITES_ENABLED', true)
	],

	'user_filters' => [
		'max_user_blocks' => env('PF_MAX_USER_BLOCKS', 50),
		'max_user_mutes' => env('PF_MAX_USER_MUTES', 50),
		'max_domain_blocks' => env('PF_MAX_DOMAIN_BLOCKS', 50),
	],

	'reports' => [
		'email' => [
			'enabled' => env('INSTANCE_REPORTS_EMAIL_ENABLED', false),
			'to' => env('INSTANCE_REPORTS_EMAIL_ADDRESSES'),
			'autospam' => env('INSTANCE_REPORTS_EMAIL_AUTOSPAM', false)
		]
	],

	'landing' => [
		'show_directory' => env('INSTANCE_LANDING_SHOW_DIRECTORY', true),
		'show_explore' => env('INSTANCE_LANDING_SHOW_EXPLORE', true),
	],

	'banner' => [
		'blurhash' => env('INSTANCE_BANNER_BLURHASH', 'UzJR]l{wHZRjM}R%XRkCH?X9xaWEjZj]kAjt')
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
        'disable_failed_warning' => env('INSTANCE_SOFTWARE_UPDATE_DISABLE_FAILED_WARNING', false)
    ],
];
