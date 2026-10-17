<?php

use App\Jobs\HomeFeedPipeline\HashtagInsertFanoutPipeline;
use App\Models\Profile;
use App\Models\Status;
use App\Models\StatusHashtag;
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

function fanoutFeedIds(int $pid): array
{
    return collect(HomeTimelineService::get($pid, 0, -1))->map(fn ($v) => (int) $v)->all();
}

function remoteTaggedStatus(string $createdAt): array
{
    $author = Profile::factory()->create(['user_id' => null, 'domain' => 'remote.example']);

    $status = Status::factory()->create([
        'profile_id' => $author->id,
        'type' => 'photo',
        'scope' => 'public',
        'visibility' => 'public',
        'local' => false,
        'in_reply_to_id' => null,
        'reblog_of_id' => null,
        'uri' => 'https://remote.example/p/'.uniqid(),
        'created_at' => $createdAt,
    ]);

    $hashtagRow = StatusHashtag::create([
        'status_id' => $status->id,
        'hashtag_id' => 9191,
        'profile_id' => $author->id,
        'status_visibility' => 'public',
    ]);

    StatusService::get($status->id, false);

    return [$status, $hashtagRow];
}

it('does not fan out a stale remote post to hashtag followers', function () {
    $follower = User::factory()->create();
    $follower->refresh();
    HashtagFollowService::add(9191, $follower->profile_id);
    HashtagFollowService::setWarm(9191);

    [$status, $row] = remoteTaggedStatus(now()->subDays(30)->toDateTimeString());

    (new HashtagInsertFanoutPipeline($row))->handle();

    expect(fanoutFeedIds($follower->profile_id))->not->toContain((int) $status->id);
});

it('fans out a recent remote post to hashtag followers', function () {
    $follower = User::factory()->create();
    $follower->refresh();
    HashtagFollowService::add(9191, $follower->profile_id);
    HashtagFollowService::setWarm(9191);

    [$status, $row] = remoteTaggedStatus(now()->subHours(1)->toDateTimeString());

    (new HashtagInsertFanoutPipeline($row))->handle();

    expect(fanoutFeedIds($follower->profile_id))->toContain((int) $status->id);
});
