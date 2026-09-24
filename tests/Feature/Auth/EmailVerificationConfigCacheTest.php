<?php

use App\Http\Controllers\Auth\LoginController;
use App\Models\User;
use App\Services\ConfigCacheService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Email-verification auth gate honours runtime admin toggles
|--------------------------------------------------------------------------
|
| pixelfed.enforce_email_verification is an admin-toggled, config_cache-backed
| key. The login/register gates read it through the boot-time config() helper,
| so an admin enabling verification at runtime was ignored (an unverified user
| logged straight in). The gate must read config_cache().
|
*/

beforeEach(function () {
    // config_cache() only diverges from config() when the DB-backed cache is
    // enabled; CI runs with it off.
    config(['instance.enable_cc' => true]);
});

/**
 * Invoke the protected requiresEmailVerification() gate.
 */
function callRequiresEmailVerification(User $user): bool
{
    $controller = new LoginController;
    $method = new ReflectionMethod($controller, 'requiresEmailVerification');
    $method->setAccessible(true);

    return $method->invoke($controller, $user);
}

/**
 * Set the admin-toggled runtime value the way ConfigCacheService::get reads it,
 * while leaving the boot-time config() value at $bootValue so the two diverge.
 */
function setEnforceVerification(bool $runtimeValue, bool $bootValue): void
{
    config(['pixelfed.enforce_email_verification' => $bootValue]);
    // Forget first: a prior config_cache() read may have memoized the closure
    // result for this key in the shared cache store.
    Cache::forget(ConfigCacheService::CACHE_KEY.'pixelfed.enforce_email_verification');
    Cache::put(
        ConfigCacheService::CACHE_KEY.'pixelfed.enforce_email_verification',
        $runtimeValue,
        now()->addHour()
    );
}

it('enforces verification for an unverified user when an admin enabled it at runtime', function () {
    $user = User::factory()->create(['email_verified_at' => null]);

    // Booted with verification off, admin turned it on: config() is stale false,
    // config_cache() is the fresh true. Set last so nothing re-primes the cache.
    setEnforceVerification(runtimeValue: true, bootValue: false);

    expect(callRequiresEmailVerification($user))->toBeTrue();
});

it('does not enforce verification once an admin disabled it at runtime', function () {
    $user = User::factory()->create(['email_verified_at' => null]);

    // Booted with verification on, admin turned it off: config() is stale true,
    // config_cache() is the fresh false.
    setEnforceVerification(runtimeValue: false, bootValue: true);

    expect(callRequiresEmailVerification($user))->toBeFalse();
});

it('never enforces verification for an already-verified user', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    setEnforceVerification(runtimeValue: true, bootValue: true);

    expect(callRequiresEmailVerification($user))->toBeFalse();
});
