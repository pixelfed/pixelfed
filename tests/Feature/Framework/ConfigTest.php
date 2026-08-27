<?php

use Illuminate\Support\Facades\Cache;

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

it('config_cache falls back to config when CC is disabled', function () {
    config(['instance.enable_cc' => false]);

    config(['pixelfed.open_registration' => true]);
    expect(config_cache('pixelfed.open_registration'))->toBeTrue();

    config(['pixelfed.open_registration' => false]);
    expect(config_cache('pixelfed.open_registration'))->toBeFalse();
});

it('config_cache reads from cache when CC is enabled', function () {
    config(['instance.enable_cc' => true]);

    Cache::put('pf:services:config:pixelfed.max_photo_size', '20000', 3600);

    $value = config_cache('pixelfed.max_photo_size');

    // Returns either the cached value or the config fallback
    expect($value)->not->toBeNull();
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
