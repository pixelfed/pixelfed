<?php

use App\Models\Follower;
use App\Models\Status;
use App\Models\User;
use App\Services\HomeTimelineService;
use App\Services\StatusService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Laravel\Passport\Passport;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Walking on when a home window renders nothing
|--------------------------------------------------------------------------
|
| A window of members that are all boosts for a viewer with boosts off, or
| a run behind a muted account, produces an empty page from a feed that
| reports plenty of members, and that is not end-of-feed. The read takes up
| to three further windows while the page is empty and at least one member
| was dropped as unrenderable, as a boost, or by a viewer filter.
|
*/

afterEach(function () {
    Redis::flushdb();
});

function unrenderableViewer(): array
{
    $viewer = User::factory()->create();
    $viewer->refresh();
    $author = User::factory()->create();
    $author->refresh();
    Follower::withoutEvents(fn () => Follower::create([
        'profile_id' => $viewer->profile_id,
        'following_id' => $author->profile_id,
    ]));

    return [$viewer, $author];
}

function unrenderablePhoto(int $profileId, int $minutesAgo, array $overrides = []): Status
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
        'created_at' => now()->subMinutes($minutesAgo),
    ], $overrides));
    StatusService::get($status->id, false);
    StatusService::getMastodon($status->id, false);

    return $status;
}

it('pages past a window of boosts for a viewer with boosts switched off', function () {
    [$viewer, $author] = unrenderableViewer();
    $pid = $viewer->profile_id;
    $stranger = User::factory()->create();
    $stranger->refresh();
    $original = unrenderablePhoto($stranger->profile_id, 500);
    $photo = unrenderablePhoto($author->profile_id, 400);
    HomeTimelineService::add($pid, $photo->id);
    // The whole first window (limit 1 plus the 50-member reblog pad, and the
    // inclusive stop index that makes it one wider) is boosts.
    $shares = collect(range(1, 60))->map(function ($i) use ($author, $original) {
        $share = Status::factory()->create([
            'profile_id' => $author->profile_id,
            'type' => 'share',
            'scope' => 'public',
            'visibility' => 'public',
            'local' => true,
            'in_reply_to_id' => null,
            'reblog_of_id' => $original->id,
            'uri' => null,
            'created_at' => now()->subMinutes(120 - $i),
        ]);
        StatusService::get($share->id, false);
        StatusService::getMastodon($share->id, false);

        return $share;
    });
    foreach ($shares as $share) {
        HomeTimelineService::add($pid, $share->id);
    }
    Passport::actingAs($viewer, ['read']);
    $ids = collect($this->getJson('/api/v1/timelines/home?limit=1')->assertOk()->json())
        ->pluck('id')->map(fn ($id) => (int) $id)->all();
    expect($ids)->toContain((int) $photo->id);
});
