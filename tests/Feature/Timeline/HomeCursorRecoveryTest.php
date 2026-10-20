<?php

use App\Models\Follower;
use App\Models\Profile;
use App\Models\Status;
use App\Models\User;
use App\Services\HomeTimelineService;
use App\Services\NetworkTimelineService;
use App\Services\PublicTimelineService;
use App\Services\StatusService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Laravel\Passport\Passport;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Cursor handling on the cached timelines
|--------------------------------------------------------------------------
|
| Two ways a cursor turns a populated feed into a permanently empty one:
| a cursor value that is present but non-positive (min_id=0, min_id=),
| which selects the wrong getter and yields nothing; and a cursor that
| points below the trimmed feed window, which yields nothing on every
| retry because a client that persists its cursor keeps sending it.
|
*/

afterEach(function () {
    Redis::flushdb();
});

function cursorRecoveryStatus(int $profileId, int $minutesAgo, array $overrides = []): Status
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

/**
 * A viewer whose home feed holds six members, plus the members ordered
 * newest-first the way the endpoint serves them.
 */
function cursorRecoveryHomeFeed(): array
{
    $viewer = User::factory()->create();
    $viewer->refresh();
    $author = User::factory()->create();
    $author->refresh();

    Follower::create([
        'profile_id' => $viewer->profile_id,
        'following_id' => $author->profile_id,
    ]);

    $statuses = collect(range(0, 5))->map(
        fn ($i) => cursorRecoveryStatus($author->profile_id, 60 - $i)
    );

    foreach ($statuses as $status) {
        HomeTimelineService::add($viewer->profile_id, $status->id);
    }

    $ordered = $statuses->sortByDesc(fn ($s) => (int) $s->id)->values();

    return [$viewer, $author, $ordered];
}

/** Six local statuses in the local timeline set, newest-first. */
function cursorRecoveryPublicFeed(): array
{
    $author = User::factory()->create();
    $author->refresh();

    $statuses = collect(range(0, 5))->map(
        fn ($i) => cursorRecoveryStatus($author->profile_id, 60 - $i)
    );

    foreach ($statuses as $status) {
        PublicTimelineService::add($status->id);
    }

    return [$author, $statuses->sortByDesc(fn ($s) => (int) $s->id)->values()];
}

/** Six remote statuses in the network timeline set, newest-first. */
function cursorRecoveryNetworkFeed(): array
{
    $author = Profile::factory()->create(['user_id' => null, 'domain' => 'remote.example']);

    $statuses = collect(range(0, 5))->map(fn ($i) => cursorRecoveryStatus($author->id, 60 - $i, [
        'local' => false,
        'uri' => 'https://remote.example/p/'.uniqid(),
    ]));

    foreach ($statuses as $status) {
        NetworkTimelineService::add($status->id);
    }

    return [$author, $statuses->sortByDesc(fn ($s) => (int) $s->id)->values()];
}

it('serves the max_id window for every spelling of an absent min_id', function (string $param) {
    [$viewer, , $ordered] = cursorRecoveryHomeFeed();

    Passport::actingAs($viewer, ['read']);

    $res = $this->getJson('/api/v1/timelines/home?limit=10&max_id='.$ordered->first()->id.'&'.$param)
        ->assertOk()
        ->json();

    expect($res)->not->toBeEmpty();
})->with(['min_id=0', 'min_id=', 'min_id=%200']);

it('rejects a malformed cursor rather than normalising it', function (string $param) {
    [$viewer] = cursorRecoveryHomeFeed();

    Passport::actingAs($viewer, ['read']);

    $this->getJson('/api/v1/timelines/home?limit=10&'.$param)->assertStatus(422);
})->with(['min_id=00', 'min_id=0.0']);

it('serves the newest window for a bare max_id=0 or the min_id=1 sentinel', function (string $param) {
    [$viewer] = cursorRecoveryHomeFeed();

    Passport::actingAs($viewer, ['read']);

    $res = $this->getJson('/api/v1/timelines/home?limit=10&'.$param)
        ->assertOk()
        ->json();

    expect($res)->not->toBeEmpty();
})->with(['max_id=0', 'min_id=1']);

it('lets a positive min_id win over a positive max_id', function () {
    [$viewer, , $ordered] = cursorRecoveryHomeFeed();

    Passport::actingAs($viewer, ['read']);

    $res = $this->getJson('/api/v1/timelines/home?limit=10&min_id='.$ordered->get(2)->id.'&max_id='.$ordered->first()->id)
        ->assertOk()
        ->json();

    $ids = collect($res)->pluck('id')->map(fn ($id) => (int) $id)->all();

    expect($ids)->toBe([
        (int) $ordered->get(0)->id,
        (int) $ordered->get(1)->id,
    ]);
});

it('serves the newest window when a home max_id cursor predates the feed', function () {
    [$viewer, $author, $ordered] = cursorRecoveryHomeFeed();

    // Published a year ago and never added to the feed: the score it resolves
    // to sits below every member, which is what a client holding a cursor
    // from before the last trim sends.
    $stale = cursorRecoveryStatus($author->profile_id, 60 * 24 * 365);

    Passport::actingAs($viewer, ['read']);

    $res = $this->getJson('/api/v1/timelines/home?limit=10&max_id='.$stale->id)->assertOk();

    $ids = collect($res->json())->pluck('id')->map(fn ($id) => (int) $id)->all();

    expect($ids)->not->toBeEmpty()
        ->and($ids)->toContain((int) $ordered->first()->id)
        ->and($res->headers->get('Link'))->toContain('rel="next"');
});

it('returns an empty page at the end of the home feed', function () {
    [$viewer, , $ordered] = cursorRecoveryHomeFeed();

    Passport::actingAs($viewer, ['read']);

    $res = $this->getJson('/api/v1/timelines/home?limit=10&max_id='.$ordered->last()->id)->assertOk();

    expect($res->json())->toBe([]);
});

it('serves the newest window when a local max_id cursor predates the feed', function () {
    [$author, $ordered] = cursorRecoveryPublicFeed();

    $stale = cursorRecoveryStatus($author->profile_id, 60 * 24 * 365);

    $viewer = User::factory()->create();
    $viewer->refresh();
    Passport::actingAs($viewer, ['read']);

    $res = $this->getJson('/api/v1/timelines/public?limit=10&max_id='.$stale->id)
        ->assertOk()
        ->json();

    $ids = collect($res)->pluck('id')->map(fn ($id) => (int) $id)->all();

    expect($ids)->not->toBeEmpty()
        ->and($ids)->toContain((int) $ordered->first()->id);
});

it('returns an empty page at the end of the local feed', function () {
    [, $ordered] = cursorRecoveryPublicFeed();

    $viewer = User::factory()->create();
    $viewer->refresh();
    Passport::actingAs($viewer, ['read']);

    $res = $this->getJson('/api/v1/timelines/public?limit=10&max_id='.$ordered->last()->id)
        ->assertOk()
        ->json();

    expect($res)->toBe([]);
});

it('serves the newest window when a network max_id cursor predates the feed', function () {
    [$author, $ordered] = cursorRecoveryNetworkFeed();

    $stale = cursorRecoveryStatus($author->id, 60 * 24 * 365, [
        'local' => false,
        'uri' => 'https://remote.example/p/'.uniqid(),
    ]);

    $viewer = User::factory()->create();
    $viewer->refresh();
    Passport::actingAs($viewer, ['read']);

    $res = $this->getJson('/api/v1/timelines/public?remote=1&limit=10&max_id='.$stale->id)
        ->assertOk()
        ->json();

    $ids = collect($res)->pluck('id')->map(fn ($id) => (int) $id)->all();

    expect($ids)->not->toBeEmpty()
        ->and($ids)->toContain((int) $ordered->first()->id);
});

it('returns an empty page at the end of the network feed', function () {
    [, $ordered] = cursorRecoveryNetworkFeed();

    $viewer = User::factory()->create();
    $viewer->refresh();
    Passport::actingAs($viewer, ['read']);

    $res = $this->getJson('/api/v1/timelines/public?remote=1&limit=10&max_id='.$ordered->last()->id)
        ->assertOk()
        ->json();

    expect($res)->toBe([]);
});
