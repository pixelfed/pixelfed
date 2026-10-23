<?php

use App\Services\ConfigCacheService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Protected config_cache keys must never sit in cache as plaintext
|--------------------------------------------------------------------------
|
| put() and get() encrypted the DB column for PROTECTED_KEYS but stored the
| decrypted plaintext in the Laravel cache, so secrets (s3/spaces keys,
| captcha secrets) leaked into whatever cache store backs config_cache.
| The raw cache entry must hold ciphertext; only callers get plaintext back.
|
*/

beforeEach(function () {
    config(['instance.enable_cc' => true]);
});

it('does not store protected secrets as plaintext in cache on put', function () {
    $key = 'filesystems.disks.s3.secret';

    ConfigCacheService::put($key, 'plainsecret');

    $raw = Cache::get(ConfigCacheService::CACHE_KEY.$key);

    expect($raw)->not->toBeNull();
    expect($raw)->not->toBe('plainsecret');
    // The cached blob must decrypt back to the original secret.
    expect(decrypt($raw))->toBe('plainsecret');

    // Callers still receive the plaintext.
    expect(ConfigCacheService::get($key))->toBe('plainsecret');
});

it('does not store protected secrets as plaintext in cache on get miss', function () {
    $key = 'filesystems.disks.s3.key';

    ConfigCacheService::put($key, 'plainkey');

    // Drop the cache entry to force get() through its miss path.
    Cache::forget(ConfigCacheService::CACHE_KEY.$key);

    $returned = ConfigCacheService::get($key);
    expect($returned)->toBe('plainkey');

    $raw = Cache::get(ConfigCacheService::CACHE_KEY.$key);
    expect($raw)->not->toBeNull();
    expect($raw)->not->toBe('plainkey');
    expect(decrypt($raw))->toBe('plainkey');
});

it('stores non-protected keys as plaintext and returns them intact', function () {
    $key = 'app.name';

    ConfigCacheService::put($key, 'MyInstance');

    $raw = Cache::get(ConfigCacheService::CACHE_KEY.$key);
    expect($raw)->toBe('MyInstance');
    expect(ConfigCacheService::get($key))->toBe('MyInstance');
});
