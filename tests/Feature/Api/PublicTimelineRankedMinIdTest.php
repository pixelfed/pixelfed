<?php

use App\Services\PublicTimelineService;
use Illuminate\Support\Facades\Redis;

/*
|--------------------------------------------------------------------------
| PublicTimelineService::getRankedMinId must exclude the boundary id
|--------------------------------------------------------------------------
|
| min_id pagination is exclusive (id > min). The cached path used an
| inclusive Redis min bound, so on a partial up-page the boundary post was
| returned again as a duplicate at the tail. The bound must be exclusive.
|
*/

beforeEach(function () {
    Redis::del(PublicTimelineService::CACHE_KEY);
});

afterEach(function () {
    Redis::del(PublicTimelineService::CACHE_KEY);
});

it('excludes the boundary id on a partial up-page', function () {
    foreach ([100, 200, 300] as $id) {
        PublicTimelineService::add($id);
    }

    // Only ids strictly greater than 200 should come back (i.e. [300]).
    $ids = PublicTimelineService::getRankedMinId(200, 10);

    expect($ids)->not->toContain('200');
    expect($ids)->not->toContain(200);
    expect(collect($ids)->map(fn ($i) => (int) $i)->all())->toBe([300]);
});

it('returns all strictly-newer ids without the boundary on a full page', function () {
    foreach ([100, 200, 300, 400, 500] as $id) {
        PublicTimelineService::add($id);
    }

    // limit 2, min_id 200: strictly-newer descending, capped at 2 -> [500, 400].
    $ids = collect(PublicTimelineService::getRankedMinId(200, 2))->map(fn ($i) => (int) $i)->all();

    expect($ids)->toBe([500, 400]);
    expect($ids)->not->toContain(200);
});
