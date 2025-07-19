<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DeprecatedEndpoint
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        abort_if(now()->gt('Jan 01, 2024'), 404);
        $response = $next($request);
        $link = $response->headers->has('link') ? $response->headers->get('link').',<https://pixelfed.org/kb/10404>; rel="deprecation"' : '<https://pixelfed.org/kb/10404>; rel="deprecation"';
        $response->withHeaders([
            'Deprecation' => 'Sat, 01 Jul 2023 00:00:00 GMT',
            'Sunset' => 'Mon, 01 Jan 2024 00:00:00 GMT',
            'Link' => $link,
        ]);

        return $response;
    }
}
