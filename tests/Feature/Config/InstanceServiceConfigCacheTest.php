<?php

use App\Models\ConfigCache as ConfigCacheModel;
use App\Services\ConfigCacheService;
use App\Services\InstanceService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

uses(LazilyRefreshDatabase::class);

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

test('totalLocalStatuses() returns the config_cache DB-row value unconditionally', function () {
    ConfigCacheService::put(ISVC_TOTAL_POSTS_KEY, 4242);
    isvcForget();

    expect((string) InstanceService::totalLocalStatuses())->toBe('4242');
});

test('totalLocalStatuses() falls back to the config value when no DB row exists', function () {
    // With no row, the ADMINONLY read path returns the config-file fallback.
    ConfigCacheModel::where('k', ISVC_TOTAL_POSTS_KEY)->delete();
    Config::set(ISVC_TOTAL_POSTS_KEY, 99);
    isvcForget();

    expect((string) InstanceService::totalLocalStatuses())->toBe('99');
});
