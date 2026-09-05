<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * TEMPORARY diagnostic middleware for a 419 (CSRF token mismatch) on the
 * curated sign-up flow (POST /auth/sign_up).
 *
 * This middleware logs unconditionally for the /auth/sign_up route and is
 * TEMPORARY — remove the whole middleware and its registration once the 419
 * is diagnosed.
 *
 * It must run AFTER StartSession (so the session is available) but BEFORE
 * ValidateCsrfToken (so the request is observed before it can be rejected).
 */
class CsrfSessionDebug
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->is('auth/sign_up') && ! $request->is('auth/sign_up/*')) {
            return $next($request);
        }

        $this->logRequest($request);

        /** @var Response $response */
        $response = $next($request);

        if ($request->isMethod('POST')) {
            $this->logResponse($request, $response);
        }

        return $response;
    }

    /**
     * Mask a token so the full value is never written to logs.
     */
    protected function maskToken(mixed $token): string
    {
        if (is_string($token)) {
            return substr($token, 0, 12).'…len='.strlen($token);
        }

        return (string) json_encode($token);
    }

    /**
     * Log the incoming request state before it reaches ValidateCsrfToken.
     */
    protected function logRequest(Request $request): void
    {
        $sessionCookieName = (string) config('session.cookie');
        $hasSessionCookie = $request->cookies->has($sessionCookieName);

        $sessionStarted = false;
        $sessionId = null;
        $sessionToken = null;

        try {
            $session = $request->session();
            $sessionStarted = $session->isStarted();
            $sessionId = $session->getId();
            $sessionToken = $session->token();
        } catch (\Throwable $e) {
            Log::info('[CSRF_DEBUG] sign_up request: session access failed', [
                'error' => $e->getMessage(),
            ]);
        }

        $submittedToken = $request->input('_token')
            ?? $request->header('X-CSRF-TOKEN')
            ?? $request->header('X-XSRF-TOKEN');

        $tokensMatch = is_string($sessionToken)
            && is_string($submittedToken)
            && hash_equals($sessionToken, $submittedToken);

        Log::info('[CSRF_DEBUG] sign_up request', [
            'method' => $request->method(),
            'path' => $request->path(),
            'step' => $request->input('step'),
            'session_cookie_name' => $sessionCookieName,
            'has_session_cookie' => $hasSessionCookie,
            'session_started' => $sessionStarted,
            'session_id' => is_string($sessionId) ? substr($sessionId, 0, 10) : null,
            'session_token' => $this->maskToken($sessionToken),
            'submitted_token' => $this->maskToken($submittedToken),
            'tokens_match' => $tokensMatch,
            'cookie_keys' => array_keys($request->cookies->all()),
            'has_xsrf_cookie' => $request->cookies->has('XSRF-TOKEN'),
            'origin' => $request->header('Origin'),
            'referer' => $request->header('Referer'),
            'user_agent' => substr((string) $request->userAgent(), 0, 60),
            'ip' => $request->ip(),
            'session_driver' => config('session.driver'),
            'session_domain' => config('session.domain'),
            'session_secure' => config('session.secure'),
        ]);
    }

    /**
     * Log the session state after the request completes to detect rotation.
     */
    protected function logResponse(Request $request, Response $response): void
    {
        $sessionId = null;
        $sessionToken = null;

        try {
            $session = $request->session();
            $sessionId = $session->getId();
            $sessionToken = $session->token();
        } catch (\Throwable $e) {
            // Session may be unavailable after the response; ignore.
        }

        Log::info('[CSRF_DEBUG] sign_up response', [
            'status' => $response->getStatusCode(),
            'session_id_after' => is_string($sessionId) ? substr($sessionId, 0, 10) : null,
            'session_token_after' => $this->maskToken($sessionToken),
        ]);
    }
}
