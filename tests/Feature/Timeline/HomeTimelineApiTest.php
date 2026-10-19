<?php

use App\Jobs\HomeFeedPipeline\FeedWarmCachePipeline;
use App\Models\Follower;
use App\Models\Status;
use App\Models\User;
use App\Services\HomeTimelineService;
use App\Services\StatusService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Laravel\Passport\Passport;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| GET /api/v1/timelines/home
|--------------------------------------------------------------------------
|
| Exercises the cached home timeline read path: a warm-cache hit returns the
| seeded sorted-set ids newest-first, an empty set returns 200 and warms once
| (guarded so a second request does not warm again), and min_id / max_id
| windows land on the correct side of the cursor bounded by the limit.
|
*/

function homeColdBootKey(int $pid): string
{
    return 'pf:services:apiv1:home:cached:coldbootcheck:'.$pid;
}

function flushHome(int $pid): void
{
    Redis::del(HomeTimelineService::CACHE_KEY.$pid);
    Cache::forget(homeColdBootKey($pid));
}

/**
 * A public photo status with no reply parent survives every home-feed filter.
 */
function seedHomeStatus(int $authorPid): Status
{
    static $seq = 0;

    $status = Status::factory()->create([
        'profile_id' => $authorPid,
        'type' => 'photo',
        'scope' => 'public',
        'visibility' => 'public',
        'in_reply_to_id' => null,
        'reblog_of_id' => null,
        // Monotonic timestamps so chronological order matches creation order.
        'created_at' => now()->subMinutes(1000 - ($seq++)),
    ]);

    // Resolve the status into the cache so the endpoint can hydrate it.
    StatusService::getMastodon($status->id, false);

    return $status;
}

function makeFollowingViewer(int $authorCount): array
{
    $viewer = User::factory()->create();
    $viewer->refresh();

    $statuses = [];
    for ($i = 0; $i < $authorCount; $i++) {
        $author = User::factory()->create();
        $author->refresh();

        Follower::create([
            'profile_id' => $viewer->profile_id,
            'following_id' => $author->profile_id,
        ]);

        $statuses[] = seedHomeStatus($author->profile_id);
    }

    return [$viewer, $statuses];
}

it('serves the seeded home set newest-first on a warm hit', function () {
    [$viewer, $statuses] = makeFollowingViewer(4);
    $pid = $viewer->profile_id;
    flushHome($pid);

    foreach ($statuses as $status) {
        HomeTimelineService::add($pid, $status->id);
    }

    Passport::actingAs($viewer, ['read']);

    $res = $this->getJson('/api/v1/timelines/home?limit=40')
        ->assertOk()
        ->json();

    $returned = collect($res)->pluck('id')->map(fn ($id) => (int) $id)->all();

    $expected = collect($statuses)
        ->pluck('id')
        ->map(fn ($id) => (int) $id)
        ->sortDesc()
        ->values()
        ->all();

    expect($returned)->toBe($expected);

    flushHome($pid);
});

it('warms once on cold-start and does not warm again', function () {
    Bus::fake();

    $viewer = User::factory()->create();
    $viewer->refresh();
    $pid = $viewer->profile_id;
    flushHome($pid);

    Passport::actingAs($viewer, ['read']);

    $first = $this->getJson('/api/v1/timelines/home');
    $first->assertOk();
    expect($first->json())->toBe([]);

    Bus::assertDispatchedSync(FeedWarmCachePipeline::class, 1);

    // Second immediate request: still empty, and the coldBootKey guard must
    // stop a second warm from dispatching.
    $second = $this->getJson('/api/v1/timelines/home');
    $second->assertOk();
    expect($second->json())->toBe([]);

    Bus::assertDispatchedSync(FeedWarmCachePipeline::class, 1);

    flushHome($pid);
});

it('returns only ids older than the cursor for max_id, bounded by limit', function () {
    [$viewer, $statuses] = makeFollowingViewer(6);
    $pid = $viewer->profile_id;
    flushHome($pid);

    foreach ($statuses as $status) {
        HomeTimelineService::add($pid, $status->id);
    }

    $ordered = collect($statuses)->pluck('id')->map(fn ($id) => (int) $id)->sortDesc()->values();
    // Cursor sits in the middle of the range; expect strictly-older ids.
    $cursor = $ordered->get(2);
    $limit = 2;

    Passport::actingAs($viewer, ['read']);

    $res = $this->getJson('/api/v1/timelines/home?limit='.$limit.'&max_id='.$cursor)
        ->assertOk()
        ->json();

    $returned = collect($res)->pluck('id')->map(fn ($id) => (int) $id);

    expect($returned->count())->toBeLessThanOrEqual($limit);
    expect($returned->every(fn ($id) => $id < $cursor))->toBeTrue();

    $expectedWindow = $ordered->filter(fn ($id) => $id < $cursor)->take($limit)->values()->all();
    expect($returned->values()->all())->toBe($expectedWindow);

    flushHome($pid);
});

it('returns only ids newer than the cursor for min_id, bounded by limit', function () {
    [$viewer, $statuses] = makeFollowingViewer(6);
    $pid = $viewer->profile_id;
    flushHome($pid);

    foreach ($statuses as $status) {
        HomeTimelineService::add($pid, $status->id);
    }

    $ordered = collect($statuses)->pluck('id')->map(fn ($id) => (int) $id)->sortDesc()->values();
    // Cursor near the bottom so several newer ids remain.
    $cursor = $ordered->get(4);
    $limit = 2;

    Passport::actingAs($viewer, ['read']);

    $res = $this->getJson('/api/v1/timelines/home?limit='.$limit.'&min_id='.$cursor)
        ->assertOk()
        ->json();

    $returned = collect($res)->pluck('id')->map(fn ($id) => (int) $id);

    expect($returned->count())->toBeLessThanOrEqual($limit);
    expect($returned->every(fn ($id) => $id > $cursor))->toBeTrue();

    // min_id returns the newest strictly-newer ids, descending, capped at limit.
    $expectedWindow = $ordered->filter(fn ($id) => $id > $cursor)->take($limit)->values()->all();
    expect($returned->values()->all())->toBe($expectedWindow);

    flushHome($pid);
});
