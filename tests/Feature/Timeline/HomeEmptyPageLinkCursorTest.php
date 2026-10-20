<?php

use App\Models\CustomFilter;
use App\Models\Follower;
use App\Models\Profile;
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
| The next cursor on an empty home page
|--------------------------------------------------------------------------
|
| The feed is ranked by created_at, so a remote post can hold the lowest id
| in a window and still sit at the top of it. Deriving the continuation
| cursor from the numeric minimum therefore skips every member between that
| post and the real bottom of the window, and the client never sees them.
|
*/

afterEach(function () {
    Redis::flushdb();
});

function emptyLinkHiddenStatus(int $profileId, int $minutesAgo, array $overrides = []): Status
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
        'caption' => 'hideme sunset photo',
        'rendered' => 'hideme sunset photo',
        'created_at' => now()->subMinutes($minutesAgo),
    ], $overrides));

    StatusService::get($status->id, false);
    StatusService::getMastodon($status->id, false);

    return $status;
}

function emptyLinkHideFilter(int $pid): void
{
    $filter = CustomFilter::create([
        'profile_id' => $pid,
        'title' => 'hide sunsets',
        'context' => ['home'],
        'action' => 1,
    ]);
    $filter->keywords()->create(['keyword' => 'hideme', 'whole_word' => false]);
    Cache::forget('filters:v3:'.$pid);
}

it('points the next cursor at the display-order oldest member of an empty page', function () {
    $viewer = User::factory()->create();
    $viewer->refresh();
    $pid = $viewer->profile_id;

    $local = User::factory()->create();
    $local->refresh();
    $remote = Profile::factory()->create(['user_id' => null, 'domain' => 'remote.example']);

    foreach ([$local->profile_id, $remote->id] as $followingId) {
        Follower::withoutEvents(fn () => Follower::create([
            'profile_id' => $pid,
            'following_id' => $followingId,
        ]));
    }

    $locals = collect([30, 20, 10])->map(
        fn ($minutesAgo) => emptyLinkHiddenStatus($local->profile_id, $minutesAgo)
    );

    // Fetched first and discarded last: a low id with a recent date.
    $lowIdRecent = emptyLinkHiddenStatus($remote->id, 1, [
        'id' => SnowflakeService::byDate(now()->subMonths(8)),
        'local' => false,
        'uri' => 'https://remote.example/p/'.uniqid(),
    ]);

    foreach ($locals->push($lowIdRecent) as $status) {
        HomeTimelineService::add($pid, $status->id);
    }

    emptyLinkHideFilter($pid);

    $displayOldest = (int) $locals
        ->filter(fn ($s) => (int) $s->id !== (int) $lowIdRecent->id)
        ->sortBy(fn ($s) => $s->created_at->timestamp)
        ->first()
        ->id;

    expect((int) $lowIdRecent->id)->toBeLessThan($displayOldest);

    Passport::actingAs($viewer, ['read']);

    $res = $this->getJson('/api/v1/timelines/home?limit=5')->assertOk();

    expect($res->json())->toBe([]);

    $link = $res->headers->get('Link');

    expect($link)->not->toBeNull()
        ->and($link)->toContain('rel="next"')
        ->and($link)->toContain('max_id='.$displayOldest);
});

it('emits no link when the fetched window itself is empty', function () {
    $viewer = User::factory()->create();
    $viewer->refresh();
    $pid = $viewer->profile_id;

    $author = User::factory()->create();
    $author->refresh();
    Follower::withoutEvents(fn () => Follower::create([
        'profile_id' => $pid,
        'following_id' => $author->profile_id,
    ]));

    $statuses = collect([30, 20, 10])->map(
        fn ($minutesAgo) => emptyLinkHiddenStatus($author->profile_id, $minutesAgo, [
            'caption' => 'ordinary photo',
            'rendered' => 'ordinary photo',
        ])
    );

    foreach ($statuses as $status) {
        HomeTimelineService::add($pid, $status->id);
    }

    $oldest = (int) $statuses->sortBy(fn ($s) => $s->created_at->timestamp)->first()->id;

    Passport::actingAs($viewer, ['read']);

    $res = $this->getJson('/api/v1/timelines/home?limit=5&max_id='.$oldest)->assertOk();

    expect($res->json())->toBe([])
        ->and($res->headers->get('Link'))->toBeNull();
});
