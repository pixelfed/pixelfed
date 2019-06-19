<?php

return [
	'email' => env('INSTANCE_CONTACT_EMAIL'),

	'contact' => [
		'enabled' => env('INSTANCE_CONTACT_FORM', false),
		'max_per_day' => env('INSTANCE_CONTACT_MAX_PER_DAY', 1),
	],
];