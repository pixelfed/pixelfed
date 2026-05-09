<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class AikidoMiddleware
{
    public function handle($request, Closure $next)
    {
        if (!extension_loaded('aikido')) {
            return $next($request);
        }

        $userId = Auth::id();

        if ($userId) {
            \aikido\set_user($userId, Auth::user()?->username);
        }

        $decision = \aikido\should_block_request();

        if ($decision->block) {
            if ($decision->type == 'blocked') {
                if ($decision->trigger == 'user') {
                    return response('Your user is blocked!', 403);
                }
            } elseif ($decision->type == 'ratelimited') {
                if ($decision->trigger == 'user') {
                    return response('Your user exceeded the rate limit for this endpoint!', 429);
                } elseif ($decision->trigger == 'ip') {
                    return response("Your IP ({$decision->ip}) exceeded the rate limit for this endpoint!", 429);
                } elseif ($decision->trigger == 'group') {
                    return response('Your group exceeded the rate limit for this endpoint!', 429);
                }
            }
        }

        return $next($request);
    }
}
