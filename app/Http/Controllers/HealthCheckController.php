<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HealthCheckController extends Controller
{
    public function get(Request $request)
    {
        return response('OK')->withHeaders([
            'Content-Type' => 'text/plain',
            'Cache-Control' => 'max-age=0, must-revalidate, no-cache, no-store'
        ]);
    }
}
