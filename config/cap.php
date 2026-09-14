<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cap instance endpoint
    |--------------------------------------------------------------------------
    |
    | The full URL of your self-hosted Cap instance, including the site key.
    | Example: https://cap.example.com/your-site-key/
    |
    */
    'endpoint' => env('CAP_ENDPOINT'),

    /*
    |--------------------------------------------------------------------------
    | Secret key
    |--------------------------------------------------------------------------
    |
    | The secret key generated in your Cap dashboard.
    | Never expose this value on the client side.
    |
    */
    'secret' => env('CAP_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Token field name
    |--------------------------------------------------------------------------
    |
    | The name of the hidden field automatically injected by the Cap widget
    | into the parent form. Can be overridden via the data-cap-hidden-field-name
    | attribute.
    |
    */
    'token_field' => env('CAP_TOKEN_FIELD', 'cap-token'),

    /*
    |--------------------------------------------------------------------------
    | Verification timeout
    |--------------------------------------------------------------------------
    |
    | Time (in seconds) before giving up on the request to /siteverify.
    |
    */
    'timeout' => (int) env('CAP_TIMEOUT', 5),

    /*
    |--------------------------------------------------------------------------
    | Fail-open mode
    |--------------------------------------------------------------------------
    |
    | When true, any communication error with the Cap instance (network,
    | timeout, 5xx server error) lets the request through instead of blocking
    | it. An explicitly invalid token (success: false) is always rejected,
    | regardless of this setting.
    |
    | Recommended in production when service availability matters more than
    | anti-spam protection.
    |
    */
    'fail_open' => (bool) env('CAP_FAIL_OPEN', false),

    /*
    |--------------------------------------------------------------------------
    | Iframe widget route
    |--------------------------------------------------------------------------
    |
    | Path of the route serving the Cap widget in iframe mode (permissive CSP).
    | Use @capFrame in your Blade templates to embed this mode.
    |
    */
    'frame_route' => env('CAP_FRAME_ROUTE', 'cap-frame'),
];
