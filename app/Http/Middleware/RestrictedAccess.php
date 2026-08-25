<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class RestrictedAccess
{
    /**
     * Routes that should be accessible without authentication
     * when the instance is restricted.
     *
     * @var array
     */
    protected $allowedRoutes = [
        'login',
        'logout',
        'password*',
        'loginAs*',
        'auth/oidc*',
        'auth/remote*',
        'auth/mastodon*',
        'auth/raw*',
        'auth/forgot*',
        'auth/pci*',
        'auth/sign_up*',
        'auth/invite*',
        'oauth/token',
        'oauth/authorize',
        'i/app-email-verify',
        'i/app-email-resend',
        'api/v1/apps',
        'api/v1/instance',
        '.well-known/*',
    ];

    /**
     * Routes that are only allowed when open registration is enabled.
     *
     * @var array
     */
    protected $registrationRoutes = [
        'register',
        'register/*',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        if (config('instance.restricted.enabled')) {
            if (! Auth::guard($guard)->check()) {
                $allowed = $this->allowedRoutes;

                if (config('pixelfed.open_registration')) {
                    $allowed = array_merge($allowed, $this->registrationRoutes);
                }

                if (! $request->is($allowed)) {
                    return redirect('/login');
                }
            }
        }

        return $next($request);
    }
}
