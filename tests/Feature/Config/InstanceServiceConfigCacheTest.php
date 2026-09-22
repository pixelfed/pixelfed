<?php

use App\Models\ConfigCache as ConfigCacheModel;
use App\Services\ConfigCacheService;
use App\Services\InstanceService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| InstanceService::totalLocalStatuses() unconditional config_cache read (Task 17)
|--------------------------------------------------------------------------
|
| Requirement 11.4: the enable_cc branch was removed, so totalLocalStatuses()
| now reads config_cache('instance.stats.total_local_posts') unconditionally.
|
| instance.stats.total_local_posts is ADMINONLY (no env var), so put() writes a
| row and config_cache resolves DB-row-first, else the config-file fallback.
| config_cache DB rows stringify booleans, so we compare loosely (string cast).
|
| Validates: Requirements 11.4
|
*/

const ISVC_TOTAL_POSTS_KEY = 'instance.stats.total_local_posts';

function isvcForget(): void
{
    Cache::forget(ConfigCacheService::CACHE_KEY.ISVC_TOTAL_POSTS_KEY);
}

beforeEach(function () {
    $this->originalConfig = Config::get(ISVC_TOTAL_POSTS_KEY);
    isvcForget();
});

afterEach(function () {
    Config::set(ISVC_TOTAL_POSTS_KEY, $this->originalConfig);
    isvcForget();
});

test('totalLocalStatuses() returns the config_cache DB-row value unconditionally (11.4)', function () {
    ConfigCacheService::put(ISVC_TOTAL_POSTS_KEY, 4242);
    isvcForget();

    expect((string) InstanceService::totalLocalStatuses())->toBe('4242');
});

test('totalLocalStatuses() falls back to the config value when no DB row exists (11.4)', function () {
    // Ensure there is genuinely no config_cache row (the app may have lazily
    // seeded one at boot), then drive the value through config() only. With no
    // row, the ADMINONLY read path returns the config-file fallback.
    ConfigCacheModel::where('k', ISVC_TOTAL_POSTS_KEY)->delete();
    Config::set(ISVC_TOTAL_POSTS_KEY, 99);
    isvcForget();

    expect((string) InstanceService::totalLocalStatuses())->toBe('99');
});
