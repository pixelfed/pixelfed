<?php

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Cache;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Fortify auth GET pages — view resolution
|--------------------------------------------------------------------------
|
| Task 4.1 pointed Fortify's view callbacks at the existing Pixelfed Blade
| templates. This file verifies that the four guest-facing GET routes each
| render the corresponding legacy view, and that the reset-password view
| receives the `token` (from the route) and `email` (from the query string).
|
| During this additive migration phase BOTH Auth::routes() and the Fortify
| routes are registered, so their route NAMES collide (login, register,
| password.request, password.reset). The legacy Auth::routes() are scoped to
| the `localhost` domain, while the Fortify routes live at the application
| root; requests here are made against the configured APP_DOMAIN test host, so
| the URL PATHS resolve to the Fortify handlers. Tests therefore hit the PATHS
| directly rather than resolving by (ambiguous) route name.
|
| Covers Requirements 2.1, 2.4, 2.5, 2.6, 2.7.
|
*/

beforeEach(function () {
    // The suite defaults CACHE_STORE to redis, which is not guaranteed to be
    // running in the test environment. The view callbacks (and the bouncer
    // guard) read config_cache(), which is cache-backed, so pin the cache to
    // the in-memory array store and start from a clean slate. This mirrors
    // tests/Feature/FortifyAuthenticateClosureTest.php.
    config(['cache.default' => 'array']);
    Cache::store('array')->flush();

    // These GET pages are guest routes; the throttle middleware resolves a
    // redis-backed limiter store, so skip it to keep redis out of the picture.
    $this->withoutMiddleware(ThrottleRequests::class);

    // The bouncer check can run on these routes; keep it disabled so the pages
    // are reachable without seeding a CIDR cache.
    config([
        'pixelfed.bouncer.cloud_ips.ban_logins' => false,
        'pixelfed.bouncer.cloud_ips.ban_signups' => false,
    ]);

    // Ensure registration is open so the register GET page is reachable.
    config(['pixelfed.open_registration' => true]);
});

it('renders the auth.login view for GET /login', function () {
    // Requirement 2.1, 2.4 — the login GET route returns the existing view.
    $this->get('/login')
        ->assertOk()
        ->assertViewIs('auth.login');
});

it('renders the auth.register view for GET /register', function () {
    // Requirement 2.1, 2.5 — the register GET route returns the existing view.
    $this->get('/register')
        ->assertOk()
        ->assertViewIs('auth.register');
});

it('renders the auth.passwords.email view for GET /forgot-password', function () {
    // Requirement 2.1, 2.6 — the forgot-password GET route returns the
    // existing password-reset-link request view.
    $this->get('/forgot-password')
        ->assertOk()
        ->assertViewIs('auth.passwords.email');
});

it('renders auth.passwords.reset with token and email for GET /reset-password/{token}', function () {
    // Requirement 2.1, 2.7 — the reset-password GET route returns the existing
    // view populated with the token (from the route) and email (from the query
    // string).
    $token = 'test-reset-token-abc123';
    $email = 'foo@bar.com';

    $this->get('/reset-password/'.$token.'?email='.urlencode($email))
        ->assertOk()
        ->assertViewIs('auth.passwords.reset')
        ->assertViewHas('token', $token)
        ->assertViewHas('email', $email);
});
