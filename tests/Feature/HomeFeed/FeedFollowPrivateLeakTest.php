<?php

use App\Jobs\HomeFeedPipeline\FeedFollowPipeline;
use App\Models\Follower;
use App\Models\Status;
use App\Models\User;
use App\Services\FollowerService;
use App\Services\HomeTimelineService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Redis;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| FeedFollowPipeline must verify the relationship before backfilling
|--------------------------------------------------------------------------
|
| The backfill included private (followers-only) posts and never checked that
| a follow exists. It is dispatched on unblock/unmute, where typically no
| follow exists, so with cached_home_timeline enabled a user could read any
| account's followers-only posts by blocking then unblocking them. Backfill
| must require a follow, and private posts require an accepted follow.
|
*/

function privatePost(User $author): Status
{
    return Status::factory()->create([
        'profile_id' => $author->profile_id,
        'type' => 'photo',
        'scope' => 'private',
        'visibility' => 'private',
    ]);
}

function publicPost(User $author): Status
{
    return Status::factory()->create([
        'profile_id' => $author->profile_id,
        'type' => 'photo',
        'scope' => 'public',
        'visibility' => 'public',
    ]);
}

it('does not backfill any posts when the actor does not follow the target', function () {
    $actor = User::factory()->create();
    $target = User::factory()->create();
    $actor->refresh();
    $target->refresh();

    Redis::del(HomeTimelineService::CACHE_KEY.$actor->profile_id);

    $priv = privatePost($target);
    $pub = publicPost($target);

    // No follow relationship (the unblock scenario).
    (new FeedFollowPipeline($actor->profile_id, $target->profile_id))->handle();

    $feed = collect(HomeTimelineService::get($actor->profile_id, 0, -1))->map(fn ($i) => (string) $i);
    expect($feed)->not->toContain((string) $priv->id);
    expect($feed)->not->toContain((string) $pub->id);
});

it('backfills private posts for an accepted follower', function () {
    $actor = User::factory()->create();
    $target = User::factory()->create();
    $actor->refresh();
    $target->refresh();

    Redis::del(HomeTimelineService::CACHE_KEY.$actor->profile_id);

    Follower::create(['profile_id' => $actor->profile_id, 'following_id' => $target->profile_id, 'local_profile' => true]);
    FollowerService::add($actor->profile_id, $target->profile_id);

    $priv = privatePost($target);
    $pub = publicPost($target);

    (new FeedFollowPipeline($actor->profile_id, $target->profile_id))->handle();

    $feed = collect(HomeTimelineService::get($actor->profile_id, 0, -1))->map(fn ($i) => (string) $i);
    expect($feed)->toContain((string) $priv->id);
    expect($feed)->toContain((string) $pub->id);
});
