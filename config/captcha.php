<?php

/*
 * SPDX-FileCopyrightText: 2018 Daniel Supernault 
 * SPDX-License-Identifier: AGPL-3.0-only
 */
 
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
];
