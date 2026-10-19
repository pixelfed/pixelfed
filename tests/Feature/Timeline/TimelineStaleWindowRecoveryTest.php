<?php

use App\Models\Follower;
use App\Models\Profile;
use App\Models\Status;
use App\Models\User;
use App\Services\AccountService;
use App\Services\HomeTimelineService;
use App\Services\NetworkTimelineService;
use App\Services\PublicTimelineService;
use App\Services\StatusService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Laravel\Passport\Passport;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Stale-window continuation and recovery
|--------------------------------------------------------------------------
|
| A cached set can stay nonempty while every member fails to resolve: the
| author was deleted, the row was removed, or hydration throws. A nonzero
| count then suppresses a cold warm, the padded/overfetch window hydrates to
| nothing, and an older entry that is still eligible is stranded below the
| stale block. These walks seed unresolvable members above an eligible entry,
| overflow the fixed overfetch for the public feeds, and assert the eligible
| entry is still reachable, that no response carries a null account, that the
| stale members are cleared by the recovery path, and that the eligible entry
| ends up present exactly once in Redis display order.
|
*/

const STALE_LOCAL_CHECK = 'api:v1:timelines:public:cache_check';
const STALE_NETWORK_CHECK = 'api:v1:timelines:network:cache_check';

beforeEach(function () {
    config(['federation.network_timeline' => true]);
    config(['instance.hide_nsfw_on_public_feeds' => false]);
    Redis::flushdb();
    Cache::flush();
});

afterEach(function () {
    Redis::flushdb();
    Cache::flush();
});

function staleNextCursor(?string $link): ?string
{
    if (! $link) {
        return null;
    }

    if (preg_match('/max_id=(\d+)>; rel="next"/', $link, $m)) {
        return $m[1];
    }

    return null;
}

function staleHasNextRel(?string $link): bool
{
    return $link !== null && str_contains($link, 'rel="next"');
}

function staleDisplayOrder(string $key): array
{
    return collect(Redis::zrevrange($key, 0, -1))
        ->map(fn ($v) => (string) $v)
        ->values()
        ->all();
}

/**
 * Seed a run of members that can never hydrate: ids with no backing row at
 * all. Scores are placed explicitly and strictly above $aboveScore so the
 * block sits at the top of display order, ahead of the eligible entry. Ids
 * start from a high base so they never collide with a real status id.
 */
function seedUnresolvableMembers(string $key, int $count, int $aboveScore): array
{
    $ids = [];

    for ($i = 0; $i < $count; $i++) {
        $id = 9100000000000000 + $i;
        $score = $aboveScore + 1 + $i;
        Redis::zadd($key, $score, $id);
        $ids[] = (string) $id;
    }

    return $ids;
}

/**
 * A resolvable remote public photo whose score is driven by an explicit date.
 * Resolving it primes the StatusService caches the read path reads from.
 */
function stalePublicStatus(int $authorPid, Carbon $createdAt, bool $remote): Status
{
    $status = Status::factory()->create([
        'profile_id' => $authorPid,
        'type' => 'photo',
        'scope' => 'public',
        'visibility' => 'public',
        'local' => ! $remote,
        'is_nsfw' => false,
        'in_reply_to_id' => null,
        'reblog_of_id' => null,
        'uri' => $remote ? 'https://remote.example/p/'.bin2hex(random_bytes(6)) : null,
        'created_at' => $createdAt,
    ]);

    StatusService::get($status->id, false);
    StatusService::getMastodon($status->id, false);

    return $status;
}

it('recovers a home timeline whose nonempty cache contains only unresolvable members', function () {
    $viewer = User::factory()->create();
    $viewer->refresh();
    $pid = $viewer->profile_id;

    $author = User::factory()->create();
    $author->refresh();
    Follower::create(['profile_id' => $pid, 'following_id' => $author->profile_id]);

    // An eligible followed post lives in SQL and is primed in the hydration
    // caches, but it is intentionally never added to the cached set.
    $eligible = Status::factory()->create([
        'profile_id' => $author->profile_id,
        'type' => 'photo',
        'scope' => 'public',
        'visibility' => 'public',
        'in_reply_to_id' => null,
        'reblog_of_id' => null,
        'local' => true,
        'uri' => null,
        'created_at' => now()->subMinutes(30),
    ]);
    StatusService::get($eligible->id, false);
    StatusService::getMastodon($eligible->id, false);

    // The cached set is nonempty but every member is unresolvable and ranks
    // above where the eligible post would sit.
    $key = HomeTimelineService::CACHE_KEY.$pid;
    $eligibleScore = (int) HomeTimelineService::statusScore($eligible->id);
    $stale = seedUnresolvableMembers($key, 6, $eligibleScore + 100);

    expect(HomeTimelineService::count($pid))->toBeGreaterThan(0);

    Passport::actingAs($viewer, ['read']);

    $resp = $this->getJson('/api/v1/timelines/home?limit=10')->assertOk();
    $ids = collect($resp->json())->pluck('id')->map(fn ($id) => (string) $id)->all();

    // No response row carries a null account.
    foreach ($resp->json() as $entry) {
        expect($entry['account'] ?? null)->not->toBeNull();
    }

    // The eligible post is reachable despite the stale block that preceded it.
    expect($ids)->toContain((string) $eligible->id);

    // The stale members are gone from the cached set, and the eligible post
    // appears exactly once in display order.
    $order = staleDisplayOrder($key);
    foreach ($stale as $staleId) {
        expect($order)->not->toContain($staleId);
    }
    expect(collect($order)->filter(fn ($id) => $id === (string) $eligible->id)->count())->toBe(1);
});

it('continues the local timeline past a stale window larger than endpoint padding', function () {
    $viewer = User::factory()->create();
    $viewer->refresh();

    $author = User::factory()->create();
    $author->refresh();

    $eligible = stalePublicStatus($author->profile_id, now()->subMinutes(300), false);

    $key = PublicTimelineService::CACHE_KEY;
    PublicTimelineService::add($eligible->id);
    Cache::put(STALE_LOCAL_CHECK, 1, 3600);

    // More unresolvable members than the limit + 5 overfetch, all ranked above
    // the eligible entry, so a single padded fetch cannot clear them and still
    // carry the eligible post on the first page.
    $eligibleScore = (int) PublicTimelineService::statusScore($eligible->id);
    $stale = seedUnresolvableMembers($key, 2 + 5 + 6, $eligibleScore + 100);

    expect(count($stale))->toBeGreaterThan(2 + 5);
    expect(staleDisplayOrder($key))->toContain((string) $eligible->id);

    Passport::actingAs($viewer, ['read']);

    $walk = walkStaleFeed($this, '/api/v1/timelines/public?_pe=1&local=1&limit=2');

    // The eligible entry is reached, exactly once, and no cursor repeats.
    expect($walk['served'])->toContain((string) $eligible->id);
    expect(collect($walk['served'])->filter(fn ($id) => $id === (string) $eligible->id)->count())->toBe(1);
    expect($walk['cursors'])->toBe(collect($walk['cursors'])->unique()->values()->all());

    // No served row ever carried a null account.
    expect($walk['nullAccountSeen'])->toBeFalse();

    // The stale members are cleared by the recovery path and the eligible
    // entry remains present exactly once in display order.
    $order = staleDisplayOrder($key);
    foreach ($stale as $staleId) {
        expect($order)->not->toContain($staleId);
    }
    expect(collect($order)->filter(fn ($id) => $id === (string) $eligible->id)->count())->toBe(1);
});

it('continues the network timeline past deleted-account members larger than endpoint padding', function () {
    $viewer = User::factory()->create();
    $viewer->refresh();

    $liveAuthor = Profile::factory()->create(['user_id' => null, 'domain' => 'remote.example']);
    $eligible = stalePublicStatus($liveAuthor->id, now()->subMinutes(300), true);

    $key = NetworkTimelineService::CACHE_KEY;
    NetworkTimelineService::add($eligible->id);
    Cache::put(STALE_NETWORK_CHECK, 1, 3600);

    // Deleted-account members: real remote statuses whose author is marked
    // deleted so hydration drops them, plus unresolvable ids with no row.
    // Together they exceed the limit + 5 overfetch and rank above the eligible
    // entry.
    $eligibleScore = (int) NetworkTimelineService::statusScore($eligible->id);

    $deletedMembers = [];
    for ($i = 0; $i < 8; $i++) {
        $deletedAuthor = Profile::factory()->create(['user_id' => null, 'domain' => 'remote.example']);
        $status = stalePublicStatus($deletedAuthor->id, now()->subMinutes(200 - $i), true);
        Redis::zadd($key, $eligibleScore + 200 + $i, $status->id);

        $deletedAuthor->status = 'delete';
        $deletedAuthor->save();
        AccountService::del($deletedAuthor->id);
        StatusService::del($status->id);

        $deletedMembers[] = (string) $status->id;
    }

    $unresolvable = seedUnresolvableMembers($key, 6, $eligibleScore + 400);
    $stale = array_merge($deletedMembers, $unresolvable);

    expect(count($stale))->toBeGreaterThan(2 + 5);
    expect(staleDisplayOrder($key))->toContain((string) $eligible->id);

    Passport::actingAs($viewer, ['read']);

    $walk = walkStaleFeed($this, '/api/v1/timelines/public?_pe=1&remote=1&limit=2');

    expect($walk['served'])->toContain((string) $eligible->id);
    expect(collect($walk['served'])->filter(fn ($id) => $id === (string) $eligible->id)->count())->toBe(1);
    expect($walk['cursors'])->toBe(collect($walk['cursors'])->unique()->values()->all());
    expect($walk['nullAccountSeen'])->toBeFalse();

    $order = staleDisplayOrder($key);
    foreach ($stale as $staleId) {
        expect($order)->not->toContain($staleId);
    }
    expect(collect($order)->filter(fn ($id) => $id === (string) $eligible->id)->count())->toBe(1);
});

/**
 * Walk a public/network feed by following rel="next". Records every served id
 * in order, each emitted cursor, whether any served row exposed a null
 * account, and the number of trailing empty responses so a walk cannot spin
 * forever without surfacing the stranded entry.
 */
function walkStaleFeed($test, string $base): array
{
    $cursor = null;
    $served = [];
    $cursors = [];
    $trailingEmpty = 0;
    $nullAccountSeen = false;

    for ($page = 0; $page < 80; $page++) {
        $url = $cursor ? $base.'&max_id='.$cursor : $base;
        $resp = $test->getJson($url)->assertOk();
        $json = $resp->json();

        foreach ($json as $entry) {
            if (($entry['account'] ?? null) === null) {
                $nullAccountSeen = true;
            }
        }

        $ids = collect($json)->pluck('id')->map(fn ($id) => (string) $id)->all();

        $trailingEmpty = count($ids) === 0 ? $trailingEmpty + 1 : 0;

        foreach ($ids as $id) {
            $served[] = $id;
        }

        $link = $resp->headers->get('Link');

        if (! staleHasNextRel($link)) {
            break;
        }

        $next = staleNextCursor($link);

        if ($next === null || $next === $cursor) {
            break;
        }

        if ($trailingEmpty > 2) {
            break;
        }

        $cursors[] = $next;
        $cursor = $next;
    }

    return [
        'served' => $served,
        'cursors' => $cursors,
        'trailingEmpty' => $trailingEmpty,
        'nullAccountSeen' => $nullAccountSeen,
    ];
}
