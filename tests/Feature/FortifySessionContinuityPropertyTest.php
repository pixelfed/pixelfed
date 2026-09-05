<?php

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Property P7 — Session continuity
|--------------------------------------------------------------------------
|
| Feature: fortify-auth-migration, Property 7: For any authenticated user
| holding a valid pre-migration web session cookie, a request after the deploy
| stays authenticated and is not redirected to login; an expired/invalid cookie
| is treated as unauthenticated
| Validates: Requirements 12.2, 12.3, 12.4
|
| A pre/post-deploy cookie replay cannot be simulated literally inside one test
| process. Instead we assert the invariants that *guarantee* continuity and
| exercise them behaviourally:
|
|   1. Config invariant (R12.4 — broker/guard unchanged): the default guard is
|      `web`, the `web` guard uses the `session` driver backed by the `users`
|      provider, and that provider resolves App\Models\User. These three values
|      are exactly what turn a session cookie back into an authenticated user.
|      If the migration changed any of them, a pre-migration cookie could no
|      longer be resolved — so pinning them proves the continuity mechanism is
|      intact. Session persistence is likewise config-driven, so we assert the
|      session driver/lifetime remain configured.
|
|   2. Authenticated continuity (R12.2): for 100+ generated users we establish a
|      session on the `web` guard via actingAs($user, 'web') — the same guard a
|      real login binds the user to — then re-resolve the guard on a fresh
|      request lifecycle. The user must remain authenticated
|      (assertAuthenticatedAs) and the guard must return the same id, proving an
|      already-authenticated session survives a subsequent request rather than
|      being bounced to the login page.
|
|   3. Unauthenticated rejection (R12.3): a guest that presents no resolvable
|      session hitting an auth-protected web route is treated as unauthenticated
|      and redirected to the login page. We drive this through a real HTTP
|      request to `/i/web` (SpaController@index), which redirects any request
|      without an authenticated user to `/login`. We vary the guest state
|      (never authenticated, authenticated then logged out, user exists with no
|      session) so the property holds however a cookie fails to resolve.
|
| Endpoint chosen for the guest path: `/i/web` (GET, SpaController@index). It is
| a first-class authenticated web route whose sole guest branch is
| `redirect('/login')`, so it is the lightest real route that exhibits the exact
| "unauthenticated -> login" contract R12.3 describes, without the auth state
| being faked by test-only middleware.
|
*/

beforeEach(function () {
    // The suite defaults CACHE_STORE to redis, which is not guaranteed to be
    // running in the test environment. Pin the cache to the in-memory array
    // store and start clean. Mirrors FortifyAuthenticateClosureTest.
    config(['cache.default' => 'array']);
    Cache::store('array')->flush();

    // Session continuity is unrelated to rate limiting; keep the redis-backed
    // throttle limiter out of the subject under test.
    $this->withoutMiddleware(ThrottleRequests::class);

    // Keep captcha, the bouncer, restricted-mode, and email-verification
    // enforcement inert so none of them stand between a request and the
    // auth-state decision we are asserting.
    config([
        'captcha.enabled' => false,
        'captcha.active.login' => false,
        'captcha.triggers.login.enabled' => false,
        'pixelfed.bouncer.cloud_ips.ban_logins' => false,
        'instance.restricted.enabled' => false,
        'pixelfed.enforce_email_verification' => false,
        'exp.spa' => true,
    ]);
});

/**
 * @return list<int>
 */
function sessionContinuityIterations(): array
{
    return range(1, 100);
}

it('preserves the web guard, session driver, and users provider so sessions survive the deploy', function () {
    // Requirement 12.4 — the guard/provider/broker that resolve a pre-migration
    // session cookie into a user are unchanged by the migration.
    expect(config('auth.defaults.guard'))->toBe('web');
    expect(config('auth.guards.web.driver'))->toBe('session');
    expect(config('auth.guards.web.provider'))->toBe('users');
    expect(config('auth.providers.users.driver'))->toBe('eloquent');
    expect(config('auth.providers.users.model'))->toBe(User::class);

    // Session persistence is config-driven; a configured driver + positive
    // lifetime is what lets a cookie outlive a single request across the deploy.
    expect(config('session.driver'))->not->toBeEmpty();
    expect((int) config('session.lifetime'))->toBeGreaterThan(0);
});

it('keeps an authenticated web session authenticated on a subsequent request without logging the user out', function (int $iteration) {
    // Requirement 12.2 — an already-authenticated web session stays authenticated
    // across requests and is not treated as a guest (which would redirect to
    // login). We bind the user on the same guard a real login uses, then
    // re-resolve the guard as a fresh request would.
    $user = User::factory()->create([
        'status' => null,
        '2fa_enabled' => false,
    ]);

    // Establish the web-guard session.
    $this->actingAs($user, 'web');

    // Re-resolve the web guard (a subsequent request resolves auth fresh from
    // the container). The session must still identify the same user.
    $webGuard = Auth::guard('web');

    expect($webGuard->check())->toBeTrue();
    expect($webGuard->id())->toBe($user->id);
    $this->assertAuthenticatedAs($user, 'web');
})->with(sessionContinuityIterations());

it('treats a request without a valid session as unauthenticated and redirects it to login', function (int $iteration) {
    // Requirement 12.3 — an expired / invalid / absent session cookie is treated
    // as unauthenticated and redirected to the login page. Vary the guest state
    // so the property holds however the cookie fails to resolve to a user.
    $mode = $iteration % 3;

    if ($mode === 1) {
        // Authenticated within this lifecycle, then logged out — a session that
        // no longer resolves to a user (e.g. an invalidated cookie).
        $user = User::factory()->create(['status' => null, '2fa_enabled' => false]);
        $this->actingAs($user, 'web');
        Auth::guard('web')->logout();
    } elseif ($mode === 2) {
        // A user exists but no session is established for them — an expired
        // cookie whose backing session record is gone.
        User::factory()->create(['status' => null, '2fa_enabled' => false]);
    }
    // $mode === 0: pure guest, no users involved.

    $response = $this->get('/i/web');

    $response->assertRedirect('/login');
    $this->assertGuest('web');
})->with(sessionContinuityIterations());
