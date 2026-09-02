<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\V1Dot1\AccountManagement;
use App\Http\Controllers\Api\V1Dot1\Archive;
use App\Http\Controllers\Api\V1Dot1\PushNotifications;
use App\Http\Controllers\Api\V1Dot1\Registration;
use App\Http\Controllers\Api\V1Dot1\Reports;
use App\Http\Controllers\Api\V1Dot1\Statuses;
use App\Http\Controllers\Api\V1Dot1\WebSettings;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use League\Fractal;
use League\Fractal\Serializer\ArraySerializer;

class ApiV1Dot1Controller extends Controller
{
    use AccountManagement, Archive, PushNotifications, Registration, Reports, Statuses, WebSettings;

    protected $fractal;

    const REPORT_TYPES = [
        'spam',
        'sensitive',
        'abusive',
        'underage',
        'violence',
        'copyright',
        'impersonation',
        'scam',
        'terrorism',
    ];

    public function __construct()
    {
        $this->fractal = new Fractal\Manager;
        $this->fractal->setSerializer(new ArraySerializer);
    }

    public function json($res, $code = 200, $headers = []): JsonResponse
    {
        return response()->json($res, $code, $headers, JSON_UNESCAPED_SLASHES);
    }

    public function error($msg, $code = 400, $extra = [], $headers = []): JsonResponse
    {
        $res = [
            'msg' => $msg,
            'code' => $code,
        ];

        return response()->json(array_merge($res, $extra), $code, $headers, JSON_UNESCAPED_SLASHES);
    }
}
