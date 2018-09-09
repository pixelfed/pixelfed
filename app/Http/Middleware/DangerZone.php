<?php

namespace App\Http\Middleware;

use App, Auth, Closure;
use Carbon\Carbon;

class DangerZone
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
        if(!Auth::check()) {
            return redirect(route('login'));
        }
        if(!$request->is('i/auth/sudo')) {
            if( false == $request->cookie('sudoMode') ) {
                return redirect('/i/auth/sudo')->withCookie('redirectNext', $request->url());
            } 
            if( $request->cookie('sudoMode') < Carbon::now()->subMinutes(30)->timestamp ) {
                return redirect('/i/auth/sudo')->withCookie('redirectNext', $request->url());
            } 
        }
        return $next($request);
    }
}
