<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\BaseApiController;
use App\{
    Follower,
    Like,
    Place,
    Profile,
    UserFilter
};
use Auth, Cache;
use Illuminate\Support\Facades\Redis;
use App\Util\Site\Config;
use Illuminate\Http\Request;

class ApiController extends BaseApiController
{
    public function siteConfiguration(Request $request)
    {
        return response()->json(Config::get());
    }

    public function userRecommendations(Request $request)
    {
        return response()->json([]);
    }
}
