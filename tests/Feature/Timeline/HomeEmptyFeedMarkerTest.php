<?php

use App\Jobs\HomeFeedPipeline\FeedFollowPipeline;
use App\Jobs\HomeFeedPipeline\FeedInsertPipeline;
use App\Jobs\HomeFeedPipeline\FeedInsertRemotePipeline;
use App\Jobs\HomeFeedPipeline\HashtagInsertFanoutPipeline;
use App\Models\Follower;
use App\Models\Profile;
use App\Models\Status;
use App\Models\StatusHashtag;
use App\Models\User;
use App\Models\UserDomainBlock;
use App\Services\FollowerService;
use App\Services\HashtagFollowService;
use App\Services\HomeTimelineService;
use App\Services\StatusService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Laravel\Passport\Passport;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| The genuinely-empty home feed marker
|--------------------------------------------------------------------------
|
| A home feed with no content and a home feed that was never built look
| identical from Redis: neither has a key. The marker records that a warm
| completed and found nothing, so repeated reads stop re-querying, and it
| has to be evicted by anything that can make the feed non-empty. It must
| never be written because a warm failed to hydrate what it found, which
| is the difference between "nothing to show" and "something went wrong".
|
*/

const MARKER_GUARD_PREFIX = 'pf:timeline:empty-warm-guard:';

afterEach(function () {
    Redis::flushdb();
});

function markerGuardKey(int $pid): string
{
    return MARKER_GUARD_PREFIX.HomeTimelineService::CACHE_KEY.$pid;
}

function markerPhoto(int $profileId, array $overrides = []): Status
{
    $status = Status::factory()->create(array_merge([
        'profile_id' => $profileId,
        'type' => 'photo',
        'scope' => 'public',
        'visibility' => 'public',
        'local' => true,
        'in_reply_to_id' => null,
        'reblog_of_id' => null,
        'uri' => null,
        'created_at' => now()->subMinutes(10),
    ], $overrides));

    StatusService::get($status->id, false);
    StatusService::getMastodon($status->id, false);

    return $status;
}

/** Count the candidate/hydration queries a block issues against statuses. */
function markerStatusQueries(callable $fn): int
{
    $count = 0;

    DB::listen(function ($query) use (&$count) {
        if (preg_match('/from\s+"?statuses"?/i', $query->sql)) {
            $count++;
        }
    });

    $fn();

    return $count;
}

/** A viewer whose feed a completed warm has already found empty. */
function markerSettledEmptyViewer(): User
{
    $viewer = User::factory()->create();
    $viewer->refresh();

    HomeTimelineService::warmCache($viewer->profile_id, true, 100);

    return $viewer;
}

it('records a completed empty warm and stops re-querying for candidates', function () {
    $viewer = User::factory()->create();
    $viewer->refresh();
    $pid = $viewer->profile_id;

    Passport::actingAs($viewer, ['read']);

    $this->getJson('/api/v1/timelines/home')->assertOk();

    expect(HomeTimelineService::shouldWarmEmpty($pid))->toBeFalse();

    $queries = markerStatusQueries(function () {
        $this->getJson('/api/v1/timelines/home')->assertOk();
    });

    expect($queries)->toBe(0);
});

it('leaves the feed and the warm eligible when candidates fail to hydrate', function () {
    $viewer = User::factory()->create();
    $viewer->refresh();
    $pid = $viewer->profile_id;

    $author = User::factory()->create();
    $author->refresh();
    Follower::withoutEvents(fn () => Follower::create([
        'profile_id' => $pid,
        'following_id' => $author->profile_id,
    ]));

    // Public visibility so the candidate query returns it, draft scope so it
    // never resolves: the warm found work it could not finish.
    markerPhoto($author->profile_id, ['scope' => 'draft']);

    $stranger = User::factory()->create();
    $stranger->refresh();
    $seed = markerPhoto($stranger->profile_id);
    HomeTimelineService::add($pid, $seed->id);

    HomeTimelineService::warmCache($pid, true, 100);

    expect(HomeTimelineService::count($pid))->toBe(1)
        ->and(Cache::has(markerGuardKey($pid)))->toBeFalse()
        ->and(HomeTimelineService::shouldWarmEmpty($pid))->toBeTrue();
});

it('keeps querying on the next read when a warm could not hydrate its candidates', function () {
    $viewer = User::factory()->create();
    $viewer->refresh();
    $pid = $viewer->profile_id;

    $author = User::factory()->create();
    $author->refresh();
    Follower::withoutEvents(fn () => Follower::create([
        'profile_id' => $pid,
        'following_id' => $author->profile_id,
    ]));

    markerPhoto($author->profile_id, ['scope' => 'draft']);

    Passport::actingAs($viewer, ['read']);

    $this->getJson('/api/v1/timelines/home')->assertOk();

    $queries = markerStatusQueries(function () {
        $this->getJson('/api/v1/timelines/home')->assertOk();
    });

    expect($queries)->toBeGreaterThan(0);
});

it('records an empty warm whose candidates were all deliberately filtered', function () {
    $viewer = User::factory()->create();
    $viewer->refresh();
    $pid = $viewer->profile_id;

    $author = Profile::factory()->create(['user_id' => null, 'domain' => 'remote.example']);
    Follower::withoutEvents(fn () => Follower::create([
        'profile_id' => $pid,
        'following_id' => $author->id,
    ]));

    markerPhoto($author->id, [
        'local' => false,
        'uri' => 'https://remote.example/p/'.uniqid(),
    ]);

    UserDomainBlock::create(['profile_id' => $pid, 'domain' => 'remote.example']);

    HomeTimelineService::warmCache($pid, true, 100);

    expect(HomeTimelineService::shouldWarmEmpty($pid))->toBeFalse();
});

it('lapses the marker within its interval', function () {
    $viewer = markerSettledEmptyViewer();

    $lapsesAt = (int) Cache::get(markerGuardKey($viewer->profile_id));

    expect($lapsesAt)->toBeGreaterThan(now()->timestamp)
        ->and($lapsesAt)->toBeLessThanOrEqual(now()->addSeconds(960)->timestamp);
});

it('still publishes on a forced warm while the marker is set', function () {
    $viewer = markerSettledEmptyViewer();
    $pid = $viewer->profile_id;

    expect(HomeTimelineService::shouldWarmEmpty($pid))->toBeFalse();

    $author = User::factory()->create();
    $author->refresh();
    Follower::withoutEvents(fn () => Follower::create([
        'profile_id' => $pid,
        'following_id' => $author->profile_id,
    ]));
    $status = markerPhoto($author->profile_id);

    expect(HomeTimelineService::warmCache($pid, true, 100))->toBe(1)
        ->and(collect(HomeTimelineService::get($pid, 0, -1))->map(fn ($v) => (int) $v)->all())
        ->toContain((int) $status->id);
});

it('clears the marker when a warm finally publishes', function () {
    $viewer = markerSettledEmptyViewer();
    $pid = $viewer->profile_id;

    $author = User::factory()->create();
    $author->refresh();
    Follower::withoutEvents(fn () => Follower::create([
        'profile_id' => $pid,
        'following_id' => $author->profile_id,
    ]));
    markerPhoto($author->profile_id);

    HomeTimelineService::warmCache($pid, true, 100);

    expect(HomeTimelineService::shouldWarmEmpty($pid))->toBeTrue();
});

it('clears the marker when the viewer posts, without creating the feed key', function () {
    Queue::fake();

    $viewer = markerSettledEmptyViewer();
    $pid = $viewer->profile_id;

    expect(HomeTimelineService::shouldWarmEmpty($pid))->toBeFalse();

    $own = markerPhoto($pid);
    (new FeedInsertPipeline($own->id, $pid))->handle();

    expect(HomeTimelineService::shouldWarmEmpty($pid))->toBeTrue()
        ->and(HomeTimelineService::cacheKeyExists($pid))->toBeFalse();
});

it('clears the marker when a followed local account posts, without creating the feed key', function () {
    Queue::fake();

    $viewer = User::factory()->create();
    $viewer->refresh();
    $pid = $viewer->profile_id;

    $author = User::factory()->create();
    $author->refresh();
    Follower::withoutEvents(fn () => Follower::create([
        'profile_id' => $pid,
        'following_id' => $author->profile_id,
        'local_profile' => true,
    ]));
    FollowerService::add($pid, $author->profile_id);

    HomeTimelineService::warmCache($pid, true, 100);
    expect(HomeTimelineService::shouldWarmEmpty($pid))->toBeFalse();

    $status = markerPhoto($author->profile_id);
    (new FeedInsertPipeline($status->id, $author->profile_id))->handle();

    expect(HomeTimelineService::shouldWarmEmpty($pid))->toBeTrue()
        ->and(HomeTimelineService::cacheKeyExists($pid))->toBeFalse();
});

it('clears the marker when a followed remote account posts, without creating the feed key', function () {
    Queue::fake();

    $viewer = User::factory()->create();
    $viewer->refresh();
    $pid = $viewer->profile_id;

    $author = Profile::factory()->create(['user_id' => null, 'domain' => 'remote.example']);
    Follower::withoutEvents(fn () => Follower::create([
        'profile_id' => $pid,
        'following_id' => $author->id,
        'local_profile' => true,
    ]));
    FollowerService::add($pid, $author->id);

    HomeTimelineService::warmCache($pid, true, 100);
    expect(HomeTimelineService::shouldWarmEmpty($pid))->toBeFalse();

    $status = markerPhoto($author->id, [
        'local' => false,
        'uri' => 'https://remote.example/p/'.uniqid(),
        'created_at' => now()->subHours(1),
    ]);
    (new FeedInsertRemotePipeline($status->id, $author->id))->handle();

    expect(HomeTimelineService::shouldWarmEmpty($pid))->toBeTrue()
        ->and(HomeTimelineService::cacheKeyExists($pid))->toBeFalse();
});

it('clears the marker on a new follow, without creating the feed key', function () {
    Queue::fake();

    $viewer = markerSettledEmptyViewer();
    $pid = $viewer->profile_id;

    $author = User::factory()->create();
    $author->refresh();
    markerPhoto($author->profile_id);

    Follower::withoutEvents(fn () => Follower::create([
        'profile_id' => $pid,
        'following_id' => $author->profile_id,
        'local_profile' => true,
    ]));
    FollowerService::add($pid, $author->profile_id);

    (new FeedFollowPipeline($pid, $author->profile_id))->handle();

    expect(HomeTimelineService::shouldWarmEmpty($pid))->toBeTrue()
        ->and(HomeTimelineService::cacheKeyExists($pid))->toBeFalse();
});

it('clears the marker when a followed hashtag gets a post, without creating the feed key', function () {
    Queue::fake();

    $viewer = markerSettledEmptyViewer();
    $pid = $viewer->profile_id;

    HashtagFollowService::add(7373, $pid);
    HashtagFollowService::setWarm(7373);

    $author = User::factory()->create();
    $author->refresh();
    $status = markerPhoto($author->profile_id);

    $row = StatusHashtag::create([
        'status_id' => $status->id,
        'hashtag_id' => 7373,
        'profile_id' => $author->profile_id,
        'status_visibility' => 'public',
    ]);

    (new HashtagInsertFanoutPipeline($row))->handle();

    expect(HomeTimelineService::shouldWarmEmpty($pid))->toBeTrue()
        ->and(HomeTimelineService::cacheKeyExists($pid))->toBeFalse();
});
