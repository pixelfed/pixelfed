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
        if( $request->session()->get('sudoModeAttempts') > 3) {
            $request->session()->pull('redirectNext');
            $request->session()->pull('sudoModeAttempts');
            Auth::logout();
            return redirect(route('login'));
        } 
        if(!Auth::check()) {
            return redirect(route('login'));
        }
        if(!$request->is('i/auth/sudo') && $request->session()->get('sudoTrustDevice') != 1) {
            if( !$request->session()->has('sudoMode') ) {
                $request->session()->put('redirectNext', $request->url());
                return redirect('/i/auth/sudo');
            } 
            if( $request->session()->get('sudoMode') < Carbon::now()->subMinutes(30)->timestamp ) {
                $request->session()->put('redirectNext', $request->url());
                return redirect('/i/auth/sudo');
            } 
        }
        return $next($request);
    }
}
