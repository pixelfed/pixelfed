<?php

use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Fortify route-resolution verification (cutover safety gate)
|--------------------------------------------------------------------------
|
| Task 9.1 verifies the public auth route set resolves to Laravel Fortify
| handlers so existing forms still post successfully. This doubles as the
| cutover safety gate: it FAILS LOUDLY, naming any auth route that does not
| resolve to a Fortify controller.
|
| During this additive migration phase BOTH Auth::routes() and the Fortify
| routes are registered. Their names collide (login, register,
| password.request, password.reset), and the URI paths collide too. The
| legacy Auth::routes() are scoped to the `localhost` domain; the Fortify
| routes live at the application root with NO domain. We therefore inspect
| the router's route collection directly, match by URI + HTTP method, and
| assert the app-root (domain-less) route's action is under the
| Laravel\Fortify namespace. After Task 10 removes the legacy routes only
| the Fortify route remains, so this test stays valid post-removal.
|
*/

beforeEach(function () {
    // The suite defaults CACHE_STORE to redis, which is not guaranteed to be
    // running in the test environment. Pin the cache to the in-memory array
    // store and start clean — mirrors tests/Feature/FortifyAuthViewsTest.php.
    config(['cache.default' => 'array']);
    Cache::store('array')->flush();

    // The guest GET pages resolve a redis-backed throttle limiter; skip it so
    // the live-URL probes below stay off redis.
    $this->withoutMiddleware(ThrottleRequests::class);

    // Keep the bouncer disabled so the pages are reachable without a CIDR cache.
    config([
        'pixelfed.bouncer.cloud_ips.ban_logins' => false,
        'pixelfed.bouncer.cloud_ips.ban_signups' => false,
    ]);

    // Ensure registration is open so the register GET page is reachable.
    // Disable the config cache so config_cache() calls in the Fortify
    // registerView gating short-circuit to the plain config() overrides set
    // here instead of hitting the DB/cache (which throws in test isolation).
    // This test does not migrate a database, so also disable the max-users
    // guard whose User::count() probe would otherwise 500 on the missing
    // `users` table when rendering the register view.
    config([
        'instance.enable_cc' => false,
        'pixelfed.open_registration' => true,
        'pixelfed.enforce_max_users' => false,
    ]);
});

/**
 * Find the app-root (domain-less) route matching the given method + URI and
 * return its action name, or null when no such route exists.
 *
 * Fortify registers its routes with no domain, while the legacy Auth::routes()
 * are bound to the `localhost` domain. Filtering to the domain-less route
 * isolates the Fortify handler during the additive phase and continues to work
 * once the legacy routes are removed.
 */
function fortifyAuthRouteAction(string $method, string $uri): ?string
{
    $uri = ltrim($uri, '/');

    foreach (Route::getRoutes()->getRoutes() as $route) {
        if ($route->uri() !== $uri) {
            continue;
        }

        if (! in_array(strtoupper($method), $route->methods(), true)) {
            continue;
        }

        // Skip the legacy `localhost`-domain routes; keep the app-root one.
        if ($route->getDomain() !== null) {
            continue;
        }

        return $route->getActionName();
    }

    return null;
}

// Feature: fortify-auth-migration, Property 2: The public auth route set resolves to Fortify handlers so existing forms still post successfully
// Validates: Requirements 2.1, 2.2, 9.3, 9.4
it('resolves every public auth route to a Laravel Fortify handler', function (string $method, string $uri) {
    $action = fortifyAuthRouteAction($method, $uri);

    // R9.4 — fail loudly, identifying any unresolved route so this doubles as
    // the cutover safety gate.
    expect($action)->not->toBeNull(
        "Auth route {$method} /{$uri} does not resolve to a registered app-root handler."
    );

    // R2.1, R2.2, R9.3 — the resolved handler must be a Fortify controller,
    // not a legacy Auth\* controller. (str_contains keeps the custom failure
    // message; Pest's toContain treats extra args as additional needles.)
    expect(str_contains($action, 'Laravel\\Fortify'))->toBeTrue(
        "Auth route {$method} /{$uri} resolves to [{$action}], not a Laravel\\Fortify handler."
    );
})->with([
    'GET /login' => ['GET', '/login'],
    'POST /login' => ['POST', '/login'],
    'GET /register' => ['GET', '/register'],
    'POST /register' => ['POST', '/register'],
    'GET /forgot-password' => ['GET', '/forgot-password'],
    'POST /forgot-password' => ['POST', '/forgot-password'],
    'GET /reset-password/{token}' => ['GET', '/reset-password/{token}'],
    'POST /reset-password' => ['POST', '/reset-password'],
]);

// Validates: Requirements 2.1, 2.2 — named routes used by existing forms still resolve to Fortify.
it('resolves the named routes existing forms post to onto Fortify handlers', function (string $name) {
    $route = Route::getRoutes()->getByName($name);

    expect($route)->not->toBeNull("Named route [{$name}] is not registered.");
    expect(str_contains($route->getActionName(), 'Laravel\\Fortify'))->toBeTrue(
        "Named route [{$name}] resolves to [{$route?->getActionName()}], not a Laravel\\Fortify handler."
    );
})->with([
    'login',
    'register',
    'password.request',
    'password.update',
]);

it('serves GET /login as a live, non-5xx page', function () {
    // R2.1 — prove the Fortify path is live for guests.
    $response = $this->get('/login');

    expect($response->status())->toBeLessThan(500);
    $response->assertOk();
});

it('serves the guest GET auth paths without a server error', function (string $uri) {
    // R2.1 — the guest-facing GET auth paths are live (non-5xx).
    $response = $this->get($uri);

    expect($response->status())->toBeLessThan(500);
})->with([
    'GET /register' => '/register',
    'GET /forgot-password' => '/forgot-password',
    'GET /reset-password/{token}' => '/reset-password/test-token-abc123?email=foo%40bar.com',
]);
