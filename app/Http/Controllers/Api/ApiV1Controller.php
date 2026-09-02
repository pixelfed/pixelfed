<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\V1Api\AccountsBlocksMutes;
use App\Http\Controllers\Api\V1Api\AccountsMisc;
use App\Http\Controllers\Api\V1Api\AccountsProfile;
use App\Http\Controllers\Api\V1Api\AccountsRelationships;
use App\Http\Controllers\Api\V1Api\Apps;
use App\Http\Controllers\Api\V1Api\Discovery;
use App\Http\Controllers\Api\V1Api\InstanceMeta;
use App\Http\Controllers\Api\V1Api\Media;
use App\Http\Controllers\Api\V1Api\Statuses;
use App\Http\Controllers\Api\V1Api\StatusInteractions;
use App\Http\Controllers\Api\V1Api\Timelines;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use League\Fractal;
use League\Fractal\Serializer\ArraySerializer;

class ApiV1Controller extends Controller
{
    use AccountsBlocksMutes,
        AccountsMisc,
        AccountsProfile,
        AccountsRelationships,
        Apps,
        Discovery,
        InstanceMeta,
        Media,
        Statuses,
        StatusInteractions,
        Timelines;

    protected $fractal;

    const PF_API_ENTITY_KEY = '_pe';

    public function __construct()
    {
        $this->fractal = new Fractal\Manager;
        $this->fractal->setSerializer(new ArraySerializer);
    }

    public function json($res, $code = 200, $headers = []): JsonResponse
    {
        return response()->json($res, $code, $headers, JSON_UNESCAPED_SLASHES);
    }
}
