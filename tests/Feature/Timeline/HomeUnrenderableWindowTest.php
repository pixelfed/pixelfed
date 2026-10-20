<?php

use App\Models\CustomFilter;
use App\Models\Follower;
use App\Models\Status;
use App\Models\User;
use App\Services\HomeTimelineService;
use App\Services\StatusService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Laravel\Passport\Passport;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| A home feed that holds members but can render none of them
|--------------------------------------------------------------------------
|
| Members whose statuses have gone away, and a window entirely made of
| boosts for a viewer who has boosts switched off, both produce an empty
| page from a feed that reports plenty of members. Neither is end-of-feed,
| and neither repairs itself today. A page the viewer explicitly filtered
| out is a different thing and must be left alone.
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

/** Remove a status so its feed member can never hydrate again. */
function unrenderableDestroy(Status $status): void
{
    DB::table('statuses')->where('id', $status->id)->delete();
    Cache::forget(StatusService::key($status->id, true));
    Cache::forget(StatusService::key($status->id, false));
}

function unrenderableCandidateQueries(callable $fn): int
{
    $count = 0;

    DB::listen(function ($query) use (&$count) {
        if (preg_match('/max\(id\)/i', $query->sql)) {
            $count++;
        }
    });

    $fn();

    return $count;
}

it('prunes members whose statuses have gone away and serves what remains', function () {
    [$viewer, $author] = unrenderableViewer();
    $pid = $viewer->profile_id;

    $stale = collect(range(1, 5))->map(fn ($i) => unrenderablePhoto($author->profile_id, 100 + $i));

    foreach ($stale as $status) {
        HomeTimelineService::add($pid, $status->id);
    }

    foreach ($stale as $status) {
        unrenderableDestroy($status);
    }

    // Eligible, recent, and not in the feed: the repair the request can make.
    $fresh = unrenderablePhoto($author->profile_id, 5);

    Passport::actingAs($viewer, ['read']);

    $ids = collect($this->getJson('/api/v1/timelines/home?limit=10')->assertOk()->json())
        ->pluck('id')->map(fn ($id) => (int) $id)->all();

    expect($ids)->toContain((int) $fresh->id);

    foreach ($stale as $status) {
        expect(Redis::zscore(HomeTimelineService::CACHE_KEY.$pid, $status->id))->toBeNull();
    }
});

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

it('leaves a page the viewer filtered out alone', function () {
    [$viewer, $author] = unrenderableViewer();
    $pid = $viewer->profile_id;

    $statuses = collect(range(1, 3))->map(fn ($i) => unrenderablePhoto($author->profile_id, 10 + $i, [
        'caption' => 'hideme sunset photo',
        'rendered' => 'hideme sunset photo',
    ]));

    foreach ($statuses as $status) {
        HomeTimelineService::add($pid, $status->id);
    }

    $filter = CustomFilter::create([
        'profile_id' => $pid,
        'title' => 'hide sunsets',
        'context' => ['home'],
        'action' => 1,
    ]);
    $filter->keywords()->create(['keyword' => 'hideme', 'whole_word' => false]);
    Cache::forget('filters:v3:'.$pid);

    Passport::actingAs($viewer, ['read']);

    $candidateQueries = unrenderableCandidateQueries(function () {
        $res = $this->getJson('/api/v1/timelines/home?limit=5')->assertOk();

        expect($res->json())->toBe([]);
    });

    expect($candidateQueries)->toBe(0)
        ->and(HomeTimelineService::count($pid))->toBe(3);
});
