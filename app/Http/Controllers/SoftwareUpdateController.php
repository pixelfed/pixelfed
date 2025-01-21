<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\Internal\SoftwareUpdateService;

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
