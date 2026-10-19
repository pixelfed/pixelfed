<?php

use App\Models\Status;
use App\Models\User;
use App\Services\HomeTimelineService;
use App\Services\NetworkTimelineService;
use App\Services\PublicTimelineService;
use App\Services\StatusService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Redis;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Physical and read-cap invariants
|--------------------------------------------------------------------------
|
| Every cached timeline has a hard ceiling: the home and local sorted sets
| hold at most 400 members, the network set holds at most its configured
| cache_dropoff, and a single read returns at most 100 members regardless of
| the requested count. These tests push each feed past its ceiling through
| the add path, a directly preloaded oversized set, a forced warm, and a
| runtime cap reduction, then assert the surviving cardinality and the exact
| newest-first display order that should remain.
|
| Ties matter at the boundary: when the cap falls inside a group of members
| sharing one second-resolution score, eviction must keep the newest members
| by a deterministic rule. Redis ranks equal scores by member string, so the
| equal-length numeric ids used here evict the numerically smaller id first,
| which is the oldest status in each tied pair.
|
*/

const HOME_CAP = 400;

const PUBLIC_CAP = 400;

/**
 * The ordered sorted-set members, newest-first, as integers.
 */
function cappedOrder(string $key): array
{
    return collect(Redis::zrevrange($key, 0, -1))
        ->map(fn ($id) => (int) $id)
        ->values()
        ->all();
}

/**
 * Persist a local public photo and prime its StatusService cache so the
 * timeline services can resolve a created_at score for it.
 */
function capLocalStatus(int $profileId, string $createdAt, array $overrides = []): Status
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

afterEach(function () {
    Redis::flushdb();
});

it('enforces the exact home and public caps after add oversized preload and warm', function () {
    $home = User::factory()->create();
    $home->refresh();
    $pid = $home->profile_id;

    $local = User::factory()->create();
    $local->refresh();
    $aid = $local->profile_id;

    $homeKey = HomeTimelineService::CACHE_KEY.$pid;
    $publicKey = PublicTimelineService::CACHE_KEY;

    // Add path: one past the cap through the per-add trim. Distinct seconds
    // fix a strict newest-first order, so the single oldest member is the one
    // that must fall out when member 401 arrives.
    $base = now()->subDays(1)->startOfMinute();
    $homeAdds = [];
    for ($i = 0; $i < HOME_CAP + 1; $i++) {
        $status = capLocalStatus($aid, $base->copy()->addSeconds($i)->toDateTimeString());
        HomeTimelineService::add($pid, $status->id);
        $homeAdds[] = (int) $status->id;
    }

    $afterAdd = cappedOrder($homeKey);
    expect(count($afterAdd))->toBe(HOME_CAP);

    // Newest 400 ids survive, newest-first; the first inserted (oldest) is gone.
    $expectedAfterAdd = collect($homeAdds)->sort()->reverse()->take(HOME_CAP)->values()->all();
    expect($afterAdd)->toBe($expectedAfterAdd);
    expect($afterAdd)->not->toContain($homeAdds[0]);

    // Oversized preload followed by one mutation: a set already 25 over the cap
    // must shrink fully to the cap on the next single add, not merely drop one.
    Redis::del($homeKey);
    $preload = [];
    for ($i = 0; $i < HOME_CAP + 24; $i++) {
        $score = 1_000_000 + $i;
        $member = 700_000_000_000_000_000 + $i;
        Redis::zadd($homeKey, $score, $member);
        $preload[] = $member;
    }
    expect(Redis::zcard($homeKey))->toBe(HOME_CAP + 24);

    $mutation = capLocalStatus($aid, $base->copy()->addSeconds(5000)->toDateTimeString());
    HomeTimelineService::add($pid, $mutation->id);

    $afterPreload = cappedOrder($homeKey);
    expect(count($afterPreload))->toBe(HOME_CAP);
    // The fresh mutation has the newest score, so it leads; the 24 oldest
    // preloaded members plus no one else are evicted to reach the cap exactly.
    expect($afterPreload[0])->toBe((int) $mutation->id);
    $survivingPreload = collect($preload)->sort()->reverse()->take(HOME_CAP - 1)->values()->all();
    expect(array_slice($afterPreload, 1))->toBe($survivingPreload);

    // Public add path with a tied-score boundary. The cap lands between two
    // members sharing one second, so eviction must keep the newer-by-rule
    // member and drop the other. Redis breaks the tie on member string, and
    // equal-length numeric ids sort numerically, so the smaller id is evicted.
    $publicAdds = [];
    $pbase = now()->subDays(2)->startOfMinute();
    for ($i = 0; $i < PUBLIC_CAP - 1; $i++) {
        $status = capLocalStatus($aid, $pbase->copy()->addSeconds(100 + $i)->toDateTimeString());
        PublicTimelineService::add($status->id);
        $publicAdds[] = (int) $status->id;
    }

    // Two more statuses at the identical oldest second form the boundary tie.
    $tieSecond = $pbase->copy()->toDateTimeString();
    $tieLow = capLocalStatus($aid, $tieSecond);
    $tieHigh = capLocalStatus($aid, $tieSecond);
    [$tieSmaller, $tieLarger] = $tieLow->id < $tieHigh->id
        ? [(int) $tieLow->id, (int) $tieHigh->id]
        : [(int) $tieHigh->id, (int) $tieLow->id];

    PublicTimelineService::add($tieSmaller);
    PublicTimelineService::add($tieLarger);

    $afterPublic = cappedOrder($publicKey);
    expect(count($afterPublic))->toBe(PUBLIC_CAP);
    // The larger tied id outranks the smaller at equal score, so it is retained
    // as the single oldest survivor and the smaller tied id is the one dropped.
    expect($afterPublic)->toContain($tieLarger);
    expect($afterPublic)->not->toContain($tieSmaller);
    expect(end($afterPublic))->toBe($tieLarger);

    // Forced warm above the physical cap: rebuild from more eligible rows than
    // the cap and assert the persisted set never exceeds it.
    Redis::del($publicKey);
    PublicTimelineService::warmCache(true, PUBLIC_CAP + 25);
    $afterWarm = cappedOrder($publicKey);
    expect(count($afterWarm))->toBe(PUBLIC_CAP);
});

it('enforces the configured network cap after add warm and runtime reduction', function () {
    config(['instance.timeline.network.max_hours_old' => 2160]);
    config(['instance.timeline.network.cache_dropoff' => 5]);

    $key = NetworkTimelineService::CACHE_KEY;

    // Build an existing set larger than the dropoff we will reduce to. Scores
    // are distinct seconds, so survivors are the newest members by publication.
    $base = now()->subDay()->startOfMinute();
    $members = [];
    for ($i = 0; $i < 8; $i++) {
        $score = $base->copy()->addSeconds($i)->timestamp;
        $member = 900_000_000_000_000_000 + $i;
        Redis::zadd($key, $score, $member);
        $members[] = $member;
    }
    expect(Redis::zcard($key))->toBe(8);

    // Reduce the configured cap below the current size. The reduction does not
    // trim retroactively; the next mutation must enforce the new ceiling.
    config(['instance.timeline.network.cache_dropoff' => 3]);

    // The newest member is a real remote status so its score resolves from
    // created_at, landing newer than every preexisting second-scored member.
    $newerStatus = Status::factory()->create([
        'profile_id' => User::factory()->create()->refresh()->profile_id,
        'type' => 'photo',
        'scope' => 'public',
        'visibility' => 'public',
        'local' => false,
        'in_reply_to_id' => null,
        'reblog_of_id' => null,
        'is_nsfw' => false,
        'uri' => 'https://remote.example/p/'.uniqid(),
        'created_at' => $base->copy()->addSeconds(500)->toDateTimeString(),
    ]);
    StatusService::get($newerStatus->id, false);
    $newer = (int) $newerStatus->id;
    NetworkTimelineService::add($newer);

    $afterReduction = cappedOrder($key);
    expect(count($afterReduction))->toBe(3);
    // The newest member leads and the two newest preexisting members follow;
    // everything older is evicted down to the configured cap.
    $expected = collect($members)->sort()->reverse()->take(2)->values()->all();
    expect($afterReduction[0])->toBe($newer);
    expect(array_slice($afterReduction, 1))->toBe($expected);

    // A forced warm must land on the configured cap as well. Three eligible
    // remote statuses against cache_dropoff of 2 keep the two newest.
    Redis::del($key);
    config(['instance.timeline.network.cache_dropoff' => 2]);

    $remote = User::factory()->create();
    $remote->refresh();
    foreach (range(0, 2) as $i) {
        $status = Status::factory()->create([
            'profile_id' => $remote->profile_id,
            'type' => 'photo',
            'scope' => 'public',
            'visibility' => 'public',
            'local' => false,
            'in_reply_to_id' => null,
            'reblog_of_id' => null,
            'is_nsfw' => false,
            'uri' => 'https://remote.example/p/'.uniqid(),
            'created_at' => $base->copy()->addSeconds($i)->toDateTimeString(),
        ]);
        StatusService::get($status->id, false);
    }

    NetworkTimelineService::warmCache(true, 2);
    $afterWarm = cappedOrder($key);
    expect(count($afterWarm))->toBe(2);
});

it('returns at most one hundred members when a timeline read requests five hundred', function () {
    $home = User::factory()->create();
    $home->refresh();
    $pid = $home->profile_id;

    $key = HomeTimelineService::CACHE_KEY.$pid;

    // Populate more members than the read cap so an oversized request cannot be
    // satisfied by scarcity. Distinct scores keep the order deterministic.
    $base = now()->subDay()->startOfMinute();
    for ($i = 0; $i < 150; $i++) {
        $score = $base->copy()->addSeconds($i)->timestamp;
        $member = 800_000_000_000_000_000 + $i;
        Redis::zadd($key, $score, $member);
    }

    $result = HomeTimelineService::get($pid, 0, 500);

    expect(count($result))->toBeLessThanOrEqual(100);
});
