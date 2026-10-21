<?php

use App\Models\Follower;
use App\Models\Status;
use App\Models\User;
use App\Services\HomeTimelineService;
use App\Services\StatusService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;
use Laravel\Passport\Passport;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Rebuilding a home feed while serving the read that found it missing
|--------------------------------------------------------------------------
|
| A feed with no Redis key is rebuilt by the request that discovered it,
| under a lock so concurrent readers do not all rebuild, and the page that
| comes back has to answer the cursor the client actually sent. A rebuild
| that fails must leave the feed eligible for the next attempt instead of
| wedging it behind bookkeeping that says the work is already done.
|
*/

afterEach(function () {
    Redis::flushdb();
});

function coldBootLockKey(int $pid): string
{
    return 'pf:timeline:home:warm:lock:'.$pid;
}

function coldBootPhoto(int $profileId, int $minutesAgo): Status
{
    $status = Status::factory()->create([
        'profile_id' => $profileId,
        'type' => 'photo',
        'scope' => 'public',
        'visibility' => 'public',
        'local' => true,
        'in_reply_to_id' => null,
        'reblog_of_id' => null,
        'uri' => null,
        'created_at' => now()->subMinutes($minutesAgo),
    ]);

    StatusService::get($status->id, false);
    StatusService::getMastodon($status->id, false);

    return $status;
}

/** A viewer with a cold feed and $count eligible posts from one follow. */
function coldBootViewerWithPosts(int $count): array
{
    $viewer = User::factory()->create();
    $viewer->refresh();
    $author = User::factory()->create();
    $author->refresh();

    Follower::withoutEvents(fn () => Follower::create([
        'profile_id' => $viewer->profile_id,
        'following_id' => $author->profile_id,
    ]));

    $statuses = collect(range(0, $count - 1))->map(
        fn ($i) => coldBootPhoto($author->profile_id, $count - $i)
    );

    Redis::del(HomeTimelineService::CACHE_KEY.$viewer->profile_id);

    return [$viewer, $author, $statuses->sortByDesc(fn ($s) => (int) $s->id)->values()];
}

function coldBootStatusQueries(callable $fn): int
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

it('honours the cursor on the page served by a cold boot', function () {
    [$viewer, , $ordered] = coldBootViewerWithPosts(12);

    expect(HomeTimelineService::cacheKeyExists($viewer->profile_id))->toBeFalse();

    $cursor = (int) $ordered->get(8)->id;

    Passport::actingAs($viewer, ['read']);

    $res = $this->getJson('/api/v1/timelines/home?limit=4&max_id='.$cursor)
        ->assertOk()
        ->json();

    $ids = collect($res)->pluck('id')->map(fn ($id) => (int) $id)->all();

    expect($ids)->not->toBeEmpty();

    foreach ($ids as $id) {
        expect($id)->toBeLessThan($cursor);
    }
});

it('does not rebuild while another request holds the warm lock', function () {
    [$viewer] = coldBootViewerWithPosts(4);
    $pid = $viewer->profile_id;

    $held = Cache::lock(coldBootLockKey($pid), 30);
    expect($held->get())->toBeTrue();

    Passport::actingAs($viewer, ['read']);

    $queries = coldBootStatusQueries(function () {
        $this->getJson('/api/v1/timelines/home?limit=4')->assertOk();
    });

    expect($queries)->toBe(0);

    $held->release();
});

it('releases the warm lock it took', function () {
    [$viewer] = coldBootViewerWithPosts(4);
    $pid = $viewer->profile_id;

    Passport::actingAs($viewer, ['read']);

    $this->getJson('/api/v1/timelines/home?limit=4')->assertOk();

    $lock = Cache::lock(coldBootLockKey($pid), 30);

    expect($lock->get())->toBeTrue();

    $lock->release();
});

it('leaves the feed rebuildable when the rebuild fails', function () {
    [$viewer, , $ordered] = coldBootViewerWithPosts(3);
    $pid = $viewer->profile_id;

    Passport::actingAs($viewer, ['read']);

    Schema::rename('statuses', 'statuses_unavailable');

    try {
        $first = $this->getJson('/api/v1/timelines/home?limit=4');
    } finally {
        Schema::rename('statuses_unavailable', 'statuses');
    }

    $first->assertOk();
    expect($first->json())->toBe([])
        ->and(HomeTimelineService::shouldWarmEmpty($pid))->toBeTrue();

    $ids = collect($this->getJson('/api/v1/timelines/home?limit=4')->assertOk()->json())
        ->pluck('id')->map(fn ($id) => (int) $id)->all();

    expect($ids)->toContain((int) $ordered->first()->id);
});

it('completes a cold boot over a large candidate set promptly', function () {
    [$viewer] = coldBootViewerWithPosts(120);

    Passport::actingAs($viewer, ['read']);

    $start = microtime(true);
    $this->getJson('/api/v1/timelines/home?limit=20')->assertOk();
    $elapsed = (microtime(true) - $start) * 1000;

    expect($elapsed)->toBeLessThan(3000);
});
