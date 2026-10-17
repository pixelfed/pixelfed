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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Rebuild publication races and cold-warm serialization
|--------------------------------------------------------------------------
|
| A rebuild selects a snapshot from the database and then publishes it to the
| canonical sorted set. Anything that mutates the canonical set in the window
| between those two steps must survive: a post delivered live after the
| snapshot but before publication has to remain, and a removal applied in that
| window must not be resurrected by the published snapshot. Readers must never
| catch the canonical set empty or half-built while a rebuild is in flight,
| and two cold readers arriving together must trigger exactly one warm.
|
| The pause point is the snapshot query itself. A query listener fires after
| the rebuild has read its rows but before it publishes, which is precisely
| the window a concurrent writer would act in, so the live add and live remove
| are applied there against the canonical key.
|
*/

beforeEach(function () {
    config(['federation.network_timeline' => true]);
    config(['instance.hide_nsfw_on_public_feeds' => false]);
    config(['instance.timeline.network.max_hours_old' => 2160]);
    Redis::flushdb();
    Cache::flush();
});

afterEach(function () {
    DB::flushQueryLog();
    Redis::flushdb();
    Cache::flush();
});

/**
 * The ordered sorted-set members, newest-first, as integers.
 */
function raceOrder(string $key): array
{
    return collect(Redis::zrevrange($key, 0, -1))
        ->map(fn ($id) => (int) $id)
        ->values()
        ->all();
}

/**
 * A local public photo resolved through StatusService so the timeline
 * services can score it from its created_at.
 */
function raceLocalStatus(int $profileId, string $createdAt, array $overrides = []): Status
{
    $status = Status::factory()->create(array_merge([
        'profile_id' => $profileId,
        'type' => 'photo',
        'scope' => 'public',
        'visibility' => 'public',
        'local' => true,
        'in_reply_to_id' => null,
        'reblog_of_id' => null,
        'is_nsfw' => false,
        'uri' => null,
        'created_at' => $createdAt,
    ], $overrides));

    StatusService::get($status->id, false);

    return $status;
}

/**
 * A remote public photo on a given domain, resolved through StatusService.
 */
function raceRemoteStatus(string $domain, string $createdAt, array $overrides = []): Status
{
    $author = Profile::factory()->create(['user_id' => null, 'domain' => $domain]);

    $status = Status::factory()->create(array_merge([
        'profile_id' => $author->id,
        'type' => 'photo',
        'scope' => 'public',
        'visibility' => 'public',
        'local' => false,
        'in_reply_to_id' => null,
        'reblog_of_id' => null,
        'is_nsfw' => false,
        'uri' => 'https://'.$domain.'/p/'.uniqid(),
        'created_at' => $createdAt,
    ], $overrides));

    StatusService::get($status->id, false);

    return $status;
}

/**
 * Register a one-shot hook that fires the first time a query touches the
 * statuses table, i.e. after a rebuild has read its snapshot and before it
 * publishes. Returns a flag object so the caller can confirm it ran.
 */
function onSnapshotSelected(callable $callback): object
{
    $state = new class
    {
        public bool $fired = false;
    };

    DB::listen(function ($query) use ($callback, $state) {
        if ($state->fired) {
            return;
        }

        if (stripos($query->sql, 'from `statuses`') === false
            && stripos($query->sql, 'from "statuses"') === false
            && stripos($query->sql, 'from statuses') === false) {
            return;
        }

        $state->fired = true;
        $callback();
    });

    return $state;
}

it('preserves a concurrent addition and removal across home public and network rebuild publication', function () {
    // Home: a viewer with a cached feed that already holds two members. A
    // rebuild is forced while, mid-flight, one live post is added and one of
    // the cached members is removed. The published snapshot must not erase the
    // live addition nor resurrect the removed member.
    $viewer = User::factory()->create();
    $viewer->refresh();
    $pid = $viewer->profile_id;

    $author = User::factory()->create();
    $author->refresh();
    Follower::create(['profile_id' => $pid, 'following_id' => $author->profile_id]);

    $kept = raceLocalStatus($author->profile_id, now()->subMinutes(30)->toDateTimeString());
    $removed = raceLocalStatus($author->profile_id, now()->subMinutes(40)->toDateTimeString());
    $concurrent = raceLocalStatus($author->profile_id, now()->subMinutes(1)->toDateTimeString());

    HomeTimelineService::add($pid, $kept->id);
    HomeTimelineService::add($pid, $removed->id);

    $homeHook = onSnapshotSelected(function () use ($pid, $concurrent, $removed) {
        HomeTimelineService::add($pid, $concurrent->id);
        HomeTimelineService::rem($pid, $removed->id);
    });

    HomeTimelineService::warmCache($pid, true, 400);

    expect($homeHook->fired)->toBeTrue();

    $homeMembers = raceOrder(HomeTimelineService::CACHE_KEY.$pid);
    expect($homeMembers)->toContain((int) $concurrent->id);
    expect($homeMembers)->not->toContain((int) $removed->id);

    // Public: same window, same expectations against the instance-wide set.
    $local = User::factory()->create();
    $local->refresh();

    $pubKept = raceLocalStatus($local->profile_id, now()->subMinutes(30)->toDateTimeString());
    $pubRemoved = raceLocalStatus($local->profile_id, now()->subMinutes(40)->toDateTimeString());
    $pubConcurrent = raceLocalStatus($local->profile_id, now()->subMinutes(1)->toDateTimeString());

    PublicTimelineService::add($pubKept->id);
    PublicTimelineService::add($pubRemoved->id);

    $pubHook = onSnapshotSelected(function () use ($pubConcurrent, $pubRemoved) {
        PublicTimelineService::add($pubConcurrent->id);
        PublicTimelineService::rem($pubRemoved->id);
    });

    PublicTimelineService::warmCache(true, 400);

    expect($pubHook->fired)->toBeTrue();

    $pubMembers = raceOrder(PublicTimelineService::CACHE_KEY);
    expect($pubMembers)->toContain((int) $pubConcurrent->id);
    expect($pubMembers)->not->toContain((int) $pubRemoved->id);

    // Network: same window against the network set.
    config(['instance.timeline.network.cache_dropoff' => 400]);

    $netKept = raceRemoteStatus('remote.example', now()->subMinutes(30)->toDateTimeString());
    $netRemoved = raceRemoteStatus('remote.example', now()->subMinutes(40)->toDateTimeString());
    $netConcurrent = raceRemoteStatus('remote.example', now()->subMinutes(1)->toDateTimeString());

    NetworkTimelineService::add($netKept->id);
    NetworkTimelineService::add($netRemoved->id);

    $netHook = onSnapshotSelected(function () use ($netConcurrent, $netRemoved) {
        NetworkTimelineService::add($netConcurrent->id);
        NetworkTimelineService::rem($netRemoved->id);
    });

    NetworkTimelineService::warmCache(true, 400);

    expect($netHook->fired)->toBeTrue();

    $netMembers = raceOrder(NetworkTimelineService::CACHE_KEY);
    expect($netMembers)->toContain((int) $netConcurrent->id);
    expect($netMembers)->not->toContain((int) $netRemoved->id);
});

it('keeps the network live key complete until one final publication', function () {
    config(['instance.timeline.network.cache_dropoff' => 400]);

    // Seed a complete, known old network set. A reader hitting the feed at any
    // instant during the rebuild must see this entire set or the entire new
    // set, never a shrunken or empty one.
    $old = collect(range(0, 4))->map(function ($i) {
        return raceRemoteStatus('remote.example', now()->subMinutes(10 + $i)->toDateTimeString());
    });

    foreach ($old as $status) {
        NetworkTimelineService::add($status->id);
    }

    $oldCount = NetworkTimelineService::count();
    expect($oldCount)->toBe(5);

    // Observe cardinality at the publication window: after the rebuild has read
    // its snapshot and is about to repopulate. A complete feed is never torn
    // down before a finished replacement exists, so the observed count must
    // still be the full old set, not zero or a partial build.
    $observed = null;
    $hook = onSnapshotSelected(function () use (&$observed) {
        $observed = NetworkTimelineService::count();
    });

    NetworkTimelineService::warmCache(true, 400);

    expect($hook->fired)->toBeTrue();
    expect($observed)->not->toBeNull();
    expect($observed)->toBe($oldCount);

    // After publication the set is complete and nonempty.
    expect(NetworkTimelineService::count())->toBeGreaterThan(0);
});

it('serializes two concurrent local and network cold-start warms', function () {
    // Local: a cold feed with eligible content. Two back-to-back cold reads
    // must warm exactly once; the second read sees the warmed set and does not
    // launch a second rebuild. Both responses are well-formed and successful.
    $viewer = User::factory()->create();
    $viewer->refresh();

    $author = User::factory()->create();
    $author->refresh();

    raceLocalStatus($author->profile_id, now()->subMinutes(5)->toDateTimeString());
    raceLocalStatus($author->profile_id, now()->subMinutes(9)->toDateTimeString());

    expect(PublicTimelineService::count())->toBe(0);

    $localWarms = 0;
    DB::listen(function ($query) use (&$localWarms) {
        if (stripos($query->sql, 'statuses') !== false
            && stripos($query->sql, 'in_reply_to_id') !== false
            && stripos($query->sql, 'reblog_of_id') !== false
            && stripos($query->sql, 'uri') !== false) {
            $localWarms++;
        }
    });

    $first = $this->actingAs($viewer)->getJson('/api/pixelfed/v1/timelines/public?limit=5');
    $first->assertOk();

    $second = $this->actingAs($viewer)->getJson('/api/pixelfed/v1/timelines/public?limit=5');
    $second->assertOk();

    expect($localWarms)->toBe(1);
    expect($first->json())->toBeArray();
    expect($second->json())->toBeArray();
    expect(Redis::keys(PublicTimelineService::CACHE_KEY.':rebuild:*'))->toBeEmpty();

    // Network: same contract against the network cold path.
    config(['instance.timeline.network.cache_dropoff' => 400]);
    Redis::del(NetworkTimelineService::CACHE_KEY);
    Cache::flush();

    raceRemoteStatus('remote.example', now()->subMinutes(5)->toDateTimeString());
    raceRemoteStatus('remote.example', now()->subMinutes(9)->toDateTimeString());

    expect(NetworkTimelineService::count())->toBe(0);

    $netWarms = 0;
    DB::listen(function ($query) use (&$netWarms) {
        if (stripos($query->sql, 'statuses') !== false
            && stripos($query->sql, 'uri') !== false
            && stripos($query->sql, 'is not null') !== false) {
            $netWarms++;
        }
    });

    $netFirst = $this->actingAs($viewer)->getJson('/api/pixelfed/v1/timelines/network?limit=5');
    $netFirst->assertOk();

    $netSecond = $this->actingAs($viewer)->getJson('/api/pixelfed/v1/timelines/network?limit=5');
    $netSecond->assertOk();

    expect($netWarms)->toBe(1);
    expect($netFirst->json())->toBeArray();
    expect($netSecond->json())->toBeArray();
    expect(Redis::keys(NetworkTimelineService::CACHE_KEY.':rebuild:*'))->toBeEmpty();
});
