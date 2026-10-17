<?php

use App\Jobs\HomeFeedPipeline\HashtagRemoveFanoutPipeline;
use App\Models\Follower;
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
    Queue::fake();
});

afterEach(function () {
    Redis::flushdb();
});

function feedIds(int $pid): array
{
    return collect(HomeTimelineService::get($pid, 0, -1))->map(fn ($v) => (int) $v)->all();
}

it('keeps the post for a hashtag follower who also follows the author', function () {
    $hid = 7777;

    $author = User::factory()->create();
    $author->refresh();

    $alsoFollowsAuthor = User::factory()->create();
    $alsoFollowsAuthor->refresh();
    Follower::create([
        'profile_id' => $alsoFollowsAuthor->profile_id,
        'following_id' => $author->profile_id,
    ]);

    $tagOnly = User::factory()->create();
    $tagOnly->refresh();

    foreach ([$alsoFollowsAuthor, $tagOnly] as $u) {
        HashtagFollowService::add($hid, $u->profile_id);
    }
    HashtagFollowService::setWarm($hid);

    $status = Status::factory()->create([
        'profile_id' => $author->profile_id,
        'type' => 'photo',
        'scope' => 'public',
        'visibility' => 'public',
    ]);
    StatusService::get($status->id, false);

    HomeTimelineService::add($alsoFollowsAuthor->profile_id, $status->id);
    HomeTimelineService::add($tagOnly->profile_id, $status->id);

    (new HashtagRemoveFanoutPipeline($status->id, $hid))->handle();

    expect(feedIds($alsoFollowsAuthor->profile_id))->toContain((int) $status->id)
        ->and(feedIds($tagOnly->profile_id))->not->toContain((int) $status->id);
});

it('removes a deleted status for every hashtag follower', function () {
    $hid = 8888;

    $author = User::factory()->create();
    $author->refresh();

    $follower = User::factory()->create();
    $follower->refresh();
    Follower::create([
        'profile_id' => $follower->profile_id,
        'following_id' => $author->profile_id,
    ]);

    HashtagFollowService::add($hid, $follower->profile_id);
    HashtagFollowService::setWarm($hid);

    $status = Status::factory()->create([
        'profile_id' => $author->profile_id,
        'type' => 'photo',
        'scope' => 'public',
        'visibility' => 'public',
    ]);

    $sid = $status->id;
    HomeTimelineService::add($follower->profile_id, $sid);

    $status->delete();
    StatusService::del($sid, true);

    (new HashtagRemoveFanoutPipeline($sid, $hid))->handle();

    expect(feedIds($follower->profile_id))->not->toContain((int) $sid);
});
