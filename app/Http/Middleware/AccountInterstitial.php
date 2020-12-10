<?php

namespace App\Http\Middleware;

use Closure;
use Auth;
use App\User;

class AccountInterstitial
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
        $ar = [
            'login', 
            'logout',
            'password*', 
            'loginAs*', 
            'i/warning*', 
            'i/auth/checkpoint',
            'i/auth/sudo',
            'site/privacy',
            'site/terms',
            'site/kb/community-guidelines',
        ];

        if(Auth::check() && !$request->is($ar)) {
            if($request->user()->has_interstitial) {
                if($request->wantsJson()) {
                    $res = ['_refresh'=>true,'error' => 403, 'message' => \App\AccountInterstitial::JSON_MESSAGE];
                    return response()->json($res, 403);
                } else {
                    return redirect('/i/warning');
                }
            } else {
                return $next($request);
            }
        } else {
            return $next($request);
        }
    }
}
