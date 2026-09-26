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

it('does not skip the first older post when max_id is absent (PublicTimeline)', function () {
    // 400 is deleted / absent; paginating from 400 must return [300,200,100].
    foreach ([100, 200, 300, 500] as $id) {
        PublicTimelineService::add($id);
    }

    expect(ints(PublicTimelineService::getRankedMaxId(400, 3)))->toBe([300, 200, 100]);
});

it('excludes the boundary id when max_id is present (PublicTimeline)', function () {
    foreach ([100, 200, 300, 400, 500] as $id) {
        PublicTimelineService::add($id);
    }

    // Exclusive: 400 itself must not be returned.
    $ids = ints(PublicTimelineService::getRankedMaxId(400, 10));
    expect($ids)->toBe([300, 200, 100]);
    expect($ids)->not->toContain(400);
});

it('does not skip the first older post when max_id is absent (NetworkTimeline)', function () {
    foreach ([100, 200, 300, 500] as $id) {
        NetworkTimelineService::add($id);
    }

    expect(ints(NetworkTimelineService::getRankedMaxId(400, 3)))->toBe([300, 200, 100]);
});

it('does not skip the first older post when max_id is absent (HomeTimeline)', function () {
    foreach ([100, 200, 300, 500] as $id) {
        HomeTimelineService::add(1, $id);
    }

    expect(ints(HomeTimelineService::getRankedMaxId(1, 400, 3)))->toBe([300, 200, 100]);
});

it('returns the full requested limit on HomeTimeline (no off-by-one)', function () {
    foreach ([100, 200, 300, 400, 500, 600] as $id) {
        HomeTimelineService::add(1, $id);
    }

    // limit 3 from an absent 550 boundary must return 3 ids, not 2.
    expect(ints(HomeTimelineService::getRankedMaxId(1, 550, 3)))->toBe([500, 400, 300]);
});

it('does not skip the first older post when max_id is absent (GroupFeed)', function () {
    foreach ([100, 200, 300, 500] as $id) {
        GroupFeedService::add(1, $id);
    }

    expect(ints(GroupFeedService::getRankedMaxId(1, 400, 3)))->toBe([300, 200, 100]);
});
