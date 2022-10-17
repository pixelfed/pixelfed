<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Portfolio Domain
    |--------------------------------------------------------------------------
    |
    | This value is the domain used for the portfolio feature. Only change
    | the default value if you have a subdomain configured. You must use
    | a subdomain on the same app domain.
    |
    */
    'domain' => env('PORTFOLIO_DOMAIN', config('pixelfed.domain.app')),

    /*
    |--------------------------------------------------------------------------
    | Portfolio Path
    |--------------------------------------------------------------------------
    |
    | This value is the path used for the portfolio feature. Only change
    | the default value if you have a subdomain configured. If you want
    | to use the root path of the subdomain, leave this value empty.
    |
    | WARNING: SETTING THIS VALUE WITHOUT A SUBDOMAIN COULD BREAK YOUR
    | INSTANCE, SO ONLY CHANGE THIS IF YOU KNOW WHAT YOU'RE DOING.
    |
    */
    'path' => env('PORTFOLIO_PATH', '/i/portfolio'),
];
