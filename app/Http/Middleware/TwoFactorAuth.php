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
                if($request->session()->has('2fa.session.active') !== true && !$request->is($checkpoint))
                {
                    return redirect('/i/auth/checkpoint');
                }
            }
        }
        return $next($request);
    }
}
