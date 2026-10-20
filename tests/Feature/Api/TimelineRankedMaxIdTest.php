<?php

use App\Services\GroupFeedService;
use App\Services\HomeTimelineService;
use App\Services\NetworkTimelineService;
use App\Services\PublicTimelineService;
use Illuminate\Support\Facades\Redis;

/*
|--------------------------------------------------------------------------
| getRankedMaxId must use an exclusive upper bound (id < max_id)
|--------------------------------------------------------------------------
|
| The cached max_id path used an inclusive Redis bound plus a fixed offset
| of 1 to "skip" the boundary. When max_id was absent from the set (deleted
| post, or an id between posts) the offset dropped the first valid older
| post instead, opening a pagination gap. The bound must be exclusive with
| offset 0, matching id < max_id and the min_id sibling.
|
*/

beforeEach(function () {
    Redis::del(PublicTimelineService::CACHE_KEY);
    Redis::del(NetworkTimelineService::CACHE_KEY);
    Redis::del(HomeTimelineService::CACHE_KEY.'1');
    Redis::del(GroupFeedService::CACHE_KEY.'1');
});

afterEach(function () {
    Redis::del(PublicTimelineService::CACHE_KEY);
    Redis::del(NetworkTimelineService::CACHE_KEY);
    Redis::del(HomeTimelineService::CACHE_KEY.'1');
    Redis::del(GroupFeedService::CACHE_KEY.'1');
});

function ints($ids): array
{
    return collect($ids)->map(fn ($i) => (int) $i)->all();
}

// Ids are Pixelfed snowflakes. Map a small ordinal to a snowflake spaced far
// enough apart that each one lands on a distinct, strictly-increasing score.
function maxIdSnowflake(int $ordinal): int
{
    return 1_000_000_000_000_000_000 + ($ordinal * 83_886_080);
}

it('does not skip the first older post when max_id is absent (PublicTimeline)', function () {
    $s = fn ($n) => maxIdSnowflake($n);
    // The 400-boundary is deleted / absent; paginating from it must return 300,200,100.
    foreach ([100, 200, 300, 500] as $n) {
        PublicTimelineService::add($s($n));
    }

    expect(ints(PublicTimelineService::getRankedMaxId($s(400), 3)))->toBe([$s(300), $s(200), $s(100)]);
});

it('excludes the boundary id when max_id is present (PublicTimeline)', function () {
    $s = fn ($n) => maxIdSnowflake($n);
    foreach ([100, 200, 300, 400, 500] as $n) {
        PublicTimelineService::add($s($n));
    }

    // Exclusive: the boundary itself must not be returned.
    $ids = ints(PublicTimelineService::getRankedMaxId($s(400), 10));
    expect($ids)->toBe([$s(300), $s(200), $s(100)]);
    expect($ids)->not->toContain($s(400));
});

it('does not skip the first older post when max_id is absent (NetworkTimeline)', function () {
    $s = fn ($n) => maxIdSnowflake($n);
    foreach ([100, 200, 300, 500] as $n) {
        NetworkTimelineService::add($s($n));
    }

    expect(ints(NetworkTimelineService::getRankedMaxId($s(400), 3)))->toBe([$s(300), $s(200), $s(100)]);
});

it('does not skip the first older post when max_id is absent (HomeTimeline)', function () {
    $s = fn ($n) => maxIdSnowflake($n);
    foreach ([100, 200, 300, 500] as $n) {
        HomeTimelineService::add(1, $s($n));
    }

    expect(ints(HomeTimelineService::getRankedMaxId(1, $s(400), 3)))->toBe([$s(300), $s(200), $s(100)]);
});

it('returns the full requested limit on HomeTimeline (no off-by-one)', function () {
    $s = fn ($n) => maxIdSnowflake($n);
    foreach ([100, 200, 300, 400, 500, 600] as $n) {
        HomeTimelineService::add(1, $s($n));
    }

    // limit 3 from an absent 550-boundary must return 3 ids, not 2.
    expect(ints(HomeTimelineService::getRankedMaxId(1, $s(550), 3)))->toBe([$s(500), $s(400), $s(300)]);
});

it('does not skip the first older post when max_id is absent (GroupFeed)', function () {
    $s = fn ($n) => maxIdSnowflake($n);
    foreach ([100, 200, 300, 500] as $n) {
        GroupFeedService::add(1, $s($n));
    }

    expect(ints(GroupFeedService::getRankedMaxId(1, $s(400), 3)))->toBe([$s(300), $s(200), $s(100)]);
});
