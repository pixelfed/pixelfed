<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\V2Api\InstanceSearch;
use App\Http\Controllers\Api\V2Api\Media;
use App\Http\Controllers\Api\V2Api\StatusContext;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class ApiV2Controller extends Controller
{
    use InstanceSearch, Media, StatusContext;

    const PF_API_ENTITY_KEY = '_pe';

    public function json($res, $code = 200, $headers = []): JsonResponse
    {
        return response()->json($res, $code, $headers, JSON_UNESCAPED_SLASHES);
    }
}
