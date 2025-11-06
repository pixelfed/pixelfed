<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class StreamingController extends Controller
{
    const PF_API_ENTITY_KEY = '_pe';

    public function json($res, $code = 200, $headers = [])
    {
        return response()->json($res, $code, $headers, JSON_UNESCAPED_SLASHES);
    }

    /**
     * GET /api/v2/streaming/config
     *
     *
     * @return object
     */
    public function getWebsocketConfig()
    {
        return config('broadcasting.default') === 'pusher' ? [
            'host' => config('broadcasting.connections.pusher.options.host'),
            'port' => config('broadcasting.connections.pusher.options.port'),
            'key' => config('broadcasting.connections.pusher.key'),
            'cluster' => config('broadcasting.connections.pusher.options.cluster'),
        ] : [];
    }
}