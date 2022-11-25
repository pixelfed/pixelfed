<?php

return [
    'enabled'       => env('OIDC_ENABLED', false),
    'client_id'     => env('OIDC_CLIENT_ID'),
    'client_secret' => env('OIDC_CLIENT_SECRET'),
    'provider_url'  => env('OIDC_PROVIDER_URL'),
    'provider_name' => env('OIDC_PROVIDER_NAME', 'OIDC'),
    'scopes'        => ['openid', 'roles']
];
