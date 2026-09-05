<?php

use App\Http\Middleware\TwoFactorAuth;
use App\Models\User;
use Illuminate\Cache\RateLimiter;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Laravel\Fortify\Features;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Fortify 2FA invariance regression (task 11.1)
|--------------------------------------------------------------------------
|
| Proves the Fortify migration left Pixelfed's own two-factor system intact:
| Fortify's 2FA feature stays disabled and unrouted, the custom `twofactor`
| middleware alias still maps to App\Http\Middleware\TwoFactorAuth, the
| checkpoint redirect / active-session bypass behaves as before, and none of
| the `2fa_*` columns changed.
|
| The existing tests/Feature/Auth/TwoFactorTest.php already covers the single
| happy-path redirect and checkpoint rendering; this file complements it with
| the disabled-feature / no-route / column-schema invariants and the
| across-many-users property.
|
| Covers Requirements 10.1, 10.2, 10.3, 10.4, 10.5.
|
*/

beforeEach(function () {
    // The suite defaults CACHE_STORE to redis, which is not guaranteed to be
    // running in the test environment. Pin the cache to the in-memory array
    // store, then start from a clean slate. Mirrors
    // tests/Feature/FortifyAuthenticateClosureTest.php.
    config(['cache.default' => 'array']);
    Cache::store('array')->flush();

    // With instance.enable_cc disabled, config_cache($key) short-circuits to
    // config($key), keeping toggles deterministic and DB-free.
    config(['instance.enable_cc' => false]);

    // The TwoFactorAuth middleware is the single subject under test; skip the
    // throttle middleware, which resolves a redis-backed limiter store.
    $this->withoutMiddleware(ThrottleRequests::class);

    // Rebind the RateLimiter singleton (resolved with the redis store at boot)
    // to the array store so nothing reaches for redis.
    $this->app->instance(
        RateLimiter::class,
        new RateLimiter(Cache::store('array'))
    );

    // Disable the bouncer login ban and pin email-verification enforcement off
    // so the /settings/home request is only ever gated by the TwoFactorAuth
    // middleware under test, not by the bouncer or the validemail middleware.
    config([
        'pixelfed.bouncer.cloud_ips.ban_logins' => false,
        'pixelfed.enforce_email_verification' => false,
    ]);
});

// Feature: fortify-auth-migration, Property 4: For every user with 2fa_enabled = true, the first authenticated request after login redirects to /i/auth/checkpoint; a valid active 2FA session proceeds without redirect; no 2fa_* column or session semantics change
// Validates: Requirements 10.1, 10.2, 10.3, 10.4, 10.5
it('preserves the 2fa checkpoint redirect and active-session bypass for every 2fa-enabled user', function (int $iteration) {
    $user = User::factory()->create([
        '2fa_enabled' => true,
        '2fa_secret' => 'TESTSECRETKEY123',
    ]);
    $user->refresh();

    // Requirement 10.3 — an authenticated request WITHOUT an active 2FA session
    // is redirected to the checkpoint and denied the requested resource.
    $this->actingAs($user)
        ->get('/settings/home')
        ->assertRedirect('/i/auth/checkpoint');

    // Requirement 10.5 — the SAME user WITH an active 2FA session proceeds to
    // the requested resource and is NOT redirected to the checkpoint.
    $response = $this->actingAs($user)
        ->withSession(['2fa.session.active' => true])
        ->get('/settings/home');

    expect($response->isRedirect('/i/auth/checkpoint'))->toBeFalse();
})->with(range(1, 100));

it('keeps Fortify two-factor authentication disabled and unrouted', function () {
    // Requirement 10.1 — Fortify's own 2FA feature is off and none of its 2FA
    // routes are registered or reachable.
    expect(Features::enabled(Features::twoFactorAuthentication()))->toBeFalse();

    expect(Route::getRoutes()->getByName('two-factor.login'))->toBeNull();

    $twoFactorChallengeRegistered = collect(Route::getRoutes()->getRoutes())
        ->contains(fn ($route) => $route->uri() === 'two-factor-challenge');

    expect($twoFactorChallengeRegistered)->toBeFalse();
});

it('keeps the twofactor middleware alias bound to the custom TwoFactorAuth middleware', function () {
    // Requirement 10.4 — the `twofactor` alias still maps to Pixelfed's own
    // middleware class (behaviorally proven by the redirect property above).
    expect(app('router')->getMiddleware())
        ->toHaveKey('twofactor')
        ->and(app('router')->getMiddleware()['twofactor'])
        ->toBe(TwoFactorAuth::class);
});

it('leaves the 2fa_* user columns unchanged', function () {
    // Requirement 10.2 — no schema change to the 2FA columns.
    expect(Schema::hasColumns('users', [
        '2fa_enabled',
        '2fa_secret',
        '2fa_backup_codes',
        '2fa_setup_at',
    ]))->toBeTrue();
});
