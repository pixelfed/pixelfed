<?php

use App\Models\AccountLog;
use App\Models\User;
use Illuminate\Cache\RateLimiter;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Fortify authenticateUsing() closure — edge cases
|--------------------------------------------------------------------------
|
| Exercises the guardrails wired into Fortify::authenticateUsing() via the
| POST /login flow (the closure is only reachable through Fortify). The happy
| path and the plain wrong-password / unknown-email cases are already covered
| by tests/Feature/Auth/LoginTest.php; this file focuses on the closure's
| distinct decisions: input-size limits, deleted-status accounts, the bouncer
| IP ban, and the captcha-failure branch.
|
| Covers Requirements 3.4, 3.5, 3.6, 4.1, 4.2.
|
*/

beforeEach(function () {
    // The suite defaults CACHE_STORE to redis, which is not guaranteed to be
    // running in the test environment. Pin the cache to the in-memory array
    // store so the bouncer CIDR cache read does not require an external
    // dependency, then start from a clean slate.
    config(['cache.default' => 'array']);
    Cache::store('array')->flush();

    // These tests exercise the authenticateUsing() closure, not Fortify's
    // login rate limiter (verified separately for task 2.2). The throttle
    // middleware resolves a redis-backed limiter store, so skip it here to keep
    // the closure the single subject under test.
    $this->withoutMiddleware(ThrottleRequests::class);

    // Fortify's PrepareAuthenticatedSession clears the login limiter on a
    // successful login via the RateLimiter singleton, which was resolved with
    // the redis store at boot. Rebind it to the array store so the success path
    // does not reach for redis.
    $this->app->instance(
        RateLimiter::class,
        new RateLimiter(Cache::store('array'))
    );

    // Keep captcha off for every non-captcha case so its rules never interfere.
    config([
        'captcha.enabled' => false,
        'captcha.active.login' => false,
        'captcha.triggers.login.enabled' => false,
        'pixelfed.bouncer.cloud_ips.ban_logins' => false,
    ]);
});

it('returns null without authenticating when the password exceeds 255 characters', function () {
    // Requirement 3.5 — oversized password short-circuits before any lookup.
    $user = User::factory()->create([
        'password' => Hash::make('correct-password'),
    ]);

    $this->post('/login', [
        'email' => $user->email,
        'password' => str_repeat('a', 256),
    ]);

    $this->assertGuest();
});

it('returns null without authenticating when the email exceeds 255 characters', function () {
    // Requirement 3.5 — oversized email short-circuits before any lookup.
    $this->post('/login', [
        'email' => str_repeat('a', 250).'@example.com',
        'password' => 'correct-password',
    ]);

    $this->assertGuest();
});

it('returns null without authenticating when the email is empty', function () {
    // Requirement 3.5 — empty email is rejected without querying the users table.
    $this->post('/login', [
        'email' => '',
        'password' => 'correct-password',
    ]);

    $this->assertGuest();
});

it('returns null without authenticating when the password is empty', function () {
    // Requirement 3.5 — empty password is rejected without querying the users table.
    $user = User::factory()->create([
        'password' => Hash::make('correct-password'),
    ]);

    $this->post('/login', [
        'email' => $user->email,
        'password' => '',
    ]);

    $this->assertGuest();
});

it('refuses to authenticate a deleted-status user even with the correct password', function () {
    // Requirement 3.6 — a deleted account must never establish a session.
    $user = User::factory()->create([
        'password' => Hash::make('correct-password'),
        'status' => 'deleted',
    ]);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'correct-password',
    ]);

    $this->assertGuest();
    $this->assertDatabaseCount('account_logs', 0);
});

it('authenticates a valid, non-deleted user with the correct bcrypt password', function () {
    // Baseline for the deleted-status case: same credentials succeed when the
    // account is active (Requirements 3.1, 3.2).
    $user = User::factory()->create([
        'password' => Hash::make('correct-password'),
        'status' => null,
    ]);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'correct-password',
    ])->assertRedirect('/i/web');

    $this->assertAuthenticatedAs($user);
});

it('aborts 404 without incrementing login_attempts when the bouncer ban matches the request IP', function () {
    // Requirement 4.1 — bouncer ban aborts before the captcha/credential logic
    // and must not touch the login_attempts counter.
    config(['pixelfed.bouncer.cloud_ips.ban_logins' => true]);

    // BouncerService::checkIp matches the request IP against this cached CIDR
    // list; seed it with the test client IP so the ban path triggers without
    // stubbing the static service.
    Cache::forever(
        'pf:bouncer-service:check-ip:known-cloud-cidrs',
        ['127.0.0.0/8']
    );

    $user = User::factory()->create([
        'password' => Hash::make('correct-password'),
    ]);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'correct-password',
    ]);

    $response->assertNotFound();
    $this->assertGuest();
    expect(session('login_attempts'))->toBeNull();
});

it('increments login_attempts and fails auth without an AccountLog when captcha is required but missing', function () {
    // Requirement 4.2 — a captcha-required login that fails captcha increments
    // the counter, raises auth.failed, and writes no AccountLog.
    config([
        'captcha.enabled' => true,
        'captcha.active.login' => true,
    ]);

    $user = User::factory()->create([
        'password' => Hash::make('correct-password'),
    ]);

    // Correct credentials, but no h-captcha-response -> captcha branch fails
    // before the credential check ever runs.
    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'correct-password',
    ]);

    $response->assertRedirect();
    $response->assertSessionHasErrors('email');

    $this->assertGuest();
    expect(session('login_attempts'))->toBe(1);
    $this->assertDatabaseCount('account_logs', 0);
});
