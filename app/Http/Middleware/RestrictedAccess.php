<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class RestrictedAccess
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     * @param string|null              $guard
     *
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        if(config('instance.restricted.enabled')) {
            if (!Auth::guard($guard)->check()) {
                $p = ['login', 'password*', 'loginAs*'];
                if(!$request->is($p)) {
                    return redirect('/login');
                }
            }
        }

        return $next($request);
    }
}
