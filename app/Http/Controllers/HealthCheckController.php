<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

class HealthCheckController extends Controller
{
    public function get(Request $request): Response
    {
        return response('OK')->withHeaders([
            'Content-Type' => 'text/plain',
            'Cache-Control' => 'max-age=0, must-revalidate, no-cache, no-store',
        ]);
    }
}
