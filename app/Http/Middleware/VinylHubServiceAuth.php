<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class VinylHubServiceAuth
{
    public function handle(Request $request, Closure $next)
    {
        $configuredToken = (string) config('vinylhub.account_edge.service_token');
        $requestToken = (string) $request->header('X-VinylHub-Service-Token');

        abort_unless((bool) config('vinylhub.account_edge.enabled'), 404);
        abort_unless($configuredToken !== '' && $requestToken !== '' && hash_equals($configuredToken, $requestToken), 404);

        return $next($request);
    }
}
