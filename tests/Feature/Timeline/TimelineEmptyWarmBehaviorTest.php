<?php

use App\Jobs\HomeFeedPipeline\FeedInsertPipeline;
use App\Models\Follower;
use App\Models\Status;
use App\Models\User;
use App\Services\HomeTimelineService;
use App\Services\NetworkTimelineService;
use App\Services\PublicTimelineService;
use App\Services\StatusService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Laravel\Passport\Passport;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Empty-warm throttling and active-empty-home delivery
|--------------------------------------------------------------------------
|
| A cold local or network set that warms to nothing must not re-run its
| synchronous rebuild on every subsequent read. The correct contract: one
| synchronous warm per feed holds for the length of an empty-warm interval,
| the feed keeps answering with a stable empty success response, and only
| after the interval lapses or a mutation invalidates the guard does a second
| warm become eligible. These walks drive both the Mastodon-compatible and
| the internal local/network routes, count the status-table rebuild query,
| advance the clock past the interval, and apply the invalidating mutation.
|
| The home feed has a different shape: it never materialises a set for a
| viewer who has not loaded one, yet a viewer with an active (already loaded)
| feed that happens to be empty must see a freshly fanned-out followed status
| immediately, while a genuine never-loaded viewer still gains no key.
|
*/

// The post-rebuild query that PublicTimelineService::warmCache issues. It is
// the only read that selects exactly these three columns from the table, so a
// listener can count rebuilds without touching the production path.
function localWarmQueryCount(callable $run): int
{
    $count = 0;
    DB::listen(function ($query) use (&$count) {
        $sql = strtolower($query->sql);
        if (str_contains($sql, 'from "statuses"')
            && str_contains($sql, '"uri" is null')
            && str_contains($sql, '"created_at"')
            && str_contains($sql, 'order by "id" desc')) {
            $count++;
        }
    });

    $run();

    return $count;
}

// NetworkTimelineService::warmCache selects not-null-uri rows from the
// statuses table. No read path on the network endpoint issues that exact
// selection, so counting it isolates the rebuild.
function networkWarmQueryCount(callable $run): int
{
    $count = 0;
    DB::listen(function ($query) use (&$count) {
        $sql = strtolower($query->sql);
        if (str_contains($sql, 'from "statuses"')
            && str_contains($sql, '"uri" is not null')
            && str_contains($sql, '"in_reply_to_id" is null')) {
            $count++;
        }
    });

    $run();

    return $count;
}

beforeEach(function () {
    config(['federation.network_timeline' => true]);
    config(['instance.hide_nsfw_on_public_feeds' => false]);
    config(['instance.timeline.network.cache_dropoff' => 100]);
    config(['instance.timeline.network.max_hours_old' => 2160]);
    Redis::flushdb();
    Cache::flush();
});

afterEach(function () {
    Redis::flushdb();
    Cache::flush();
    Carbon::setTestNow();
});

it('warms an empty local cache once across repeated endpoint reads inside the empty-warm interval', function () {
    // No eligible local statuses exist, so every warm resolves to an empty set
    // and the count stays zero between reads.
    $viewer = User::factory()->create();
    $viewer->refresh();

    expect(PublicTimelineService::count())->toBe(0);

    $masto = 0;
    $internal = 0;

    $warms = localWarmQueryCount(function () use ($viewer, &$masto, &$internal) {
        // Mastodon-compatible local route.
        Passport::actingAs($viewer, ['read']);
        for ($i = 0; $i < 3; $i++) {
            $res = $this->getJson('/api/v1/timelines/public?local=1&limit=10')->assertOk();
            expect($res->json())->toBe([]);
            $masto++;
        }

        // Drop the token guard before switching to session auth for the
        // internal route so the two mechanisms do not collide.
        $this->app['auth']->forgetGuards();

        // Internal local route backed by the same cached set.
        for ($i = 0; $i < 3; $i++) {
            $res = $this->actingAs($viewer, 'web')
                ->getJson('/api/pixelfed/v1/timelines/public?limit=10')
                ->assertOk();
            expect($res->json())->toBe([]);
            $internal++;
        }
    });

    // Six empty reads across both routes, yet the synchronous rebuild must run
    // exactly once while the empty-warm interval is live.
    expect($masto + $internal)->toBe(6);
    expect($warms)->toBe(1);
    expect(PublicTimelineService::count())->toBe(0);
});

it('warms an empty network cache once across repeated endpoint reads inside the empty-warm interval', function () {
    $viewer = User::factory()->create();
    $viewer->refresh();

    expect(NetworkTimelineService::count())->toBe(0);

    $masto = 0;
    $internal = 0;

    $warms = networkWarmQueryCount(function () use ($viewer, &$masto, &$internal) {
        // Mastodon-compatible network route (remote arm of timelinePublic).
        Passport::actingAs($viewer, ['read']);
        for ($i = 0; $i < 3; $i++) {
            $res = $this->getJson('/api/v1/timelines/public?remote=1&limit=10')->assertOk();
            expect($res->json())->toBe([]);
            $masto++;
        }

        // Drop the token guard before switching to session auth for the
        // internal route so the two mechanisms do not collide.
        $this->app['auth']->forgetGuards();

        // Internal network route backed by the same cached set.
        for ($i = 0; $i < 3; $i++) {
            $res = $this->actingAs($viewer, 'web')
                ->getJson('/api/pixelfed/v1/timelines/network?limit=10')
                ->assertOk();
            expect($res->json())->toBe([]);
            $internal++;
        }
    });

    expect($masto + $internal)->toBe(6);
    expect($warms)->toBe(1);
    expect(NetworkTimelineService::count())->toBe(0);
});

it('allows one new empty warm after interval expiry or fanout invalidation', function () {
    $viewer = User::factory()->create();
    $viewer->refresh();

    Carbon::setTestNow(now());

    // First empty read warms the local feed once.
    $firstLocal = localWarmQueryCount(function () use ($viewer) {
        Passport::actingAs($viewer, ['read']);
        $this->getJson('/api/v1/timelines/public?local=1&limit=10')->assertOk();
    });
    expect($firstLocal)->toBe(1);

    // A second read inside the interval must not warm again.
    $insideLocal = localWarmQueryCount(function () {
        $this->getJson('/api/v1/timelines/public?local=1&limit=10')->assertOk();
    });
    expect($insideLocal)->toBe(0);

    // Push the clock beyond the empty-warm interval; a fresh warm becomes
    // eligible again because the guard has lapsed.
    Carbon::setTestNow(now()->addHours(4));

    $afterExpiryLocal = localWarmQueryCount(function () {
        $this->getJson('/api/v1/timelines/public?local=1&limit=10')->assertOk();
    });
    expect($afterExpiryLocal)->toBe(1);

    // The network feed gets the same treatment, but the second warm is unlocked
    // by an invalidating mutation rather than the clock: adding then removing a
    // member clears the guard that suppresses repeat empty warms.
    Carbon::setTestNow(now());
    $this->app['auth']->forgetGuards();

    $firstNetwork = networkWarmQueryCount(function () use ($viewer) {
        $this->actingAs($viewer, 'web')
            ->getJson('/api/pixelfed/v1/timelines/network?limit=10')
            ->assertOk();
    });
    expect($firstNetwork)->toBe(1);

    $insideNetwork = networkWarmQueryCount(function () use ($viewer) {
        $this->actingAs($viewer, 'web')
            ->getJson('/api/pixelfed/v1/timelines/network?limit=10')
            ->assertOk();
    });
    expect($insideNetwork)->toBe(0);

    // Invalidate the empty-warm guard with a real mutation on the set.
    NetworkTimelineService::add(999_000_000_000_000_001);
    NetworkTimelineService::rem(999_000_000_000_000_001);
    expect(NetworkTimelineService::count())->toBe(0);

    $afterInvalidation = networkWarmQueryCount(function () use ($viewer) {
        $this->actingAs($viewer, 'web')
            ->getJson('/api/pixelfed/v1/timelines/network?limit=10')
            ->assertOk();
    });
    expect($afterInvalidation)->toBe(1);
});

it('delivers immediate fanout to an active empty home feed without creating a never-loaded feed', function () {
    $author = User::factory()->create();
    $author->refresh();

    // An active viewer who has loaded a home feed before: the key exists with a
    // placeholder member, representing a feed that is warm but presently empty
    // of eligible content the viewer cares about.
    $active = User::factory()->create();
    $active->refresh();
    Follower::create(['profile_id' => $active->profile_id, 'following_id' => $author->profile_id]);
    HomeTimelineService::add($active->profile_id, 1);
    HomeTimelineService::rem($active->profile_id, 1);
    // Re-materialise the key as genuinely active-but-empty via a sentinel that
    // keeps the sorted set present without contributing a visible status.
    Redis::zadd(HomeTimelineService::CACHE_KEY.$active->profile_id, 0, 'coldboot:sentinel');
    expect(HomeTimelineService::cacheKeyExists($active->profile_id))->toBeTrue();

    // A genuine never-loaded follower: no key at all.
    $neverLoaded = User::factory()->create();
    $neverLoaded->refresh();
    Follower::create(['profile_id' => $neverLoaded->profile_id, 'following_id' => $author->profile_id]);
    expect(HomeTimelineService::cacheKeyExists($neverLoaded->profile_id))->toBeFalse();

    // The active viewer first sees an empty home response (the sentinel carries
    // no resolvable status), then a followed status is published.
    Passport::actingAs($active, ['read']);
    $before = $this->getJson('/api/v1/timelines/home?limit=20')->assertOk()->json();
    expect(collect($before)->pluck('id')->map(fn ($id) => (int) $id)->all())->toBe([]);

    $fresh = Status::factory()->create([
        'profile_id' => $author->profile_id,
        'type' => 'photo',
        'scope' => 'public',
        'visibility' => 'public',
        'local' => true,
        'uri' => null,
        'in_reply_to_id' => null,
        'reblog_of_id' => null,
        'created_at' => now(),
    ]);
    StatusService::get($fresh->id, false);

    (new FeedInsertPipeline($fresh->id, $author->profile_id))->handle();

    // The fanned-out status lands in the active feed immediately and is served
    // on the next read, while the never-loaded viewer still has no feed key.
    $after = $this->getJson('/api/v1/timelines/home?limit=20')->assertOk()->json();
    $afterIds = collect($after)->pluck('id')->map(fn ($id) => (int) $id)->all();

    expect($afterIds)->toContain((int) $fresh->id);
    expect(HomeTimelineService::cacheKeyExists($neverLoaded->profile_id))->toBeFalse();
    expect(Redis::ttl(HomeTimelineService::CACHE_KEY.$neverLoaded->profile_id))->toBe(-2);
});
