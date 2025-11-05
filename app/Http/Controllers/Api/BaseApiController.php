<?php

namespace App\Http\Controllers\Api;

use App\Avatar;
use App\Http\Controllers\AvatarController;
use App\Http\Controllers\Controller;
use App\Jobs\AvatarPipeline\AvatarOptimize;
use App\Jobs\NotificationPipeline\NotificationWarmUserCache;
use App\Services\AccountService;
use App\Services\NotificationService;
use App\Services\StatusService;
use App\Status;
use App\StatusArchived;
use App\Transformer\Api\StatusStatelessTransformer;
use Auth;
use Cache;
use Illuminate\Http\Request;
use League\Fractal;
use League\Fractal\Serializer\ArraySerializer;

class BaseApiController extends Controller
{
    protected $fractal;

    public function __construct()
    {
        // $this->middleware('auth');
        $this->fractal = new Fractal\Manager;
        $this->fractal->setSerializer(new ArraySerializer);
    }
}
