<?php

namespace App\Http\Middleware\Api;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class Admin
{
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
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
