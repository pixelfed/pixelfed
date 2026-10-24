<?php

use App\Services\HomeTimelineService;
use App\Services\NetworkTimelineService;
use App\Services\PublicTimelineService;
use Illuminate\Support\Facades\Redis;

/*
|--------------------------------------------------------------------------
| Ranked pagination bounds
|--------------------------------------------------------------------------
|
| For any populated sorted set, getRankedMaxId(cursor, limit) returns only
| ids strictly less than the cursor, and getRankedMinId(cursor, limit)
| returns only ids strictly greater than the cursor. Each result is capped
| at limit and ordered newest-first (descending).
*/

const RANKED_BOUNDS_HOME_PID = 1;

beforeEach(function () {
    Redis::del(PublicTimelineService::CACHE_KEY);
    Redis::del(NetworkTimelineService::CACHE_KEY);
    Redis::del(HomeTimelineService::CACHE_KEY.RANKED_BOUNDS_HOME_PID);
});

afterEach(function () {
    Redis::del(PublicTimelineService::CACHE_KEY);
    Redis::del(NetworkTimelineService::CACHE_KEY);
    Redis::del(HomeTimelineService::CACHE_KEY.RANKED_BOUNDS_HOME_PID);
});

// Ids are Pixelfed snowflakes. Each successive id is spaced far enough apart
// that it lands on a distinct, strictly-increasing score, so id order and score
// order agree and the bounds below the cursor are well defined.
const RANKED_BOUNDS_ID_BASE = 1_000_000_000_000_000_000;

const RANKED_BOUNDS_ID_STEP = 8_388_608_000;

/** Build a random, strictly-increasing set of snowflake ids. */
function randomIdSet(int $count): array
{
    $ids = [];
    $cursor = random_int(1, 1000);
    for ($i = 0; $i < $count; $i++) {
        $cursor += random_int(1, 50);
        $ids[] = RANKED_BOUNDS_ID_BASE + ($cursor * RANKED_BOUNDS_ID_STEP);
    }

    return $ids;
}

function toInts($ids): array
{
    return collect($ids)->map(fn ($i) => (int) $i)->all();
}

/**
 * Pick a cursor on the same snowflake lattice as the seeded ids, somewhere
 * across (and occasionally just outside) the range. Staying on the lattice
 * keeps score order and id order in lockstep, so the strict-bound filter and
 * the service agree on which ids fall on each side of the cursor.
 */
function rankedBoundsCursor(array $ids): int
{
    $lowSteps = (int) (($ids[0] - RANKED_BOUNDS_ID_BASE) / RANKED_BOUNDS_ID_STEP);
    $highSteps = (int) ((end($ids) - RANKED_BOUNDS_ID_BASE) / RANKED_BOUNDS_ID_STEP);

    $steps = random_int(max(1, $lowSteps - 2), $highSteps + 2);

    return RANKED_BOUNDS_ID_BASE + ($steps * RANKED_BOUNDS_ID_STEP);
}

/** A seeder/reader pair for a timeline service, hiding the per-pid Home signature. */
function rankedServices(): array
{
    return [
        'home' => [
            fn ($val) => HomeTimelineService::add(RANKED_BOUNDS_HOME_PID, $val),
            fn ($cursor, $limit) => HomeTimelineService::getRankedMaxId(RANKED_BOUNDS_HOME_PID, $cursor, $limit),
            fn ($cursor, $limit) => HomeTimelineService::getRankedMinId(RANKED_BOUNDS_HOME_PID, $cursor, $limit),
        ],
        'public' => [
            fn ($val) => PublicTimelineService::add($val),
            fn ($cursor, $limit) => PublicTimelineService::getRankedMaxId($cursor, $limit),
            fn ($cursor, $limit) => PublicTimelineService::getRankedMinId($cursor, $limit),
        ],
        'network' => [
            fn ($val) => NetworkTimelineService::add($val),
            fn ($cursor, $limit) => NetworkTimelineService::getRankedMaxId($cursor, $limit),
            fn ($cursor, $limit) => NetworkTimelineService::getRankedMinId($cursor, $limit),
        ],
    ];
}

it('keeps getRankedMaxId below the cursor and capped at limit across random inputs', function (string $name) {
    [$add, $maxId, $minId] = rankedServices()[$name];
    $key = [
        'home' => HomeTimelineService::CACHE_KEY.RANKED_BOUNDS_HOME_PID,
        'public' => PublicTimelineService::CACHE_KEY,
        'network' => NetworkTimelineService::CACHE_KEY,
    ][$name];

    for ($iter = 0; $iter < 120; $iter++) {
        Redis::del($key);

        $ids = randomIdSet(random_int(1, 25));
        foreach ($ids as $id) {
            $add($id);
        }

        $limit = random_int(1, 30);
        // The services treat a falsy cursor as "no cursor" and short-circuit.
        $cursor = rankedBoundsCursor($ids);

        $result = toInts($maxId($cursor, $limit));
        $expected = collect($ids)
            ->filter(fn ($id) => $id < $cursor)
            ->sortDesc()
            ->take($limit)
            ->values()
            ->all();

        // Every returned id is strictly older (less) than the cursor.
        foreach ($result as $id) {
            expect($id)->toBeLessThan($cursor);
        }
        // Never more than the requested limit.
        expect(count($result))->toBeLessThanOrEqual($limit);
        // Exactly the newest-first window below the cursor.
        expect($result)->toBe($expected);
    }
})->with(['home', 'public', 'network']);

it('keeps getRankedMinId above the cursor and capped at limit across random inputs', function (string $name) {
    [$add, $maxId, $minId] = rankedServices()[$name];
    $key = [
        'home' => HomeTimelineService::CACHE_KEY.RANKED_BOUNDS_HOME_PID,
        'public' => PublicTimelineService::CACHE_KEY,
        'network' => NetworkTimelineService::CACHE_KEY,
    ][$name];

    for ($iter = 0; $iter < 120; $iter++) {
        Redis::del($key);

        $ids = randomIdSet(random_int(1, 25));
        foreach ($ids as $id) {
            $add($id);
        }

        $limit = random_int(1, 30);
        $cursor = rankedBoundsCursor($ids);

        $result = toInts($minId($cursor, $limit));
        $expected = collect($ids)
            ->filter(fn ($id) => $id > $cursor)
            ->sortDesc()
            ->take($limit)
            ->values()
            ->all();

        // Every returned id is strictly newer (greater) than the cursor.
        foreach ($result as $id) {
            expect($id)->toBeGreaterThan($cursor);
        }
        expect(count($result))->toBeLessThanOrEqual($limit);
        expect($result)->toBe($expected);
    }
})->with(['home', 'public', 'network']);
