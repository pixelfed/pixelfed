<?php

return [

	'announcement' => [
		'enabled' => env('INSTANCE_ANNOUNCEMENT_ENABLED', false),
		'message' => env('INSTANCE_ANNOUNCEMENT_MESSAGE', 'Example announcement message.<br><span class="font-weight-normal">Something else here</span>')
	],

	'contact' => [
		'enabled' => env('INSTANCE_CONTACT_FORM', false),
		'max_per_day' => env('INSTANCE_CONTACT_MAX_PER_DAY', 1),
	],

	'discover' => [
		'loops' => [
			'enabled' => false
		],
		'tags' => [
			'is_public' => env('INSTANCE_PUBLIC_HASHTAGS', false)
		],
	],
	
	'email' => env('INSTANCE_CONTACT_EMAIL'),

	'timeline' => [
		'local' => [
			'is_public' => env('INSTANCE_PUBLIC_LOCAL_TIMELINE', true)
		]
	],

	'page' => [
		'503' => [
			'header' => env('PAGE_503_HEADER', 'Service Unavailable'),
			'body' => env('PAGE_503_BODY', 'Our service is in maintenance mode, please try again later.')
		]
	],

];