<?php

namespace App\Http\Middleware\Api;

use Auth;
use Closure;

class Admin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (Auth::check() == false || Auth::user()->is_admin == false) {
            return abort(403, 'You must be an administrator to do that');
        }

        return $next($request);
    }
}
