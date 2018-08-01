<?php

namespace App\Http\Middleware;

use Auth, Closure;

class EmailVerificationCheck
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
        if($request->user() && 
            config('pixelfed.enforce_email_verification') &&
            is_null($request->user()->email_verified_at) && 
            !$request->is('i/verify-email') && !$request->is('log*') && 
            !$request->is('i/confirm-email/*')
        ) {
            return redirect('/i/verify-email');
        } 
        return $next($request);
    }
}
