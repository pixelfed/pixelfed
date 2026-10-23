<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DangerZone
{
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Only OIDC-registered users have a random unknown password and cannot complete sudo-mode password confirmation.
        if (config('remote-auth.oidc.enabled') && $request->user() && $request->user()->register_source === 'oidc') {
            return $next($request);
        }

        if ($request->session()->get('sudoModeAttempts') > 3) {
            // Invalidate the whole session
            Auth::logout();
            $request->session()->invalidate();

            return redirect(route('login'));
        }
        if (! $request->user()) {
            return redirect(route('login'));
        }
        if (! $request->is('i/auth/sudo') && $request->session()->get('sudoTrustDevice') != 1) {
            if (! $request->session()->has('sudoMode')) {
                $request->session()->put('redirectNext', $request->url());

                return redirect('/i/auth/sudo');
            }
            if ($request->session()->get('sudoMode') < now()->subMinutes(30)->timestamp) {
                $request->session()->put('redirectNext', $request->url());

                return redirect('/i/auth/sudo');
            }
        }

        return $next($request);
    }
}
