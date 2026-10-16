<?php

use App\Models\Profile;
use App\Models\Status;
use App\Models\User;
use App\Services\NetworkTimelineService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| GET /api/pixelfed/v1/timelines/network
|--------------------------------------------------------------------------
|
| Warm hit, cold-start, and min_id / max_id pagination coverage for the
| cached network timeline endpoint.
|
*/

const NETWORK_CACHE_CHECK_KEY = 'api:v1:timelines:network:cache_check';

beforeEach(function () {
    config(['federation.network_timeline' => true]);
    config(['instance.hide_nsfw_on_public_feeds' => false]);

    Redis::del(NetworkTimelineService::CACHE_KEY);
    Cache::forget(NETWORK_CACHE_CHECK_KEY);
});

afterEach(function () {
    Redis::del(NetworkTimelineService::CACHE_KEY);
    Cache::forget(NETWORK_CACHE_CHECK_KEY);
});

function networkRemoteStatus(?Profile $author = null, array $overrides = []): Status
{
    $author ??= Profile::factory()->create(['user_id' => null, 'domain' => 'remote.example']);

    return Status::factory()->create(array_merge([
        'profile_id' => $author->id,
        'type' => 'photo',
        'scope' => 'public',
        'visibility' => 'public',
        'local' => false,
        'in_reply_to_id' => null,
        'reblog_of_id' => null,
        'is_nsfw' => false,
        'uri' => 'https://remote.example/p/'.uniqid(),
        'created_at' => now()->subMinutes(5),
    ], $overrides));
}

it('returns 403 without an authenticated user', function () {
    $this->getJson('/api/pixelfed/v1/timelines/network')
        ->assertStatus(403);
});

it('returns 404 when the network timeline is disabled', function () {
    config(['federation.network_timeline' => false]);

    $user = User::factory()->create();
    $user->refresh();

    $this->actingAs($user)
        ->getJson('/api/pixelfed/v1/timelines/network')
        ->assertStatus(404);
});

it('serves a warm cache hit newest-first', function () {
    $user = User::factory()->create();
    $user->refresh();

    // Keep the cache_check warm from running by seeding the set first and
    // marking the check as already performed.
    $statuses = collect([1, 2, 3])->map(fn () => networkRemoteStatus());
    $seededIds = $statuses->pluck('id')->sort()->values();

    foreach ($seededIds as $id) {
        NetworkTimelineService::add($id);
    }

    Cache::put(NETWORK_CACHE_CHECK_KEY, 1, 3600);

    $res = $this->actingAs($user)
        ->getJson('/api/pixelfed/v1/timelines/network?limit=30')
        ->assertOk()
        ->json();

    $returned = collect($res)->pluck('id')->map(fn ($id) => (int) $id)->all();
    $expected = $seededIds->reverse()->values()->map(fn ($id) => (int) $id)->all();

    expect($returned)->toBe($expected);
});

it('warms synchronously on cold start and serves the first page', function () {
    $user = User::factory()->create();
    $user->refresh();

    // Eligible remote statuses exist in the DB but the set is empty and the
    // cache_check has not run yet.
    $author = Profile::factory()->create(['user_id' => null, 'domain' => 'remote.example']);
    $eligible = collect([1, 2, 3])->map(fn () => networkRemoteStatus($author));

    expect(NetworkTimelineService::count())->toBe(0);
    expect(Cache::has(NETWORK_CACHE_CHECK_KEY))->toBeFalse();

    $res = $this->actingAs($user)
        ->getJson('/api/pixelfed/v1/timelines/network?limit=30')
        ->assertOk()
        ->json();

    // The synchronous cache_check warm ran and populated the set.
    expect(NetworkTimelineService::count())->toBeGreaterThan(0);

    $returned = collect($res)->pluck('id')->map(fn ($id) => (int) $id)->all();
    foreach ($eligible as $status) {
        expect($returned)->toContain((int) $status->id);
    }
});

it('returns only entries older than max_id, bounded by limit', function () {
    $user = User::factory()->create();
    $user->refresh();

    $author = Profile::factory()->create(['user_id' => null, 'domain' => 'remote.example']);
    $statuses = collect([1, 2, 3, 4, 5])->map(fn () => networkRemoteStatus($author));
    $ordered = $statuses->pluck('id')->map(fn ($id) => (int) $id)->sort()->values();

    foreach ($ordered as $id) {
        NetworkTimelineService::add($id);
    }
    Cache::put(NETWORK_CACHE_CHECK_KEY, 1, 3600);

    // Cursor is the middle id; max_id returns strictly-older ids newest-first.
    $cursor = $ordered[2];
    $res = $this->actingAs($user)
        ->getJson('/api/pixelfed/v1/timelines/network?max_id='.$cursor.'&limit=2')
        ->assertOk()
        ->json();

    $returned = collect($res)->pluck('id')->map(fn ($id) => (int) $id)->all();
    $expected = $ordered->slice(0, 2)->reverse()->values()->all();

    expect($returned)->toBe($expected);
    expect($returned)->not->toContain($cursor);
    expect(count($returned))->toBeLessThanOrEqual(2);
    foreach ($returned as $id) {
        expect($id)->toBeLessThan($cursor);
    }
});

it('returns only entries newer than min_id, bounded by limit', function () {
    $user = User::factory()->create();
    $user->refresh();

    $author = Profile::factory()->create(['user_id' => null, 'domain' => 'remote.example']);
    $statuses = collect([1, 2, 3, 4, 5])->map(fn () => networkRemoteStatus($author));
    $ordered = $statuses->pluck('id')->map(fn ($id) => (int) $id)->sort()->values();

    foreach ($ordered as $id) {
        NetworkTimelineService::add($id);
    }
    Cache::put(NETWORK_CACHE_CHECK_KEY, 1, 3600);

    // Cursor is the second id; min_id returns strictly-newer ids newest-first.
    $cursor = $ordered[1];
    $res = $this->actingAs($user)
        ->getJson('/api/pixelfed/v1/timelines/network?min_id='.$cursor.'&limit=2')
        ->assertOk()
        ->json();

    $returned = collect($res)->pluck('id')->map(fn ($id) => (int) $id)->all();
    $expected = $ordered->slice(3, 2)->reverse()->values()->all();

    expect($returned)->toBe($expected);
    expect($returned)->not->toContain($cursor);
    expect(count($returned))->toBeLessThanOrEqual(2);
    foreach ($returned as $id) {
        expect($id)->toBeGreaterThan($cursor);
    }
});
