<?php

return [

	/*
    |--------------------------------------------------------------------------
    | Enable Autospam
    |--------------------------------------------------------------------------
    |
    | Autospam uses NLP and other techniques to detect and mitigate potential
    | spam posts from users on your server.
    | We recommend enabling this when you have open registrations, regardless
    | of how many users you have.
    |
    */

    'enabled' => env('PF_BOUNCER_ENABLED', false),


    /*
    |--------------------------------------------------------------------------
    | Ignored Tokens
    |--------------------------------------------------------------------------
    |
    | Ignored tokens are for commonly used words that may impact the detection.
    | These tokens should be lowercase and not contain spaces or non alpha-
    | numerical characters and be in comma-separated format.
    |
    */

    'ignored_tokens' => env('PF_AUTOSPAM_IGNORED_TOKENS', 'the,a,of,and'),

    'nlp' => [
    	'enabled' => false,
    	'spam_sample_limit' => env('PF_AUTOSPAM_NLP_SPAM_SAMPLE_LIMIT', 200),
    ],

    'live_filters' => [
        'enabled' => env('PF_AUTOSPAM_LIVE_FILTERS_ENABLED', false),
        'filters' => env('PF_AUTOSPAM_LIVE_FILTERS_CSV', ''),
    ]
];
