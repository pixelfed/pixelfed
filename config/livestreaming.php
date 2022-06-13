<?php

return [
	'enabled' => env('HLS_LIVE', false),

	'server' => [
		'host' => env('HLS_LIVE_HOST', env('APP_DOMAIN', 'localhost')),
		'port' => env('HLS_LIVE_PORT', 1935),
		'path' => env('HLS_LIVE_PATH', 'live')
	],

	'broadcast' => [
		'delete_token_after_finished' => (bool) env('HLS_LIVE_BROADCAST_DELETE_TOKEN_AFTER', true),
		'max_duration' => (int) env('HLS_LIVE_BROADCAST_MAX_DURATION', 60),
		'max_active' => (int) env('HLS_LIVE_BROADCAST_MAX_ACTIVE', 10),

		'limits' => [
			'enabled' => (bool) env('HLS_LIVE_BROADCAST_LIMITS', true),
			'min_follower_count' => (int) env('HLS_LIVE_BROADCAST_LIMITS_MIN_FOLLOWERS', 100),
			'min_account_age' => (int) env('HLS_LIVE_BROADCAST_LIMITS_MIN_ACCOUNT_AGE', 14),
			'admins_only' => (bool) env('HLS_LIVE_BROADCAST_LIMITS_ADMINS_ONLY', true)
		],

		'sources' => [
			'app' => (bool) env('HLS_LIVE_BROADCAST_SOURCE_APP', false),
			'web' => (bool) env('HLS_LIVE_BROADCAST_SOURCE_WEB', false)
		]
	],

	'comments' => [
		'max_falloff' => env('HLS_LIVE_COMMENTS_MAX_FALLOFF', 50)
	],
];
