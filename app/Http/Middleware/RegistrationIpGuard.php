<?php

namespace App\Http\Middleware;

use App\Services\RegistrationBlockService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rejects signup requests from IPs or ranges blocked by an admin.
 */
class RegistrationIpGuard
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_if(
            RegistrationBlockService::ipBlocked($request->ip(), $request->isMethod('POST')),
            403,
            RegistrationBlockService::IP_MESSAGE
        );

        return $next($request);
    }
}
