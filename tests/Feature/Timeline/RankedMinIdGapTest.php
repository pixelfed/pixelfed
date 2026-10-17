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

it('returns the ids immediately newer than the cursor, not the newest', function () {
    foreach ([10, 20, 30, 40, 50] as $id) {
        PublicTimelineService::add($id);
    }

    // Cursor 10, limit 2: a forward-paginating client must receive the two ids
    // directly above the cursor (20, 30), newest-first, so it can fill the gap
    // rather than skipping to the newest ids (40, 50).
    $window = collect(PublicTimelineService::getRankedMinId(10, 2))
        ->map(fn ($id) => (int) $id)
        ->all();

    expect($window)->toBe([30, 20]);
});

it('fills forward across repeated min_id requests', function () {
    foreach ([10, 20, 30, 40, 50] as $id) {
        NetworkTimelineService::add($id);
    }

    $first = collect(NetworkTimelineService::getRankedMinId(10, 2))->map(fn ($id) => (int) $id)->all();
    expect($first)->toBe([30, 20]);

    // Advance the cursor to the newest id seen (30) and page forward again.
    $second = collect(NetworkTimelineService::getRankedMinId(max($first), 2))->map(fn ($id) => (int) $id)->all();
    expect($second)->toBe([50, 40]);
});
