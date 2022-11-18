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
			'timeout' => env('ACTIVITYPUB_DELIVERY_TIMEOUT', 30.0),
			'concurrency' => env('ACTIVITYPUB_DELIVERY_CONCURRENCY', 10),
			'logger' => [
				'enabled' => env('AP_LOGGER_ENABLED', false),
				'driver' => 'log'
			]
		]
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
		'enabled' => env('WEBFINGER', true)
	],

	'network_timeline' => env('PF_NETWORK_TIMELINE', true),
	'network_timeline_days_falloff' => env('PF_NETWORK_TIMELINE_DAYS_FALLOFF', 2),

	'custom_emoji' => [
		'enabled' => env('CUSTOM_EMOJI', false),

		// max size in bytes, default is 2mb
		'max_size' => env('CUSTOM_EMOJI_MAX_SIZE', 2000000),
	]

];
