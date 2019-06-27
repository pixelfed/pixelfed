<?php

return [
	'email' => env('INSTANCE_CONTACT_EMAIL'),

	'contact' => [
		'enabled' => env('INSTANCE_CONTACT_FORM', false),
		'max_per_day' => env('INSTANCE_CONTACT_MAX_PER_DAY', 1),
	],

	'announcement' => [
		'enabled' => env('INSTANCE_ANNOUNCEMENT_ENABLED', true),
		'message' => env('INSTANCE_ANNOUNCEMENT_MESSAGE', 'Example announcement message.<br><span class="font-weight-normal">Something else here</span>')
	]
];