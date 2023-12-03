<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\BouncerService;

class OpenWebAuthController extends Controller
{
    public function showLoginForm() {

		if(config('pixelfed.bouncer.cloud_ips.ban_logins')) {
			abort_if(BouncerService::checkIp(request()->ip()), 404);
		}

        return view('auth.openwebauth');
    }

    public function openWebAuth(Request $request) {
        $handle = $request->input('handle');

        \Log::debug('Entered web-based remote openweb authentication for handle ' . $handle);
        return redirect('/?zid=' . $handle);
    }
}