<?php

namespace App\Http\Middleware;

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
            return redirect(config('app.url'));
        }

        return $next($request);
    }
}
