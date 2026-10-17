<?php

use App\Models\Follower;
use App\Models\Status;
use App\Models\User;
use App\Services\HomeTimelineService;
use App\Services\NetworkTimelineService;
use App\Services\PublicTimelineService;
use App\Services\StatusService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Rebuild failure preservation
|--------------------------------------------------------------------------
|
| A warm rebuild should never be able to destroy a feed that is already
| serving readers. The home and public services build into a temporary
| ":rebuild:" key and only swap it onto the live key with a single atomic
| rename, so a crash mid-population or mid-publication must leave the live
| key, its ordering, and (for home) its lifetime exactly as they were, and
| must not strand a temporary key. A later clean retry then publishes and
| leaves nothing but the live key.
|
| These walks inject one-shot failures into the underlying client: the first
| temporary write during population, and the rename that publishes the set.
| A known canonical set is seeded first and asserted byte-for-byte after each
| failure, so any observable loss, reordering, lifetime damage, or leaked
| temporary key is a reportable defect rather than tolerated behaviour.
|
*/

/**
 * Wraps the real redis facade root and raises one exception the first time a
 * matched command runs, then steps aside so every later call behaves normally.
 * Everything else is forwarded untouched to the live connection, so the
 * canonical set stays readable throughout.
 */
class OneShotFailingRedis
{
    public bool $tripped = false;

    public function __construct(
        private $inner,
        private $matcher,
        private string $message = 'injected redis failure'
    ) {}

    public function __call($method, $parameters)
    {
        if (! $this->tripped && ($this->matcher)($method, $parameters)) {
            $this->tripped = true;

            throw new RedisException($this->message);
        }

        return $this->inner->{$method}(...$parameters);
    }
}

/**
 * Swap the redis facade root for a one-shot failing decorator and return it so
 * the test can confirm the failure actually fired.
 */
function injectOneShotRedisFailure(callable $matcher): OneShotFailingRedis
{
    $decorator = new OneShotFailingRedis(Redis::getFacadeRoot(), $matcher);
    Redis::swap($decorator);

    return $decorator;
}

/**
 * The real, unprefixed names of every live ":rebuild:" temporary key.
 */
function rebuildKeys(string $liveKey): array
{
    $prefix = config('database.redis.options.prefix', '');

    return collect(Redis::keys($liveKey.':rebuild:*'))
        ->map(fn ($k) => $prefix === '' ? $k : str_replace($prefix, '', $k))
        ->values()
        ->all();
}

/**
 * The ordered sorted-set members, newest-first, as raw string members so the
 * comparison is byte-for-byte rather than coerced.
 */
function canonicalMembers(string $key): array
{
    return Redis::zrevrange($key, 0, -1) ?: [];
}

function rebuildFailureLocalStatus(int $profileId, string $createdAt): Status
{
    $status = Status::factory()->create([
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
    ]);

    StatusService::get($status->id, false);

    return $status;
}

function rebuildFailureRemoteStatus(int $profileId, string $createdAt): Status
{
    $status = Status::factory()->create([
        'profile_id' => $profileId,
        'type' => 'photo',
        'scope' => 'public',
        'visibility' => 'public',
        'local' => false,
        'in_reply_to_id' => null,
        'reblog_of_id' => null,
        'is_nsfw' => false,
        'uri' => 'https://remote.example/p/'.uniqid(),
        'created_at' => $createdAt,
    ]);

    StatusService::get($status->id, false);

    return $status;
}

beforeEach(function () {
    config(['instance.hide_nsfw_on_public_feeds' => false]);
    config(['instance.timeline.network.max_hours_old' => 2160]);
    Redis::flushdb();
    Cache::flush();
});

afterEach(function () {
    // Restore the real facade root in case a test left the decorator in place.
    Redis::clearResolvedInstances();
    Redis::flushdb();
    Cache::flush();
});

it('preserves the home cache and ttl when rebuild population or publication fails', function () {
    $viewer = User::factory()->create();
    $viewer->refresh();
    $pid = $viewer->profile_id;

    $author = User::factory()->create();
    $author->refresh();
    Follower::create(['profile_id' => $pid, 'following_id' => $author->profile_id]);

    $liveKey = HomeTimelineService::CACHE_KEY.$pid;

    // Seed a known canonical feed through a first clean warm. This is the set
    // readers depend on and it must outlive every failed rebuild unchanged.
    $base = now()->subDay()->startOfMinute();
    foreach (range(0, 4) as $i) {
        rebuildFailureLocalStatus($author->profile_id, $base->copy()->addSeconds($i * 10)->toDateTimeString());
    }
    HomeTimelineService::warmCache($pid, true, 100);

    $canonical = canonicalMembers($liveKey);
    expect($canonical)->not->toBeEmpty();
    expect(rebuildKeys($liveKey))->toBeEmpty();

    // Give the live key a known, aged lifetime so a damaged rename or expire
    // during a failed rebuild would be visible as a changed or cleared ttl.
    Redis::expire($liveKey, 500);
    $canonicalTtl = Redis::ttl($liveKey);
    expect($canonicalTtl)->toBeGreaterThan(0);

    // Add more eligible content so the next warm has real work to publish.
    foreach (range(0, 2) as $i) {
        rebuildFailureLocalStatus($author->profile_id, $base->copy()->addSeconds(1000 + $i * 10)->toDateTimeString());
    }

    // Failure one: blow up on the first temporary write during population.
    $popFail = injectOneShotRedisFailure(function ($method, $params) {
        return strtolower($method) === 'zadd'
            && isset($params[0])
            && str_contains((string) $params[0], ':rebuild:');
    });

    try {
        HomeTimelineService::warmCache($pid, true, 100);
    } catch (Throwable $e) {
        // A rebuild crash must not take the live feed with it.
    }

    Redis::clearResolvedInstances();

    expect($popFail->tripped)->toBeTrue();
    expect(canonicalMembers($liveKey))->toBe($canonical);
    $ttlAfterPop = Redis::ttl($liveKey);
    expect($ttlAfterPop)->toBeGreaterThan(0);
    expect($ttlAfterPop)->toBeLessThanOrEqual(HomeTimelineService::CACHE_TTL);
    foreach (rebuildKeys($liveKey) as $leaked) {
        expect(Redis::ttl($leaked))->toBeGreaterThan(0);
    }
    expect(HomeTimelineService::get($pid, 0, -1))->not->toBeEmpty();

    // Failure two: let population finish but blow up on the atomic publish.
    $pubFail = injectOneShotRedisFailure(function ($method, $params) {
        return strtolower($method) === 'rename';
    });

    try {
        HomeTimelineService::warmCache($pid, true, 100);
    } catch (Throwable $e) {
        // A failed publish must leave the previous published set intact.
    }

    Redis::clearResolvedInstances();

    expect($pubFail->tripped)->toBeTrue();
    expect(canonicalMembers($liveKey))->toBe($canonical);
    $ttlAfterPub = Redis::ttl($liveKey);
    expect($ttlAfterPub)->toBeGreaterThan(0);
    expect($ttlAfterPub)->toBeLessThanOrEqual(HomeTimelineService::CACHE_TTL);
    foreach (rebuildKeys($liveKey) as $leaked) {
        expect(Redis::ttl($leaked))->toBeGreaterThan(0);
    }
    expect(HomeTimelineService::get($pid, 0, -1))->not->toBeEmpty();

    // A clean retry now publishes and leaves only the live key, freshly
    // stamped with a bounded lifetime.
    HomeTimelineService::warmCache($pid, true, 100);

    expect(rebuildKeys($liveKey))->toBeEmpty();
    expect(HomeTimelineService::get($pid, 0, -1))->not->toBeEmpty();
    $ttlAfterRetry = Redis::ttl($liveKey);
    expect($ttlAfterRetry)->toBeGreaterThan(0);
    expect($ttlAfterRetry)->toBeLessThanOrEqual(HomeTimelineService::CACHE_TTL);
});

it('preserves the public cache when rebuild population or publication fails', function () {
    $author = User::factory()->create();
    $author->refresh();

    $liveKey = PublicTimelineService::CACHE_KEY;

    // Seed a known canonical local feed through a first clean warm.
    $base = now()->subDay()->startOfMinute();
    foreach (range(0, 4) as $i) {
        rebuildFailureLocalStatus($author->profile_id, $base->copy()->addSeconds($i * 10)->toDateTimeString());
    }
    PublicTimelineService::warmCache(true, 400);

    $canonical = canonicalMembers($liveKey);
    expect($canonical)->not->toBeEmpty();
    expect(rebuildKeys($liveKey))->toBeEmpty();

    // More eligible content so the next rebuild has members to publish.
    foreach (range(0, 2) as $i) {
        rebuildFailureLocalStatus($author->profile_id, $base->copy()->addSeconds(1000 + $i * 10)->toDateTimeString());
    }

    // Failure one: first temporary write during population.
    $popFail = injectOneShotRedisFailure(function ($method, $params) {
        return strtolower($method) === 'zadd'
            && isset($params[0])
            && str_contains((string) $params[0], ':rebuild:');
    });

    try {
        PublicTimelineService::warmCache(true, 400);
    } catch (Throwable $e) {
        // A rebuild crash must not take the live feed with it.
    }

    Redis::clearResolvedInstances();

    expect($popFail->tripped)->toBeTrue();
    expect(canonicalMembers($liveKey))->toBe($canonical);
    foreach (rebuildKeys($liveKey) as $leaked) {
        expect(Redis::ttl($leaked))->toBeGreaterThan(0);
    }
    expect(PublicTimelineService::count())->toBe(count($canonical));

    // Failure two: the atomic publish.
    $pubFail = injectOneShotRedisFailure(function ($method, $params) {
        return strtolower($method) === 'rename';
    });

    try {
        PublicTimelineService::warmCache(true, 400);
    } catch (Throwable $e) {
        // A failed publish must leave the previous published set intact.
    }

    Redis::clearResolvedInstances();

    expect($pubFail->tripped)->toBeTrue();
    expect(canonicalMembers($liveKey))->toBe($canonical);
    foreach (rebuildKeys($liveKey) as $leaked) {
        expect(Redis::ttl($leaked))->toBeGreaterThan(0);
    }
    expect(PublicTimelineService::count())->toBe(count($canonical));

    // A clean retry publishes and leaves only the live key.
    PublicTimelineService::warmCache(true, 400);

    expect(rebuildKeys($liveKey))->toBeEmpty();
    expect(PublicTimelineService::count())->toBeGreaterThan(0);
});

it('preserves the complete network cache when rebuild population fails', function () {
    config(['instance.timeline.network.cache_dropoff' => 50]);

    $remote = User::factory()->create();
    $remote->refresh();

    $liveKey = NetworkTimelineService::CACHE_KEY;

    // Seed a known canonical network feed through a first clean warm.
    $base = now()->subDay()->startOfMinute();
    foreach (range(0, 4) as $i) {
        rebuildFailureRemoteStatus($remote->profile_id, $base->copy()->addSeconds($i * 10)->toDateTimeString());
    }
    NetworkTimelineService::warmCache(true, 50);

    $canonical = canonicalMembers($liveKey);
    expect($canonical)->not->toBeEmpty();
    expect(rebuildKeys($liveKey))->toBeEmpty();

    // More eligible remote content so the next rebuild has work to do.
    foreach (range(0, 2) as $i) {
        rebuildFailureRemoteStatus($remote->profile_id, $base->copy()->addSeconds(1000 + $i * 10)->toDateTimeString());
    }

    // Blow up on the first population write of the rebuild. The network warm
    // builds into a temporary key and publishes with one atomic rename, so a
    // crashed population must leave the previous live set serving untouched.
    $popFail = injectOneShotRedisFailure(function ($method, $params) {
        return strtolower($method) === 'zadd'
            && isset($params[0])
            && str_contains((string) $params[0], ':rebuild:');
    });

    try {
        NetworkTimelineService::warmCache(true, 50);
    } catch (Throwable $e) {
        // A rebuild crash must not take the live feed with it.
    }

    Redis::clearResolvedInstances();

    expect($popFail->tripped)->toBeTrue();

    // Readers must still observe the complete previous set, unchanged.
    expect(canonicalMembers($liveKey))->toBe($canonical);
    expect(NetworkTimelineService::count())->toBe(count($canonical));
    expect(NetworkTimelineService::get(0, -1))->not->toBeEmpty();
    foreach (rebuildKeys($liveKey) as $leaked) {
        expect(Redis::ttl($leaked))->toBeGreaterThan(0);
    }

    // A clean retry publishes and leaves only the live key populated.
    NetworkTimelineService::warmCache(true, 50);

    expect(rebuildKeys($liveKey))->toBeEmpty();
    expect(NetworkTimelineService::count())->toBeGreaterThan(0);
});
