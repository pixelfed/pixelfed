<?php

/*
 * This file is part of Laravel Hashids.
 *
 * (c) Vincent Klaiber <hello@vinkla.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Default Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the connections below you wish to use as
    | your default connection for all work. Of course, you may use many
    | connections at once using the manager class.
    |
    */

    'default' => 'main',

    /*
    |--------------------------------------------------------------------------
    | Hashids Connections
    |--------------------------------------------------------------------------
    |
    | Here are each of the connections setup for your application. Example
    | configuration has been included, but you may add as many connections as
    | you would like.
    |
    */

    'connections' => [

        'main' => [
            'salt'   => env('APP_HASHID_SALT', 'v3MsJ1Hgnlma8YPrD3f4sW6vAn6zLnkuh6vOpKnR5IKkLqDGIk7TCADS2igpEN4ADrtXkkzV2E8HBfzpz7BreDzQqVOYDMeb4cJ1xhDhDwDeicZVUPyrxihHDaMWpTsP'),
            'length' => '24',
        ],

        'alternative' => [
            'salt'   => 'your-salt-string',
            'length' => 'your-length-integer',
        ],

    ],

];
