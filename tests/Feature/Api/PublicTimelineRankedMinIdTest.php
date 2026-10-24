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

// Ids are Pixelfed snowflakes. Map a small ordinal to a snowflake spaced far
// enough apart that each one lands on a distinct, strictly-increasing score.
function minIdSnowflake(int $ordinal): int
{
    return 1_000_000_000_000_000_000 + ($ordinal * 8_388_608_000);
}

it('excludes the boundary id on a partial up-page', function () {
    $ids = collect([1, 2, 3])->mapWithKeys(fn ($n) => [$n => minIdSnowflake($n)]);

    foreach ($ids as $id) {
        PublicTimelineService::add($id);
    }

    // Only ids strictly greater than the second entry should come back.
    $result = PublicTimelineService::getRankedMinId($ids[2], 10);

    expect($result)->not->toContain((string) $ids[2]);
    expect($result)->not->toContain($ids[2]);
    expect(collect($result)->map(fn ($i) => (int) $i)->all())->toBe([$ids[3]]);
});

it('returns all strictly-newer ids without the boundary on a full page', function () {
    $ids = collect([1, 2, 3, 4, 5])->mapWithKeys(fn ($n) => [$n => minIdSnowflake($n)]);

    foreach ($ids as $id) {
        PublicTimelineService::add($id);
    }

    // limit 2 from the second entry: strictly-newer descending, capped at 2.
    $result = collect(PublicTimelineService::getRankedMinId($ids[2], 2))->map(fn ($i) => (int) $i)->all();

    expect($result)->toBe([$ids[5], $ids[4]]);
    expect($result)->not->toContain($ids[2]);
});
