<?php

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
    */
    'hcaptcha' => [
        'secret' => env('CAPTCHA_H_SECRET', 'default_secret'),
        'sitekey' => env('CAPTCHA_H_SITEKEY', 'default_sitekey'),
        'timeout' => (int) env('CAPTCHA_H_TIMEOUT', 5),
        'fail_open' => (bool) env('CAPTCHA_H_FAIL_OPEN', false),
        'lang' => env('CAPTCHA_H_LANG'),  // Optional widget locale (e.g. "fr"). Null uses hCaptcha auto-detection.
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
    | Cap
    |--------------------------------------------------------------------------
    | The endpoint is the instance base URL WITHOUT the site key, e.g.
    | https://cap.example.com. The site key/secret are separate values;
    */
    'cap' => [
        'endpoint' => env('CAPTCHA_CAP_ENDPOINT'),
        'sitekey' => env('CAPTCHA_CAP_SITEKEY'),
        'secret' => env('CAPTCHA_CAP_SECRET'),
        'token_field' => env('CAPTCHA_CAP_TOKEN_FIELD', 'cap-token'),
        'timeout' => (int) env('CAPTCHA_CAP_TIMEOUT', 5),
        'fail_open' => (bool) env('CAPTCHA_CAP_FAIL_OPEN', false),
        'widget_version' => env('CAPTCHA_CAP_WIDGET_VERSION') ?: 'latest',
    ],

    /*
    |--------------------------------------------------------------------------
    | Where captcha is active
    |--------------------------------------------------------------------------
    | Per-surface toggles. Each requires the global "enabled" flag to also be on.
    */
    'active' => [
        'login' => env('CAPTCHA_ENABLED_ON_LOGIN', true),
        'register' => env('CAPTCHA_ENABLED_ON_REGISTER', true),
        'curated_register' => env('CAPTCHA_ENABLED_ON_CURATED_REGISTER', true),
        'forgot_email' => env('CAPTCHA_ENABLED_ON_FORGOT_EMAIL', true),
        'forgot_password' => env('CAPTCHA_ENABLED_ON_FORGOT_PASSWORD', true),
        'password_reset' => env('CAPTCHA_ENABLED_ON_PASSWORD_RESET', true),
    ],
];
