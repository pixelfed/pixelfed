<?php

use App\Jobs\HomeFeedPipeline\FeedInsertPipeline;
use App\Models\Follower;
use App\Models\Status;
use App\Models\User;
use App\Services\HomeTimelineService;
use App\Services\StatusService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Queue::fake();
});

afterEach(function () {
    Redis::flushdb();
});

it('does not create a home feed for a follower who has never loaded one', function () {
    $author = User::factory()->create();
    $author->refresh();

    $idle = User::factory()->create();
    $idle->refresh();
    Follower::create(['profile_id' => $idle->profile_id, 'following_id' => $author->profile_id]);

    $active = User::factory()->create();
    $active->refresh();
    Follower::create(['profile_id' => $active->profile_id, 'following_id' => $author->profile_id]);
    // The active follower already has a warmed feed.
    HomeTimelineService::add($active->profile_id, 1);

    $status = Status::factory()->create([
        'profile_id' => $author->profile_id,
        'type' => 'photo', 'scope' => 'public', 'visibility' => 'public',
        'in_reply_to_id' => null, 'reblog_of_id' => null,
    ]);
    StatusService::get($status->id, false);

    (new FeedInsertPipeline($status->id, $author->profile_id))->handle();

    expect(HomeTimelineService::cacheKeyExists($idle->profile_id))->toBeFalse()
        ->and(collect(HomeTimelineService::get($active->profile_id, 0, -1))->map(fn ($v) => (int) $v)->all())
        ->toContain((int) $status->id);
});

it('applies a ttl to a warmed feed and refreshes it on read', function () {
    $user = User::factory()->create();
    $user->refresh();
    $pid = $user->profile_id;

    HomeTimelineService::add($pid, 100);

    $ttl = Redis::ttl(HomeTimelineService::CACHE_KEY.$pid);
    expect($ttl)->toBeGreaterThan(0)
        ->and($ttl)->toBeLessThanOrEqual(HomeTimelineService::CACHE_TTL);

    // Simulate the key aging, then confirm a read bumps the ttl back up.
    Redis::expire(HomeTimelineService::CACHE_KEY.$pid, 100);
    HomeTimelineService::get($pid, 0, 10);

    expect(Redis::ttl(HomeTimelineService::CACHE_KEY.$pid))->toBeGreaterThan(100);
});
