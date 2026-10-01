<?php

use App\Services\ConfigCacheService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Configuration Integration Tests
|--------------------------------------------------------------------------
|
| Verify that configuration loads correctly, environment variables
| are respected, and the config_cache helper works as expected.
|
*/

it('loads the application name from config', function () {
    expect(config('app.name'))->toBeString()->not->toBeEmpty();
});

it('loads the app URL from config', function () {
    expect(config('app.url'))->toBeString()->toContain('http');
});

it('loads the database connection from config', function () {
    expect(config('database.default'))->toBeString();
});

it('loads the cache driver from config', function () {
    expect(config('cache.default'))->toBeString();
});

it('loads pixelfed-specific config values', function () {
    expect(config('pixelfed.domain.app'))->toBeString();
});

it('respects the testing environment', function () {
    expect(app()->environment())->toBe('testing');
});

it('has the encryption key set', function () {
    expect(config('app.key'))->not->toBeNull()->not->toBeEmpty();
});

it('config_cache resolves an ENVCONFIG key from db/config when its env var is absent', function () {
    // instance.landing.show_directory is ENVCONFIG (env
    // INSTANCE_LANDING_SHOW_DIRECTORY), which is absent under the test env — so
    // config_cache() takes the DB-value-or-config-fallback path. Persisting via
    // put() + clearing the memoized entry makes the read deterministic without any
    // master switch (removed in the config-cache refactor).
    $key = 'instance.landing.show_directory';

    // The persisted DB row stringifies booleans, so compare loosely (truthy/falsy)
    // rather than strict boolean identity.
    Config::set($key, true);
    ConfigCacheService::put($key, true);
    Cache::forget(ConfigCacheService::CACHE_KEY.$key);
    expect((bool) config_cache($key))->toBeTrue();

    Config::set($key, false);
    ConfigCacheService::put($key, false);
    Cache::forget(ConfigCacheService::CACHE_KEY.$key);
    expect((bool) config_cache($key))->toBeFalse();
});

it('config_cache reads a persisted value from the cache', function () {
    // Seed the memoized cache entry under the real CACHE_KEY prefix and confirm
    // config_cache() serves it back for an ENVCONFIG key whose env var is absent
    // (instance.landing.show_directory / INSTANCE_LANDING_SHOW_DIRECTORY), so the
    // read takes the DB-value-or-config-fallback (cache-backed) path.
    $key = 'instance.landing.show_directory';

    Cache::put(ConfigCacheService::CACHE_KEY.$key, 'cached-value', 3600);

    $value = config_cache($key);

    expect($value)->toBe('cached-value');
});

it('loads auth configuration correctly', function () {
    expect(config('auth.defaults.guard'))->toBe('web');
    expect(config('auth.guards.api'))->toBeArray();
    expect(config('auth.password_timeout'))->toBeInt();
});

it('loads queue connection for testing', function () {
    expect(config('queue.default'))->toBe('sync');
});

it('loads session driver for testing', function () {
    expect(config('session.driver'))->toBe('array');
});
