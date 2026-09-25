<?php

use App\Services\ConfigCacheService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| In-app registration rate limit must honor admin-saved config
|--------------------------------------------------------------------------
|
| inAppRegistration read the rate-limit attempts/decay via config() (env
| defaults) instead of config_cache() (admin-editable, DB-backed), so admin
| panel changes were silently ignored by the live limiter.
|
*/

beforeEach(function () {
    config(['instance.enable_cc' => true]);
});

it('reads admin-saved rate-limit attempts via config_cache', function () {
    ConfigCacheService::put('pixelfed.app_registration_rate_limit_attempts', 10);

    // The value the fixed limiter now reads.
    expect((int) config_cache('pixelfed.app_registration_rate_limit_attempts'))->toBe(10);

    // The env/default path the buggy code used diverges from admin intent.
    expect((int) config('pixelfed.app_registration_rate_limit_attempts', 3))->toBe(3);
});

it('reads admin-saved rate-limit decay via config_cache', function () {
    ConfigCacheService::put('pixelfed.app_registration_rate_limit_decay', 3600);

    expect((int) config_cache('pixelfed.app_registration_rate_limit_decay'))->toBe(3600);
});

it('honors a tightened attempts cap via config_cache', function () {
    ConfigCacheService::put('pixelfed.app_registration_rate_limit_attempts', 1);

    expect((int) config_cache('pixelfed.app_registration_rate_limit_attempts'))->toBe(1);
});

// End-to-end: the limiter enforces the admin-configured attempts cap, not the
// env default. The limiter runs before validation, so an invalid body still
// consumes an attempt.
it('enforces the admin-configured attempts cap on the iar endpoint', function () {
    config(['pixelfed.open_registration' => true]);
    config(['pixelfed.allow_app_registration' => true]);

    // Admin tightens the cap to a single attempt (env default is 3).
    ConfigCacheService::put('pixelfed.app_registration_rate_limit_attempts', 1);
    ConfigCacheService::put('pixelfed.app_registration_rate_limit_decay', 3600);

    RateLimiter::clear('pf:apiv1.1:iar:127.0.0.1');

    $headers = ['X-PIXELFED-APP' => '1'];

    // 1st attempt: passes the limiter, fails validation (422).
    $this->postJson('/api/v1.1/auth/iar', [], $headers)->assertStatus(422);

    // 2nd attempt: the admin cap of 1 is exhausted -> limiter blocks (400).
    // With the bug (config() default of 3) this would still be 422.
    $this->postJson('/api/v1.1/auth/iar', [], $headers)->assertStatus(400);
});
