<?php

use App\Jobs\HomeFeedPipeline\FeedInsertPipeline;
use App\Models\Follower;
use App\Models\Status;
use App\Models\User;
use App\Services\HomeTimelineService;
use App\Services\StatusService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Home-cache TTL lifecycle
|--------------------------------------------------------------------------
|
| The home feed is a Redis sorted set that must expire so abandoned viewers
| release memory, yet every live touch has to push that expiry back toward the
| full window. These walks exercise the real lifecycle: a nonempty rebuild
| stamps a bounded TTL, and ranked reads plus live fan-out into an existing
| feed each lift an aged key back up without ever exceeding the ceiling. Fan-out
| still refuses to materialise a key for a viewer who never loaded a feed.
|
*/

beforeEach(function () {
    config(['exp.cached_home_timeline' => true]);
    Redis::flushdb();
    Cache::flush();
});

afterEach(function () {
    Redis::flushdb();
    Cache::flush();
});

/**
 * A plain eligible home status: public photo, not a reply or a boost, resolved
 * through StatusService so the timeline services can score it.
 */
function ttlHomeStatus(int $authorPid, array $overrides = []): Status
{
    $status = Status::factory()->create(array_merge([
        'profile_id' => $authorPid,
        'type' => 'photo',
        'scope' => 'public',
        'visibility' => 'public',
        'local' => true,
        'uri' => null,
        'in_reply_to_id' => null,
        'reblog_of_id' => null,
    ], $overrides));

    StatusService::get($status->id, false);

    return $status;
}

it('assigns a bounded ttl during a successful nonempty home warm', function () {
    $viewer = User::factory()->create();
    $viewer->refresh();
    $pid = $viewer->profile_id;

    $author = User::factory()->create();
    $author->refresh();
    Follower::create(['profile_id' => $pid, 'following_id' => $author->profile_id]);

    foreach (range(1, 3) as $i) {
        ttlHomeStatus($author->profile_id);
    }

    $result = HomeTimelineService::warmCache($pid, true, 100);

    $key = HomeTimelineService::CACHE_KEY.$pid;

    // The warm actually built a nonempty feed; TTL only matters on a key that
    // survived the rebuild.
    expect($result)->toBe(1);
    expect(HomeTimelineService::count($pid))->toBeGreaterThan(0);

    $ttl = Redis::ttl($key);

    // A persistent key reports -1 and a missing key reports -2; neither is
    // acceptable after a successful warm.
    expect($ttl)->not->toBe(-1);
    expect($ttl)->not->toBe(-2);

    // The warm stamps the full window; allow a small margin for the seconds
    // that elapse between the expire call and this read.
    expect($ttl)->toBeGreaterThanOrEqual(HomeTimelineService::CACHE_TTL - 10);
    expect($ttl)->toBeLessThanOrEqual(HomeTimelineService::CACHE_TTL);
});

it('refreshes an aged home ttl through ranked reads and existing-feed fanout', function () {
    $viewer = User::factory()->create();
    $viewer->refresh();
    $pid = $viewer->profile_id;

    $author = User::factory()->create();
    $author->refresh();
    Follower::create(['profile_id' => $pid, 'following_id' => $author->profile_id]);

    foreach (range(1, 4) as $i) {
        ttlHomeStatus($author->profile_id);
    }

    HomeTimelineService::warmCache($pid, true, 100);

    $key = HomeTimelineService::CACHE_KEY.$pid;
    $ttlCeiling = HomeTimelineService::CACHE_TTL;

    // A real member of the warmed feed, used as a ranked cursor so the read
    // resolves against live data rather than a fabricated score.
    $members = HomeTimelineService::get($pid, 0, -1);
    expect($members)->not->toBeEmpty();
    $cursor = $members[intdiv(count($members), 2)];

    $ageTo = 100;

    // Ranked max-id read on an aged key.
    Redis::expire($key, $ageTo);
    expect(Redis::ttl($key))->toBeLessThanOrEqual($ageTo);
    HomeTimelineService::getRankedMaxId($pid, $cursor, 5);
    $afterMax = Redis::ttl($key);
    expect($afterMax)->toBeGreaterThan($ageTo);
    expect($afterMax)->toBeLessThanOrEqual($ttlCeiling);

    // Ranked min-id read on an aged key.
    Redis::expire($key, $ageTo);
    expect(Redis::ttl($key))->toBeLessThanOrEqual($ageTo);
    HomeTimelineService::getRankedMinId($pid, $cursor, 5);
    $afterMin = Redis::ttl($key);
    expect($afterMin)->toBeGreaterThan($ageTo);
    expect($afterMin)->toBeLessThanOrEqual($ttlCeiling);

    // Live fan-out into the existing feed on an aged key.
    $fresh = ttlHomeStatus($author->profile_id);
    Redis::expire($key, $ageTo);
    expect(Redis::ttl($key))->toBeLessThanOrEqual($ageTo);
    (new FeedInsertPipeline($fresh->id, $author->profile_id))->handle();
    $afterFanout = Redis::ttl($key);
    expect($afterFanout)->toBeGreaterThan($ageTo);
    expect($afterFanout)->toBeLessThanOrEqual($ttlCeiling);

    // The fan-out that refreshed the existing viewer must have landed its post.
    expect(collect(HomeTimelineService::get($pid, 0, -1))->map(fn ($v) => (int) $v)->all())
        ->toContain((int) $fresh->id);

    // A second follower who never loaded a feed must not gain a key from the
    // same fan-out: refreshing an existing key never creates a missing one.
    $neverLoaded = User::factory()->create();
    $neverLoaded->refresh();
    Follower::create(['profile_id' => $neverLoaded->profile_id, 'following_id' => $author->profile_id]);

    $another = ttlHomeStatus($author->profile_id);
    (new FeedInsertPipeline($another->id, $author->profile_id))->handle();

    expect(HomeTimelineService::cacheKeyExists($neverLoaded->profile_id))->toBeFalse();
    expect(Redis::ttl(HomeTimelineService::CACHE_KEY.$neverLoaded->profile_id))->toBe(-2);
});
