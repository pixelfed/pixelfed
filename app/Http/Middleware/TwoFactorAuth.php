<?php

namespace App\Http\Middleware;

use Auth;
use Closure;

class TwoFactorAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if($request->user()) {
            $user = $request->user();
            $enabled = (bool) $user->{'2fa_enabled'};
            if($enabled != false) {
                $checkpoint = 'i/auth/checkpoint';
                if($request->session()->has('2fa.session.active') !== true && !$request->is($checkpoint) && !$request->is('logout'))
                {
                    return redirect('/i/auth/checkpoint');
                } elseif($request->session()->has('2fa.attempts') && (int) $request->session()->get('2fa.attempts') > 3) {
                    $request->session()->pull('2fa.attempts');
                    Auth::logout();
                }
            }
        }
        return $next($request);
    }
}
