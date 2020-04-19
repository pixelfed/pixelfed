<?php

/* 
 * COSTAR - Confirm Object Sentiment Transform and Reduce
 *
 * v 0.1
 *
 */



return [
	'enabled' => false,

	'domain' => [
		'block' => env('CS_BLOCKED_DOMAINS', null) ? explode(',', env('CS_BLOCKED_DOMAINS')) : null,
		'cw' => env('CS_CW_DOMAINS', null) ? explode(',', env('CS_CW_DOMAINS')) : null,
		'unlisted' => env('CS_UNLISTED_DOMAINS', null) ? explode(',', env('CS_UNLISTED_DOMAINS')) : null,
	],

	'keyword' => [
		'block' => env('CS_BLOCKED_KEYWORDS', null) ? explode(',', env('CS_BLOCKED_KEYWORDS')) : null,
		'cw' => env('CS_CW_KEYWORDS', null) ? explode(',', env('CS_CW_KEYWORDS')) : null,
		'unlisted' => env('CS_UNLISTED_KEYWORDS', null) ? explode(',', env('CS_UNLISTED_KEYWORDS')) : null,
	],

	'actor' => [
		'block' => env('CS_BLOCKED_ACTOR', null) ? explode(',', env('CS_BLOCKED_ACTOR')) : null,
		'cw' => env('CS_CW_ACTOR', null) ? explode(',', env('CS_CW_ACTOR')) : null,
		'unlisted' => env('CS_UNLISTED_ACTOR', null) ? explode(',', env('CS_UNLISTED_ACTOR')) : null,
	]

];