<?php

use App\Services\HomeTimelineService;
use App\Services\NetworkTimelineService;
use App\Services\PublicTimelineService;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

// These services call config() and the Redis facade, so they need a booted
// application. tests/Unit is not bound to the application TestCase in Pest.php
// (only Feature is), so bind it explicitly here.
uses(TestCase::class);

/*
|--------------------------------------------------------------------------
| Service add/remove/get round-trip
|--------------------------------------------------------------------------
|
| For any timeline service and any status id, adding the id and then reading
| the set returns a set containing that id, and removing it then reading the
| set returns a set that does not contain it.
|
| HomeTimelineService is per-profile and takes a $pid argument; the Public and
| Network services are instance-wide. The round-trip holds for all three.
|
*/

const ROUND_TRIP_ITERATIONS = 150;

const ROUND_TRIP_HOME_PID = 1;

/** Clean every sorted set these tests touch so state never leaks between runs. */
function flushRoundTripSets(): void
{
    Redis::del(PublicTimelineService::CACHE_KEY);
    Redis::del(NetworkTimelineService::CACHE_KEY);
    Redis::del(HomeTimelineService::CACHE_KEY.ROUND_TRIP_HOME_PID);
}

/** Generate a fresh random status id, avoiding the one just used. */
function randomRoundTripId(int $exclude = 0): int
{
    do {
        $id = random_int(1, PHP_INT_MAX);
    } while ($id === $exclude);

    return $id;
}

beforeEach(function () {
    flushRoundTripSets();
});

afterEach(function () {
    flushRoundTripSets();
});

it('round-trips add/remove/get for PublicTimelineService', function () {
    for ($i = 0; $i < ROUND_TRIP_ITERATIONS; $i++) {
        flushRoundTripSets();
        $id = randomRoundTripId();

        PublicTimelineService::add($id);
        $afterAdd = collect(PublicTimelineService::get(0, -1))->map(fn ($v) => (int) $v);
        expect($afterAdd)->toContain($id);

        PublicTimelineService::rem($id);
        $afterRem = collect(PublicTimelineService::get(0, -1))->map(fn ($v) => (int) $v);
        expect($afterRem)->not->toContain($id);
    }
});

it('round-trips add/remove/get for NetworkTimelineService', function () {
    for ($i = 0; $i < ROUND_TRIP_ITERATIONS; $i++) {
        flushRoundTripSets();
        $id = randomRoundTripId();

        NetworkTimelineService::add($id);
        $afterAdd = collect(NetworkTimelineService::get(0, -1))->map(fn ($v) => (int) $v);
        expect($afterAdd)->toContain($id);

        NetworkTimelineService::rem($id);
        $afterRem = collect(NetworkTimelineService::get(0, -1))->map(fn ($v) => (int) $v);
        expect($afterRem)->not->toContain($id);
    }
});

it('round-trips add/remove/get for HomeTimelineService', function () {
    for ($i = 0; $i < ROUND_TRIP_ITERATIONS; $i++) {
        flushRoundTripSets();
        $id = randomRoundTripId();

        HomeTimelineService::add(ROUND_TRIP_HOME_PID, $id);
        $afterAdd = collect(HomeTimelineService::get(ROUND_TRIP_HOME_PID, 0, -1))->map(fn ($v) => (int) $v);
        expect($afterAdd)->toContain($id);

        HomeTimelineService::rem(ROUND_TRIP_HOME_PID, $id);
        $afterRem = collect(HomeTimelineService::get(ROUND_TRIP_HOME_PID, 0, -1))->map(fn ($v) => (int) $v);
        expect($afterRem)->not->toContain($id);
    }
});
