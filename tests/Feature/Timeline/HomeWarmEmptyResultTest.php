<?php

use App\Models\Follower;
use App\Models\Status;
use App\Models\User;
use App\Services\HomeTimelineService;
use App\Services\StatusService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Redis;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| What the home warm does when it finds nothing
|--------------------------------------------------------------------------
|
| A forced warm that publishes no members must not take the existing feed
| with it: a per-user feed can come up empty because the viewer's cached
| relationships or the statuses behind their feed could not be resolved,
| and throwing the live feed away is how a user who was reading a moment
| ago ends up with nothing. warmCache also returns the number of members
| it published, which is what lets a caller tell "rebuilt" from "empty".
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

it('reports how many members the warm published', function () {
    [$viewer, $author] = warmEmptyViewerFollowing();
    $pid = $viewer->profile_id;
    collect(range(1, 3))->each(fn () => warmEmptyPhoto($author->profile_id));
    expect(HomeTimelineService::warmCache($pid, true, 100))->toBe(3);
    $barren = User::factory()->create();
    $barren->refresh();
    expect(HomeTimelineService::warmCache($barren->profile_id, true, 100))->toBe(0);
});
