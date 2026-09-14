<?php

use Buzz\LaravelHCaptcha\HttpClient;

return [
    /*
    |--------------------------------------------------------------------------
    | Global toggle
    |--------------------------------------------------------------------------
    | Whether any captcha is enabled at all. Kept for backward compatibility.
    */
    'enabled' => env('CAPTCHA_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Active driver
    |--------------------------------------------------------------------------
    | Which provider to use: "hcaptcha", "turnstile", or "cap". Admin-selectable.
    | Defaults to hcaptcha so existing instances keep their current behavior.
    */
    'driver' => env('CAPTCHA_DRIVER', 'hcaptcha'),

    /*
    |--------------------------------------------------------------------------
    | hCaptcha
    |--------------------------------------------------------------------------
    | Canonical, provider-namespaced hCaptcha config used throughout the app.
    |
    | The buzz/laravel-h-captcha package reads these values at the top level of
    | the "captcha" config (captcha.secret, captcha.sitekey, captcha.http_client,
    | captcha.options, captcha.attributes). CaptchaServiceProvider hydrates those
    | top-level keys from this block at boot, so everything lives here.
    */
    'hcaptcha' => [
        'secret' => env('CAPTCHA_H_SECRET', 'default_secret'),
        'sitekey' => env('CAPTCHA_H_SITEKEY', 'default_sitekey'),
        'http_client' => HttpClient::class,
        'options' => [
            'multiple' => false,
            'lang' => app()->getLocale(),
        ],
        'attributes' => [
            'theme' => 'light',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cloudflare Turnstile
    |--------------------------------------------------------------------------
    */
    'turnstile' => [
        'sitekey' => env('CAPTCHA_TURNSTILE_SITEKEY'),
        'secret' => env('CAPTCHA_TURNSTILE_SECRET'),
        'timeout' => (int) env('CAPTCHA_TURNSTILE_TIMEOUT', 5),
        'fail_open' => (bool) env('CAPTCHA_TURNSTILE_FAIL_OPEN', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cap (self-hosted proof-of-work CAPTCHA)
    |--------------------------------------------------------------------------
    | The endpoint is the instance base URL WITHOUT the site key, e.g.
    | https://cap.example.com. The site key is a separate value; the full API
    | endpoint (https://cap.example.com/your-site-key/) is composed by CapDriver.
    */
    'cap' => [
        'endpoint' => env('CAP_ENDPOINT'),
        'sitekey' => env('CAP_SITEKEY'),
        'secret' => env('CAP_SECRET'),
        'token_field' => env('CAP_TOKEN_FIELD', 'cap-token'),
        'timeout' => (int) env('CAP_TIMEOUT', 5),
        'fail_open' => (bool) env('CAP_FAIL_OPEN', false),
        'widget_version' => env('CAP_WIDGET_VERSION') ?: 'latest',
    ],

    /*
    |--------------------------------------------------------------------------
    | Where captcha is active
    |--------------------------------------------------------------------------
    | Per-surface toggles. Each requires the global "enabled" flag to also be on.
    */
    'active' => [
        'login' => env('CAPTCHA_ENABLED_ON_LOGIN', false),
        'register' => env('CAPTCHA_ENABLED_ON_REGISTER', false),
        'forgotpassword' => env('CAPTCHA_ENABLED_ON_FORGOT_PASSWORD', false),
        'password_reset' => env('CAPTCHA_ENABLED_ON_PASSWORD_RESET', false),
        'curated_register' => env('CAPTCHA_ENABLED_ON_CURATED_REGISTER', false),
    ],
];
