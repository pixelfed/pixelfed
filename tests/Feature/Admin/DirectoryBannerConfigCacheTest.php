<?php

use App\Http\Controllers\AdminController;
use App\Models\ConfigCache;
use App\Services\ConfigCacheService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Directory banner reset stores JSON, not the ConfigCache model
|--------------------------------------------------------------------------
|
| directoryDeleteBannerImage passed the ConfigCache model to
| ConfigCacheService::put('pixelfed.directory', ...), which serialized the
| whole row (id/k/v/metadata/...) into the value column and cached the model
| object for 12h, breaking callers that expect the directory JSON string.
|
*/

it('stores the directory JSON string, not the wrapped model row', function () {
    ConfigCache::create([
        'k' => 'app.banner_image',
        'v' => 'https://example.test/storage/headers/custom.jpg',
    ]);
    ConfigCache::create([
        'k' => 'pixelfed.directory',
        'v' => json_encode([
            'banner_image' => 'public/headers/custom.jpg',
            'admin' => ['name' => 'Alice'],
        ]),
    ]);

    (new AdminController)->directoryDeleteBannerImage(new Request);

    // The persisted row must hold the directory JSON, not the wrapped model row.
    $stored = ConfigCache::whereK('pixelfed.directory')->first()->v;
    expect($stored)->toBeString();
    $storedDecoded = json_decode($stored, true);
    expect($storedDecoded)->toBeArray();
    expect($storedDecoded)->not->toHaveKeys(['id', 'k', 'metadata']);
    expect($storedDecoded['banner_image'])->toBe('public/headers/default.jpg');

    // The 12h cache must hold the directory JSON string, not a ConfigCache model.
    $cached = Cache::get(ConfigCacheService::CACHE_KEY.'pixelfed.directory');
    expect($cached)->toBeString();
    expect(json_decode($cached, true))->not->toHaveKeys(['id', 'k', 'metadata']);
});
