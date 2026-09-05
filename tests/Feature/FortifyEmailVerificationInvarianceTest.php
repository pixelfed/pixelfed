<?php

use App\Http\Middleware\EmailVerificationCheck;
use App\Models\EmailVerification;
use App\Models\User;
use Illuminate\Cache\RateLimiter;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

// Feature: fortify-auth-migration, Property 5: The validemail middleware behavior and EmailVerification records are unaffected and no Fortify verification route is registered
// Validates: Requirements 11.1, 11.2, 11.3, 11.4, 11.5, 11.6

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    // The suite defaults CACHE_STORE to redis, which is not guaranteed to be
    // running in the test environment. Pin the cache to the in-memory array
    // store, then start from a clean slate. Mirrors the sibling Fortify tests.
    config(['cache.default' => 'array']);
    Cache::store('array')->flush();

    // With instance.enable_cc disabled, config_cache($key) short-circuits to
    // config($key), making the auth toggles deterministic and DB-free.
    config(['instance.enable_cc' => false]);

    // Skip the throttle middleware: it resolves a redis-backed limiter store,
    // and these tests exercise middleware behavior, not rate limiting.
    $this->withoutMiddleware(ThrottleRequests::class);

    // Rebind the RateLimiter singleton (resolved with the redis store at boot)
    // to the array store so the auth pipeline never reaches for redis.
    $this->app->instance(
        RateLimiter::class,
        new RateLimiter(Cache::store('array'))
    );

    // Keep the bouncer ban disabled so no guardrail interferes with the
    // authenticated request paths under test.
    config([
        'pixelfed.bouncer.cloud_ips.ban_logins' => false,
        'pixelfed.bouncer.cloud_ips.ban_signups' => false,
    ]);
});

/*
|--------------------------------------------------------------------------
| R11.1 — Fortify email-verification feature disabled; no Fortify routes
|--------------------------------------------------------------------------
*/

it('leaves the Fortify email-verification feature disabled', function () {
    // R11.1 — Fortify does not own the email-verification feature.
    expect(Features::enabled(Features::emailVerification()))->toBeFalse();
});

it('registers no Fortify-style email-verification routes', function () {
    // R11.1 — the Laravel/Fortify verification.* route + email/verify path are
    // NOT registered. Pixelfed uses its own /i/verify-email flow instead.
    $routes = Route::getRoutes();

    expect($routes->getByName('verification.notice'))->toBeNull();
    expect($routes->getByName('verification.verify'))->toBeNull();
    expect($routes->getByName('verification.send'))->toBeNull();

    $paths = collect($routes->getRoutes())->map(fn ($route) => $route->uri());
    expect($paths->contains('email/verify'))->toBeFalse();
    expect($paths->contains('email/verify/{id}/{hash}'))->toBeFalse();
});

it('keeps the Pixelfed /i/verify-email route registered', function () {
    // R11.1 / R11.3 — Pixelfed's OWN verification route MUST still exist; only
    // Fortify's Laravel-style verification.* routes are absent.
    $paths = collect(Route::getRoutes()->getRoutes())->map(fn ($route) => $route->uri());

    expect($paths->contains('i/verify-email'))->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| R11.4 — validemail alias binding preserved
|--------------------------------------------------------------------------
*/

it('binds the validemail middleware alias to EmailVerificationCheck', function () {
    // R11.4 — the custom middleware remains bound to the validemail alias.
    $aliases = app('router')->getMiddleware();

    expect($aliases)->toHaveKey('validemail');
    expect($aliases['validemail'])->toBe(EmailVerificationCheck::class);
});

/*
|--------------------------------------------------------------------------
| R11.5 — unverified user is redirected to /i/verify-email
|--------------------------------------------------------------------------
*/

it('redirects an authenticated unverified user off a validemail-protected route', function () {
    // R11.5 — WHILE enforce_email_verification is true, an authenticated user
    // with a null email_verified_at requesting a validemail-protected route is
    // redirected to /i/verify-email.
    //
    // GET '/' (timeline.personal) runs through the app-domain web group which
    // applies ['validemail', 'twofactor', 'localization']. The user must NOT
    // trigger the 2FA checkpoint first (twofactor runs alongside validemail),
    // so 2fa is disabled on the user.
    config(['pixelfed.enforce_email_verification' => true]);

    $user = User::factory()->unverified()->create([
        '2fa_enabled' => false,
        'status' => null,
    ]);

    expect($user->email_verified_at)->toBeNull();

    $this->actingAs($user)
        ->get('/')
        ->assertRedirect('/i/verify-email');
});

/*
|--------------------------------------------------------------------------
| R11.6 — verified user is granted access
|--------------------------------------------------------------------------
*/

it('does not redirect an authenticated verified user off a validemail-protected route', function () {
    // R11.6 — WHILE enforce_email_verification is true, an authenticated user
    // WITH email_verified_at set requesting the same route is NOT redirected to
    // /i/verify-email (validemail passes them through).
    config(['pixelfed.enforce_email_verification' => true]);

    $user = User::factory()->create([
        'email_verified_at' => now(),
        '2fa_enabled' => false,
        'status' => null,
    ]);

    $response = $this->actingAs($user)->get('/');

    // validemail must not send the verified user to the verification flow. The
    // route may render or redirect elsewhere, but never to /i/verify-email.
    expect($response->headers->get('Location'))->not->toBe(url('/i/verify-email'));
    if ($response->isRedirect()) {
        expect($response->headers->get('Location'))->not->toContain('/i/verify-email');
    }
});

/*
|--------------------------------------------------------------------------
| R11.2 / R11.3 — EmailVerification model + /i/verify-email flow unchanged
|--------------------------------------------------------------------------
*/

it('keeps the EmailVerification model present', function () {
    // R11.2 — the custom EmailVerification model still exists (records unchanged).
    expect(class_exists(EmailVerification::class))->toBeTrue();
});

it('keeps /i/verify-email reachable for an authenticated unverified user', function () {
    // R11.3 — /i/verify-email is an excluded path: an unverified user reaching
    // it is NOT redirected away, and the endpoint responds without a server
    // error (functionally unchanged handling).
    config(['pixelfed.enforce_email_verification' => true]);

    $user = User::factory()->unverified()->create([
        '2fa_enabled' => false,
        'status' => null,
    ]);

    $response = $this->actingAs($user)->get('/i/verify-email');

    expect($response->getStatusCode())->toBeLessThan(500);
    // The excluded path must not bounce the user back to itself.
    if ($response->isRedirect()) {
        expect($response->headers->get('Location'))->not->toContain('/i/verify-email');
    }
});
