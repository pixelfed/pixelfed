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

/** Build a random, strictly-increasing set of ids. */
function randomIdSet(int $count): array
{
    $ids = [];
    $cursor = random_int(1, 1000);
    for ($i = 0; $i < $count; $i++) {
        $cursor += random_int(1, 50);
        $ids[] = $cursor;
    }

    return $ids;
}

function toInts($ids): array
{
    return collect($ids)->map(fn ($i) => (int) $i)->all();
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
        // Pick a positive cursor somewhere across (and occasionally outside) the
        // range. The services treat a falsy cursor as "no cursor" and short-circuit.
        $cursor = random_int(max(1, $ids[0] - 10), end($ids) + 10);

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
        $cursor = random_int(max(1, $ids[0] - 10), end($ids) + 10);

        $result = toInts($minId($cursor, $limit));
        // min_id returns the window immediately newer than the cursor: the
        // ids just above it, presented newest-first.
        $expected = collect($ids)
            ->filter(fn ($id) => $id > $cursor)
            ->sort()
            ->take($limit)
            ->sortDesc()
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
