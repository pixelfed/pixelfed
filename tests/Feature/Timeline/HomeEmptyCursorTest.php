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

afterEach(function () {
    Redis::flushdb();
});

it('returns 200 with no cold-boot when a populated feed has nothing newer than min_id', function () {
    Bus::fake();

    $viewer = User::factory()->create();
    $viewer->refresh();
    $pid = $viewer->profile_id;

    $author = User::factory()->create();
    $author->refresh();
    Follower::create(['profile_id' => $pid, 'following_id' => $author->profile_id]);

    $status = Status::factory()->create([
        'profile_id' => $author->profile_id,
        'type' => 'photo', 'scope' => 'public', 'visibility' => 'public',
        'in_reply_to_id' => null, 'reblog_of_id' => null,
    ]);
    StatusService::getMastodon($status->id, false);
    HomeTimelineService::add($pid, $status->id);

    Passport::actingAs($viewer, ['read']);

    // Nothing is newer than the newest id, so the cursor window is empty.
    $response = $this->getJson('/api/v1/timelines/home?min_id='.$status->id);

    $response->assertOk();
    expect($response->json())->toBe([]);

    // A populated-but-empty-window read must not trip cold-boot warming.
    Bus::assertNotDispatched(FeedWarmCachePipeline::class);
    expect(Cache::has('pf:services:apiv1:home:cached:coldbootcheck:'.$pid))->toBeFalse();
});

it('warms once on a genuinely cold feed and guards re-warming', function () {
    Bus::fake();

    $viewer = User::factory()->create();
    $viewer->refresh();
    $pid = $viewer->profile_id;

    Passport::actingAs($viewer, ['read']);

    $this->getJson('/api/v1/timelines/home')->assertOk();

    Bus::assertDispatchedSync(FeedWarmCachePipeline::class, 1);

    $ttl = Cache::has('pf:services:apiv1:home:cached:coldbootcheck:'.$pid);
    expect($ttl)->toBeTrue();
});
