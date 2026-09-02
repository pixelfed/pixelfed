<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use App\Services\Internal\SoftwareUpdateService;
use Illuminate\Http\Request;

class SoftwareUpdateController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
            'admin',
        ];
    }

    public function getSoftwareUpdateCheck(Request $request)
    {
        $res = SoftwareUpdateService::get();

        return $res;
    }
}
