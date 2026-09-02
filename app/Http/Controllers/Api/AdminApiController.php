<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Admin\AdminConfiguration;
use App\Http\Controllers\Api\Admin\AdminInstances;
use App\Http\Controllers\Api\Admin\AdminModeration;
use App\Http\Controllers\Api\Admin\AdminStats;
use App\Http\Controllers\Api\Admin\AdminUsers;
use App\Http\Controllers\Controller;

class AdminApiController extends Controller
{
    use AdminConfiguration, AdminInstances, AdminModeration, AdminStats, AdminUsers;
}
