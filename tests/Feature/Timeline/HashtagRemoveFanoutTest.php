<?php

use App\Jobs\HomeFeedPipeline\HashtagRemoveFanoutPipeline;
use App\Models\Status;
use App\Models\User;
use App\Services\HashtagFollowService;
use App\Services\HomeTimelineService;
use App\Services\StatusService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    // Keep the delete observer's own FeedRemovePipeline from running so this
    // test exercises only the hashtag removal job.
    Queue::fake();
});

afterEach(function () {
    Redis::flushdb();
});

it('unqueues a deleted status from hashtag followers', function () {
    $hid = 4242;

    $follower = User::factory()->create();
    $follower->refresh();
    $pid = $follower->profile_id;

    HashtagFollowService::add($hid, $pid);
    HashtagFollowService::setWarm($hid);

    $status = Status::factory()->create([
        'profile_id' => $follower->profile_id,
        'type' => 'photo',
        'scope' => 'public',
        'visibility' => 'public',
    ]);

    $sid = $status->id;
    HomeTimelineService::add($pid, $sid);

    // Mirror the delete path: the row is soft deleted and its cached payload
    // purged, so StatusService::get can no longer resolve it when the job runs.
    $status->delete();
    StatusService::del($sid, true);

    expect(collect(HomeTimelineService::get($pid, 0, -1))->map(fn ($v) => (int) $v)->all())
        ->toContain((int) $sid);

    (new HashtagRemoveFanoutPipeline($sid, $hid))->handle();

    expect(collect(HomeTimelineService::get($pid, 0, -1))->map(fn ($v) => (int) $v)->all())
        ->not->toContain((int) $sid);
});
