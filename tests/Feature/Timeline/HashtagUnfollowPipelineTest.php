<?php

use App\Jobs\HomeFeedPipeline\HashtagUnfollowPipeline;
use App\Models\Follower;
use App\Models\Hashtag;
use App\Models\Status;
use App\Models\StatusHashtag;
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

function unfollowHomeKey(int $pid): string
{
    return HomeTimelineService::CACHE_KEY.$pid;
}

function makeTaggedStatus(int $authorPid, array $tagNames): Status
{
    $status = Status::factory()->create([
        'profile_id' => $authorPid,
        'type' => 'photo',
        'scope' => 'public',
        'visibility' => 'public',
        'in_reply_to_id' => null,
        'reblog_of_id' => null,
    ]);

    foreach ($tagNames as $name) {
        $hashtag = Hashtag::firstOrCreate(
            ['slug' => strtolower($name)],
            ['name' => $name]
        );
        StatusHashtag::create([
            'status_id' => $status->id,
            'hashtag_id' => $hashtag->id,
            'profile_id' => $authorPid,
            'status_visibility' => 'public',
        ]);
    }

    StatusService::get($status->id, false);

    return $status;
}

function cachedIds(int $pid): array
{
    return collect(HomeTimelineService::get($pid, 0, -1))
        ->map(fn ($id) => (int) $id)
        ->all();
}

afterEach(function () {
    if (isset($this->viewerPid)) {
        Redis::del(unfollowHomeKey($this->viewerPid));
    }
});

it('removes only unfollowed-tag posts from accounts the viewer does not follow', function () {
    $viewer = User::factory()->create();
    $viewer->refresh();
    $pid = $viewer->profile_id;
    $this->viewerPid = $pid;

    $followed = User::factory()->create();
    $followed->refresh();
    Follower::create([
        'profile_id' => $pid,
        'following_id' => $followed->profile_id,
    ]);

    $stranger = User::factory()->create();
    $stranger->refresh();

    // Followed account, no tags: must stay.
    $followedUntagged = makeTaggedStatus($followed->profile_id, []);
    // Followed account, carries the unfollowed tag: must stay (followed wins).
    $followedTagged = makeTaggedStatus($followed->profile_id, ['sunset']);
    // Stranger, carries only a different tag: must stay.
    $strangerOtherTag = makeTaggedStatus($stranger->profile_id, ['coffee']);
    // Stranger, carries the unfollowed tag: must be removed.
    $strangerUnfollowed = makeTaggedStatus($stranger->profile_id, ['sunset']);

    Redis::del(unfollowHomeKey($pid));
    foreach ([$followedUntagged, $followedTagged, $strangerOtherTag, $strangerUnfollowed] as $s) {
        HomeTimelineService::add($pid, $s->id);
    }

    $tag = Hashtag::whereSlug('sunset')->first();

    (new HashtagUnfollowPipeline($tag->id, $pid, 'sunset'))->handle();

    $remaining = cachedIds($pid);

    expect($remaining)->toContain((int) $followedUntagged->id)
        ->and($remaining)->toContain((int) $followedTagged->id)
        ->and($remaining)->toContain((int) $strangerOtherTag->id)
        ->and($remaining)->not->toContain((int) $strangerUnfollowed->id);
});

it('prunes ids whose status can no longer be resolved', function () {
    $viewer = User::factory()->create();
    $viewer->refresh();
    $pid = $viewer->profile_id;
    $this->viewerPid = $pid;

    Redis::del(unfollowHomeKey($pid));
    HomeTimelineService::add($pid, 999999999);

    $tag = Hashtag::firstOrCreate(['slug' => 'sunset'], ['name' => 'sunset']);

    (new HashtagUnfollowPipeline($tag->id, $pid, 'sunset'))->handle();

    expect(cachedIds($pid))->not->toContain(999999999);
});
