<?php

namespace App\Http\Middleware\Api;

use Closure;
use Illuminate\Http\Request;

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
        if (! $request->user() || $request->user()->is_admin == false) {
            abort(403, 'You must be an administrator to do that');
        }

        return $next($request);
    }
}
