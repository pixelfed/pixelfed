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

it('returns the newest ids above the cursor', function () {
    foreach ([10, 20, 30, 40, 50] as $id) {
        PublicTimelineService::add($id);
    }

    // min_id returns the newest entries above the cursor, newest-first.
    $window = collect(PublicTimelineService::getRankedMinId(10, 2))
        ->map(fn ($id) => (int) $id)
        ->all();

    expect($window)->toBe([50, 40]);
});

it('returns the newest page for a low sentinel cursor', function () {
    foreach ([10, 20, 30, 40, 50] as $id) {
        NetworkTimelineService::add($id);
    }

    // Clients send min_id=1 to mean "from the start, newest first". The newest
    // ids must come back, not the oldest.
    $window = collect(NetworkTimelineService::getRankedMinId(1, 3))
        ->map(fn ($id) => (int) $id)
        ->all();

    expect($window)->toBe([50, 40, 30]);
});
