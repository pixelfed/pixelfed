<?php

/*
 * SPDX-FileCopyrightText: 2018 Daniel Supernault 
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace App\Http\Middleware;

use Closure;

class EmailVerificationCheck
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if ($request->user() &&
            config('pixelfed.enforce_email_verification') &&
            is_null($request->user()->email_verified_at) &&
            !$request->is(
                'i/auth/*',
                'i/verify-email', 
                'log*', 
                'i/confirm-email/*', 
                'settings/home',
                'settings/email'
            )
        ) {
            return redirect('/i/verify-email');
        }

        return $next($request);
    }
}
