<?php

use App\Http\Controllers\Auth\LoginController;
use App\Models\ConfigCache;
use App\Models\User;
use App\Services\ConfigCacheService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Env;

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
 *
 * The key is only admin-managed (config_cache diverges from config) when it is
 * NOT env-locked, so we clear the ENFORCE_EMAIL_VERIFICATION env var: with the
 * env unset the key is unlocked and get() honours the DB-backed value instead
 * of falling back to config().
 */
function setEnforceVerification(bool $runtimeValue, bool $bootValue): void
{
    // Unlock the key: an unset env var means .env is not authoritative, so the
    // admin/db value governs (ConfigCacheService::isLocked() returns false).
    putenv('ENFORCE_EMAIL_VERIFICATION');
    Env::getRepository()->clear('ENFORCE_EMAIL_VERIFICATION');

    config(['pixelfed.enforce_email_verification' => $bootValue]);

    // Persist the admin-set runtime value as a config_cache row and prime the
    // cache entry get() reads, so the gate sees it diverge from the boot value.
    $row = ConfigCache::firstOrNew(['k' => 'pixelfed.enforce_email_verification']);
    $row->v = $runtimeValue;
    $row->save();

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
