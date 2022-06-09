<?php

return [

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
		'local' => [
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

	'enable_cc' => env('ENABLE_CONFIG_CACHE', false),
];
