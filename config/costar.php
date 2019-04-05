<?php

/* 
 * COSTAR - Confirm Object Sentiment Transform and Reduce
 *
 * v 0.1
 *
 */



return [
	'enabled' => env('PF_COSTAR_ENABLED', true),

	'domain' => [
		'block' => env('CS_BLOCKED_DOMAINS', null) ? explode(',', env('CS_BLOCKED_DOMAINS')) : null,
		'cw' => env('CS_CW_DOMAINS', null) ? explode(',', env('CS_CW_DOMAINS')) : null,
		'unlisted' => env('CS_UNLISTED_DOMAINS', null) ? explode(',', env('CS_UNLISTED_DOMAINS')) : null,
	],

];