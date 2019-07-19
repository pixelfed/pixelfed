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
		'sharedInbox' => env('AP_SHAREDINBOX', false),

		'remoteFollow' => env('AP_REMOTE_FOLLOW', false),

		'delivery' => [
			'timeout' => env('ACTIVITYPUB_DELIVERY_TIMEOUT', 2.0),
			'concurrency' => env('ACTIVITYPUB_DELIVERY_CONCURRENCY', 10)
		]
	],

	'atom' => [
		'enabled' => env('ATOM_FEEDS', true),
	],

	'nodeinfo' => [
		'enabled' => env('NODEINFO', true),
	],

	'webfinger' => [
		'enabled' => env('WEBFINGER', true)
	],

];