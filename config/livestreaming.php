<?php

return [
	'enabled' => env('HLS_LIVE', false),

	'server' => [
		'host' => env('HLS_LIVE_HOST', env('APP_DOMAIN', 'localhost')),
		'port' => env('HLS_LIVE_PORT', 1935),
		'path' => env('HLS_LIVE_PATH', 'live')
	],

	'broadcast' => [
		'max_duration' => env('HLS_LIVE_BROADCAST_MAX_DURATION', 60),
		'max_active' => env('HLS_LIVE_BROADCAST_MAX_ACTIVE', 10),

		'limits' => [
			'enabled' => env('HLS_LIVE_BROADCAST_LIMITS', true),
			'min_follower_count' => env('HLS_LIVE_BROADCAST_LIMITS_MIN_FOLLOWERS', 100),
			'min_account_age' => env('HLS_LIVE_BROADCAST_LIMITS_MIN_ACCOUNT_AGE', 14),
			'admins_only' => env('HLS_LIVE_BROADCAST_LIMITS_ADMINS_ONLY', true)
		]
	],

	'comments' => [
		'max_falloff' => env('HLS_LIVE_COMMENTS_MAX_FALLOFF', 50)
	],
];
