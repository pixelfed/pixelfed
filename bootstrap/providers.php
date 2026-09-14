<?php

use App\Providers\AppServiceProvider;
use App\Providers\CaptchaServiceProvider;
use App\Providers\HorizonServiceProvider;
use App\Providers\PassportServiceProvider;

return [
    AppServiceProvider::class,
    CaptchaServiceProvider::class,
    HorizonServiceProvider::class,
    PassportServiceProvider::class,
];
