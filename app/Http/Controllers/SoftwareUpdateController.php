<?php

namespace App\Http\Controllers;

use App\Services\Internal\SoftwareUpdateService;
use Illuminate\Http\Request;

class SoftwareUpdateController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin');
    }

    public function getSoftwareUpdateCheck(Request $request)
    {
        $res = SoftwareUpdateService::get();

        return $res;
    }
}
