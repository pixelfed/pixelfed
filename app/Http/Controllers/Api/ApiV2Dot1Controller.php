<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiV2Dot1Controller extends Controller
{
    const PF_API_ENTITY_KEY = '_pe';

    public function json($res, $code = 200, $headers = []): JsonResponse
    {
        return response()->json($res, $code, $headers, JSON_UNESCAPED_SLASHES);
    }

    /**
     * GET /api/v2.1/config
     *
     * Optimized configuration endpoint for the new mobile app and webUI
     */
    public function getConfig(Request $request)
    {
        return $this->json(app(AppService::class)->getConfig());
    }
}
