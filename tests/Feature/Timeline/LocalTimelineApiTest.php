<?php

use App\Models\Status;
use App\Models\User;
use App\Services\PublicTimelineService;
use App\Services\StatusService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| GET /api/pixelfed/v1/timelines/public (publicTimelineApi)
|--------------------------------------------------------------------------
|
| The local timeline is always served from the Redis-backed cached path.
| These tests cover a warm-cache hit, cold-start warming, and min_id/max_id
| pagination against the live endpoint.
|
*/

const LOCAL_CACHE_CHECK_KEY = 'api:v1:timelines:public:cache_check';

beforeEach(function () {
    Redis::del(PublicTimelineService::CACHE_KEY);
    Cache::forget(LOCAL_CACHE_CHECK_KEY);
});

afterEach(function () {
    Redis::del(PublicTimelineService::CACHE_KEY);
    Cache::forget(LOCAL_CACHE_CHECK_KEY);
});

function eligibleLocalStatus(): Status
{
    static $seq = 0;

    return Status::factory()->create([
        'type' => 'photo',
        'scope' => 'public',
        'visibility' => 'public',
        'local' => true,
        'in_reply_to_id' => null,
        'reblog_of_id' => null,
        'uri' => null,
        'created_at' => now()->subMinutes(1000 - ($seq++)),
    ]);
}

function localIds($res): array
{
    return collect($res)->pluck('id')->map(fn ($id) => (int) $id)->all();
}

it('serves seeded ids newest-first on a warm-cache hit', function () {
    $viewer = User::factory()->create();
    $viewer->refresh();

    $statuses = collect(range(1, 4))->map(fn () => eligibleLocalStatus());

    foreach ($statuses as $status) {
        PublicTimelineService::add($status->id);
        StatusService::get($status->id);
    }

    $expected = $statuses->pluck('id')->map(fn ($id) => (int) $id)->sortDesc()->values()->all();

    $res = $this->actingAs($viewer)
        ->getJson('/api/pixelfed/v1/timelines/public?limit=10')
        ->assertOk()
        ->json();

    expect(localIds($res))->toBe($expected);
});

it('warms the cache synchronously on cold-start and serves the first page', function () {
    $viewer = User::factory()->create();
    $viewer->refresh();

    $statuses = collect(range(1, 3))->map(fn () => eligibleLocalStatus());

    expect(PublicTimelineService::count())->toBe(0);
    expect(Cache::has(LOCAL_CACHE_CHECK_KEY))->toBeFalse();

    $res = $this->actingAs($viewer)
        ->getJson('/api/pixelfed/v1/timelines/public?limit=10')
        ->assertOk()
        ->json();

    expect(PublicTimelineService::count())->toBeGreaterThan(0);

    $ids = localIds($res);
    expect($ids)->not->toBeEmpty();

    foreach ($statuses as $status) {
        expect($ids)->toContain((int) $status->id);
    }
});

it('returns only entries newer than min_id bounded by limit', function () {
    $viewer = User::factory()->create();
    $viewer->refresh();

    $statuses = collect(range(1, 5))
        ->map(fn () => eligibleLocalStatus())
        ->sortBy('id')
        ->values();

    foreach ($statuses as $status) {
        PublicTimelineService::add($status->id);
        StatusService::get($status->id);
    }

    $cursor = (int) $statuses[1]->id;
    $newer = $statuses->slice(3, 2)->pluck('id')->map(fn ($id) => (int) $id);

    $res = $this->actingAs($viewer)
        ->getJson('/api/pixelfed/v1/timelines/public?min_id='.$cursor.'&limit=2')
        ->assertOk()
        ->json();

    $ids = localIds($res);

    expect(count($ids))->toBeLessThanOrEqual(2);
    expect($ids)->not->toContain($cursor);

    foreach ($ids as $id) {
        expect($id)->toBeGreaterThan($cursor);
    }

    expect($ids)->toBe($newer->sortDesc()->values()->all());
});

it('returns only entries older than max_id bounded by limit', function () {
    $viewer = User::factory()->create();
    $viewer->refresh();

    $statuses = collect(range(1, 5))
        ->map(fn () => eligibleLocalStatus())
        ->sortBy('id')
        ->values();

    foreach ($statuses as $status) {
        PublicTimelineService::add($status->id);
        StatusService::get($status->id);
    }

    $cursor = (int) $statuses[3]->id;
    $older = $statuses->slice(0, 3)->pluck('id')->map(fn ($id) => (int) $id);

    $res = $this->actingAs($viewer)
        ->getJson('/api/pixelfed/v1/timelines/public?max_id='.$cursor.'&limit=2')
        ->assertOk()
        ->json();

    $ids = localIds($res);

    expect(count($ids))->toBeLessThanOrEqual(2);
    expect($ids)->not->toContain($cursor);

    foreach ($ids as $id) {
        expect($id)->toBeLessThan($cursor);
    }

    expect($ids)->toBe($older->sortDesc()->take(2)->values()->all());
});
