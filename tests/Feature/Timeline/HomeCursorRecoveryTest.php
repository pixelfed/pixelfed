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
| Cursor handling on the cached timelines
|--------------------------------------------------------------------------
|
| A cursor value that is present but non-positive (min_id=0, min_id=)
| selects the wrong getter and yields nothing, turning a populated home
| feed into a permanently empty one. The cursor is resolved once into an
| optional pair of positive ids, so a non-positive value is treated as no
| cursor at all and min_id still wins when both are sent positive.
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
