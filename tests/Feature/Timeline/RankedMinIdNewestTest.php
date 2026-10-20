<?php

use App\Services\NetworkTimelineService;
use App\Services\PublicTimelineService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Redis;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Redis::del(PublicTimelineService::CACHE_KEY);
    Redis::del(NetworkTimelineService::CACHE_KEY);
});

afterEach(function () {
    Redis::del(PublicTimelineService::CACHE_KEY);
    Redis::del(NetworkTimelineService::CACHE_KEY);
});

// Ids are Pixelfed snowflakes. Map a small ordinal to a snowflake spaced far
// enough apart that each one lands on a distinct, strictly-increasing score.
function newestSnowflakeId(int $ordinal): int
{
    return 1_000_000_000_000_000_000 + ($ordinal * 8_388_608_000);
}

it('returns the newest ids above the cursor', function () {
    $ids = collect([1, 2, 3, 4, 5])->mapWithKeys(fn ($n) => [$n => newestSnowflakeId($n)]);

    foreach ($ids as $id) {
        PublicTimelineService::add($id);
    }

    // min_id returns the newest entries above the cursor, newest-first.
    $window = collect(PublicTimelineService::getRankedMinId($ids[1], 2))
        ->map(fn ($id) => (int) $id)
        ->all();

    expect($window)->toBe([$ids[5], $ids[4]]);
});

it('returns the newest page for a low sentinel cursor', function () {
    $ids = collect([1, 2, 3, 4, 5])->mapWithKeys(fn ($n) => [$n => newestSnowflakeId($n)]);

    foreach ($ids as $id) {
        NetworkTimelineService::add($id);
    }

    // Clients send min_id=1 to mean "from the start, newest first". The newest
    // ids must come back, not the oldest.
    $window = collect(NetworkTimelineService::getRankedMinId(1, 3))
        ->map(fn ($id) => (int) $id)
        ->all();

    expect($window)->toBe([$ids[5], $ids[4], $ids[3]]);
});
