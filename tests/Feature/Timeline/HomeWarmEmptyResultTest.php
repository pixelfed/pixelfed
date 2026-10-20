<?php

use App\Models\Follower;
use App\Models\Status;
use App\Models\User;
use App\Services\HomeTimelineService;
use App\Services\SnowflakeService;
use App\Services\StatusService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Laravel\Passport\Passport;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| What the home warm does when it finds nothing
|--------------------------------------------------------------------------
|
| A forced warm that publishes no members must not take the existing feed
| with it, and it must not conclude "nothing to show" from its own six-month
| performance window, from a boost-only follow set, or from a stale cached
| following list. Those are the routes by which a user with content ends up
| with a feed that reports empty and stays that way.
|
*/

afterEach(function () {
    Redis::flushdb();
});

function warmEmptyPhoto(int $profileId, array $overrides = []): Status
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
        'created_at' => now()->subMinutes(30),
    ], $overrides));

    StatusService::get($status->id, false);
    StatusService::getMastodon($status->id, false);

    return $status;
}

function warmEmptyShare(int $sharerPid, int $originalPid): Status
{
    $original = warmEmptyPhoto($originalPid);

    $share = Status::factory()->create([
        'profile_id' => $sharerPid,
        'type' => 'share',
        'scope' => 'public',
        'visibility' => 'public',
        'local' => true,
        'in_reply_to_id' => null,
        'reblog_of_id' => $original->id,
        'uri' => null,
        'created_at' => now()->subMinutes(20),
    ]);

    StatusService::get($share->id, false);
    StatusService::getMastodon($share->id, false);

    return $share;
}

function warmEmptyViewerFollowing(): array
{
    $viewer = User::factory()->create();
    $viewer->refresh();
    $author = User::factory()->create();
    $author->refresh();

    Follower::create([
        'profile_id' => $viewer->profile_id,
        'following_id' => $author->profile_id,
    ]);

    return [$viewer, $author];
}

it('keeps a populated feed when a forced warm publishes nothing', function () {
    $viewer = User::factory()->create();
    $viewer->refresh();
    $pid = $viewer->profile_id;

    // A real status by an author the viewer does not follow: the warm's
    // candidate query cannot find it, so the warm publishes nothing.
    $stranger = User::factory()->create();
    $stranger->refresh();
    $status = warmEmptyPhoto($stranger->profile_id);

    HomeTimelineService::add($pid, $status->id);
    expect(HomeTimelineService::count($pid))->toBe(1);

    HomeTimelineService::warmCache($pid, true, 400);

    expect(HomeTimelineService::count($pid))->toBe(1)
        ->and(collect(HomeTimelineService::get($pid, 0, -1))->map(fn ($v) => (int) $v)->all())
        ->toContain((int) $status->id);
});

it('warms a dormant follow whose only post predates the six month window', function () {
    [$viewer, $author] = warmEmptyViewerFollowing();
    $pid = $viewer->profile_id;

    // The warm's candidate query floors on a six-month-old snowflake, so an
    // id below that floor is invisible to it even though it is eligible.
    $old = warmEmptyPhoto($author->profile_id, [
        'id' => SnowflakeService::byDate(now()->subMonths(8)),
        'created_at' => now()->subMonths(8),
    ]);

    expect((int) $old->id)->toBeLessThan((int) SnowflakeService::byDate(now()->subMonths(6)));

    HomeTimelineService::warmCache($pid, true, 400);

    expect(collect(HomeTimelineService::get($pid, 0, -1))->map(fn ($v) => (int) $v)->all())
        ->toContain((int) $old->id);

    Passport::actingAs($viewer, ['read']);

    $ids = collect($this->getJson('/api/v1/timelines/home?limit=10')->assertOk()->json())
        ->pluck('id')->map(fn ($id) => (int) $id)->all();

    expect($ids)->toContain((int) $old->id);
});

it('warms a follow set that has only ever boosted', function () {
    [$viewer, $author] = warmEmptyViewerFollowing();
    $pid = $viewer->profile_id;

    $stranger = User::factory()->create();
    $stranger->refresh();

    $shares = collect(range(1, 2))->map(
        fn () => warmEmptyShare($author->profile_id, $stranger->profile_id)
    );

    HomeTimelineService::warmCache($pid, true, 400);

    expect(HomeTimelineService::cacheKeyExists($pid))->toBeTrue();

    $cached = collect(HomeTimelineService::get($pid, 0, -1))->map(fn ($v) => (int) $v)->all();

    foreach ($shares as $share) {
        expect($cached)->toContain((int) $share->id);
    }

    Passport::actingAs($viewer, ['read']);

    $res = $this->getJson('/api/v1/timelines/home?_pe=1&min_id=1&include_reblogs=1&limit=10')
        ->assertOk()
        ->json();

    expect($res)->not->toBeEmpty();
});

it('reports how many members the warm published', function () {
    [$viewer, $author] = warmEmptyViewerFollowing();
    $pid = $viewer->profile_id;

    collect(range(1, 3))->each(fn () => warmEmptyPhoto($author->profile_id));

    expect(HomeTimelineService::warmCache($pid, true, 100))->toBe(3);

    $barren = User::factory()->create();
    $barren->refresh();

    expect(HomeTimelineService::warmCache($barren->profile_id, true, 100))->toBe(0);
});

it('rereads the follow relationship when the cached following list is only the viewer', function () {
    [$viewer, $author] = warmEmptyViewerFollowing();
    $pid = $viewer->profile_id;

    $status = warmEmptyPhoto($author->profile_id);

    // A following list cached before the follow landed: without a live
    // re-read the warm concludes the viewer follows nobody for 14 days.
    Cache::put('profile:following:'.$pid, [$pid], 1209600);

    HomeTimelineService::warmCache($pid, true, 400);

    expect(collect(HomeTimelineService::get($pid, 0, -1))->map(fn ($v) => (int) $v)->all())
        ->toContain((int) $status->id);
});
