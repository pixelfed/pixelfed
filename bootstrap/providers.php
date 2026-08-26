<?php

use App\Providers\AppServiceProvider;
use App\Providers\AuthServiceProvider;
use App\Providers\EventServiceProvider;
use App\Providers\HorizonServiceProvider;
use App\Providers\PassportServiceProvider;

return [
    AppServiceProvider::class,
    AuthServiceProvider::class,
    HorizonServiceProvider::class,
    EventServiceProvider::class,
    PassportServiceProvider::class,
];
