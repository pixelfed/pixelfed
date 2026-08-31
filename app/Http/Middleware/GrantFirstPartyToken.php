<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Passport\TransientToken;
use Symfony\Component\HttpFoundation\Response;

class GrantFirstPartyToken
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle($request, Closure $next)
    {
        $user = $request->user();

        // authenticated by session, not bearer token: first-party web UI
        if ($user && ! $user->token() && $request->hasSession()) {
            $user->withAccessToken(new TransientToken);
        }

        return $next($request);
    }
}
