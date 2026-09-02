<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseApi\Account;
use App\Http\Controllers\Api\BaseApi\Archive;
use App\Http\Controllers\Api\BaseApi\Notifications;
use App\Http\Controllers\Controller;
use League\Fractal;
use League\Fractal\Serializer\ArraySerializer;

class BaseApiController extends Controller
{
    use Account, Archive, Notifications;

    protected $fractal;

    public function __construct()
    {
        // $this->middleware('auth');
        $this->fractal = new Fractal\Manager;
        $this->fractal->setSerializer(new ArraySerializer);
    }
}
