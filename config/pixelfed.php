<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Domains
    |--------------------------------------------------------------------------
    |
    | Application domains used for routing
    |
    */
    'domain' => [
      'admin' => env('ADMIN_DOMAIN'),
      'app' => env('APP_DOMAIN'),
    ],

    /*
    |--------------------------------------------------------------------------
    | PixelFed Version
    |--------------------------------------------------------------------------
    |
    | This value is the version of your PixelFed instance.
    |
    */
    'version' => '0.1.0',

    /*
    |--------------------------------------------------------------------------
    | NodeInfo Route Path
    |--------------------------------------------------------------------------
    |
    | Do not change this value unless you know what you are doing.
    |
    */
    'nodeinfo' => [
      'url' => config('app.url') . '/' . 'api/nodeinfo/2.0.json'
    ],

    /*
    |--------------------------------------------------------------------------
    | PHP/ImageMagic/GD Memory Limit
    |--------------------------------------------------------------------------
    |
    | This memory_limit value is only used for image processing. The
    | default memory_limit php.ini is used for the rest of the app.
    |
    */
    'memory_limit' => '1024M',

    /*
    |--------------------------------------------------------------------------
    | Restricted Usernames
    |--------------------------------------------------------------------------
    |
    | Optional blacklist to prevent registering usernames that could
    | be confused for admin or system services.
    |
    */
    'restricted_names' => [
      'reserved_routes' => true,
      'use_blacklist' => false
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Allow New Registrations
    |--------------------------------------------------------------------------
    |
    | Enable/disable new local account registrations.
    |
    */
    'open_registration' => env('OPEN_REGISTRATION', true),
    
];