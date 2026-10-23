<?php

use App\Jobs\HomeFeedPipeline\FeedInsertPipeline;
use App\Models\Follower;
use App\Models\Profile;
use App\Models\Status;
use App\Models\User;
use App\Models\UserFilter;
use App\Services\FollowerService;
use App\Services\HomeTimelineService;
use App\Services\StatusService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Redis;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Feed fanout must honor blocks against a reblogged post's original author
|--------------------------------------------------------------------------
|
| On a reblog, the fanout only filtered recipients who muted/blocked the
| SHARER, not the original author. So a viewer who blocked an account still
| saw that account's post when someone they follow boosted it. The fanout
| must skip recipients who filtered either the sharer or the original author.
|
*/

function blockProfile(int $userProfileId, int $blockedProfileId): void
{
    $filter = new UserFilter;
    $filter->user_id = $userProfileId;
    $filter->filterable_id = $blockedProfileId;
    $filter->filterable_type = Profile::class;
    $filter->filter_type = 'block';
    $filter->save();
}

function reblogStatus(User $original, User $sharer): Status
{
    $orig = Status::factory()->create([
        'profile_id' => $original->profile_id,
        'type' => 'photo',
        'scope' => 'public',
        'visibility' => 'public',
    ]);

    return Status::factory()->create([
        'profile_id' => $sharer->profile_id,
        'type' => 'share',
        'scope' => 'public',
        'visibility' => 'public',
        'reblog_of_id' => $orig->id,
    ]);
}

it('skips a follower who blocked the reblogged posts original author', function () {
    $original = User::factory()->create();
    $sharer = User::factory()->create();
    $viewer = User::factory()->create();
    foreach ([$original, $sharer, $viewer] as $u) {
        $u->refresh();
    }

    Redis::del(HomeTimelineService::CACHE_KEY.$viewer->profile_id);

    // viewer follows sharer (so viewer is a fanout recipient), and blocks original.
    Follower::create(['profile_id' => $viewer->profile_id, 'following_id' => $sharer->profile_id, 'local_profile' => true]);
    FollowerService::add($viewer->profile_id, $sharer->profile_id);
    blockProfile($viewer->profile_id, $original->profile_id);

    $boost = reblogStatus($original, $sharer);
    StatusService::get($boost->id, false);

    (new FeedInsertPipeline($boost->id, $sharer->profile_id))->handle();

    $feed = HomeTimelineService::get($viewer->profile_id, 0, -1);
    expect(collect($feed)->map(fn ($i) => (string) $i))->not->toContain((string) $boost->id);
});

it('delivers a reblog to a follower who blocked nobody', function () {
    $original = User::factory()->create();
    $sharer = User::factory()->create();
    $viewer = User::factory()->create();
    foreach ([$original, $sharer, $viewer] as $u) {
        $u->refresh();
    }

    Redis::del(HomeTimelineService::CACHE_KEY.$viewer->profile_id);

    Follower::create(['profile_id' => $viewer->profile_id, 'following_id' => $sharer->profile_id, 'local_profile' => true]);
    FollowerService::add($viewer->profile_id, $sharer->profile_id);

    $boost = reblogStatus($original, $sharer);
    StatusService::get($boost->id, false);

    (new FeedInsertPipeline($boost->id, $sharer->profile_id))->handle();

    $feed = HomeTimelineService::get($viewer->profile_id, 0, -1);
    expect(collect($feed)->map(fn ($i) => (string) $i))->toContain((string) $boost->id);
});
