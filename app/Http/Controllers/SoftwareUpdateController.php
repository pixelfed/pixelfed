<?php

namespace App\Http\Controllers;

use App\Services\Internal\SoftwareUpdateService;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Middleware;

#[Middleware('auth')]
#[Middleware('admin')]
class SoftwareUpdateController extends Controller
{
    public function getSoftwareUpdateCheck(Request $request)
    {
        $res = SoftwareUpdateService::get();

        return $res;
    }
}
