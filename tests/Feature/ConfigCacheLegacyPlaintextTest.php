<?php

use App\Models\ConfigCache;
use App\Services\ConfigCacheService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Legacy plaintext config_cache entries must self-heal on get
|--------------------------------------------------------------------------
|
| Before protected keys were encrypted, their cache entries were stored as
| plaintext. On a cache hit decrypt() throws DecryptException, which the
| broad outer catch swallowed, so get() returned config($key) and left the
| stale plaintext entry in cache for its full 12h TTL. get() must now forget
| the stale entry and fall through to the DB read + re-cache path.
|
*/

beforeEach(function () {
    config(['instance.enable_cc' => true]);
});

it('recovers from a legacy plaintext cache entry for a protected key', function () {
    $key = 'captcha.turnstile.secret';
    $adminValue = 'admin-turnstile-secret';

    $cc = new ConfigCache;
    $cc->k = $key;
    $cc->v = encrypt($adminValue);
    $cc->save();

    Cache::put(ConfigCacheService::CACHE_KEY.$key, 'plaintext-secret', now()->addHours(12));

    expect(ConfigCacheService::get($key))->toBe($adminValue);

    $raw = Cache::get(ConfigCacheService::CACHE_KEY.$key);
    expect($raw)->not->toBe('plaintext-secret');
    expect(decrypt($raw))->toBe($adminValue);
});

it('returns a properly encrypted protected cache entry unchanged', function () {
    $key = 'captcha.turnstile.secret';
    $adminValue = 'admin-turnstile-secret';

    Cache::put(ConfigCacheService::CACHE_KEY.$key, encrypt($adminValue), now()->addHours(12));

    expect(ConfigCacheService::get($key))->toBe($adminValue);
});

it('returns a non-protected cached value without decrypting', function () {
    $key = 'app.name';

    Cache::put(ConfigCacheService::CACHE_KEY.$key, 'MyInstance', now()->addHours(12));

    expect(ConfigCacheService::get($key))->toBe('MyInstance');
});
