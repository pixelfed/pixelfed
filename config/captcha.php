<?php

return [
    'enabled' => env('CAPTCHA_ENABLED', false),
    'secret' => env('CAPTCHA_SECRET', 'default_secret'),
    'sitekey' => env('CAPTCHA_SITEKEY', 'default_sitekey'),
    'http_client' => \Buzz\LaravelHCaptcha\HttpClient::class,
    'options' => [
        'multiple' => false,
        'lang' => app()->getLocale(),
    ],
    'attributes' => [
        'theme' => 'light'
    ],

    'active' => [
    	'login' => env('CAPTCHA_ENABLED_ON_LOGIN', false),
    	'register' => env('CAPTCHA_ENABLED_ON_REGISTER', false)
    ],

    'triggers' => [
    	'login' => [
    		'enabled' => env('CAPTCHA_TRIGGERS_LOGIN_ENABLED', false),
    		'attempts' => env('CAPTCHA_TRIGGERS_LOGIN_ATTEMPTS', 2)
    	]
    ]
];
