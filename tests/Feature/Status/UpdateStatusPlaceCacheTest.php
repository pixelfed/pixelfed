<?php

use App\Models\Status;
use App\Models\User;
use App\Services\PlaceService;
use App\Services\Status\UpdateStatusService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Editing a status location must invalidate the place status-id caches
|--------------------------------------------------------------------------
|
| PlaceService caches the per-place status-id list for 4 days. Changing or
| removing a status place_id must clear both the old and new place caches so
| place pages update on the next request instead of drifting for days.
|
*/

function warmPlaceCache(int $placeId): void
{
    Cache::put(PlaceService::STATUSES_CACHE_KEY.$placeId, collect(), now()->addDays(4));
}

it('clears old and new place caches when the location changes', function () {
    $user = User::factory()->create();
    $user->refresh();

    $status = Status::factory()->create([
        'profile_id' => $user->profile_id,
        'type' => 'photo',
        'scope' => 'public',
        'place_id' => 100,
    ]);

    warmPlaceCache(100);
    warmPlaceCache(200);
    expect(Cache::has(PlaceService::STATUSES_CACHE_KEY.'100'))->toBeTrue();
    expect(Cache::has(PlaceService::STATUSES_CACHE_KEY.'200'))->toBeTrue();

    UpdateStatusService::handleImmediateAttributes($status, [
        'location' => ['id' => 200],
    ]);

    expect(Cache::has(PlaceService::STATUSES_CACHE_KEY.'100'))->toBeFalse('old place cache must be cleared');
    expect(Cache::has(PlaceService::STATUSES_CACHE_KEY.'200'))->toBeFalse('new place cache must be cleared');
});

it('does not leave a concurrently re-warmed old place cache behind (REGRESSION)', function () {
    $user = User::factory()->create();
    $user->refresh();

    $status = Status::factory()->create([
        'profile_id' => $user->profile_id,
        'type' => 'photo',
        'scope' => 'public',
        'place_id' => 100,
    ]);

    // Simulate a concurrent PlaceController::show read that lands mid-save: the
    // `updating` event fires inside save() *before* the DB UPDATE commits, so the
    // DB still holds the old place_id here. Re-warm the old place cache from that
    // pre-move state, exactly as a racing reader's Cache::remember would.
    Status::updating(function ($model) {
        expect((int) $model->getOriginal('place_id'))->toBe(100);
        warmPlaceCache(100);
    });

    UpdateStatusService::handleImmediateAttributes($status, [
        'location' => ['id' => 200],
    ]);

    Status::flushEventListeners();

    // The invalidation must win: after the move, the old place cache must not
    // survive, otherwise place 100 lists a status that left it for up to 4 days.
    expect(Cache::has(PlaceService::STATUSES_CACHE_KEY.'100'))->toBeFalse('stale re-warm of old place cache must be cleared after save');
});

it('clears the old place cache when the location is removed', function () {
    $user = User::factory()->create();
    $user->refresh();

    $status = Status::factory()->create([
        'profile_id' => $user->profile_id,
        'type' => 'photo',
        'scope' => 'public',
        'place_id' => 100,
    ]);

    warmPlaceCache(100);
    expect(Cache::has(PlaceService::STATUSES_CACHE_KEY.'100'))->toBeTrue();

    UpdateStatusService::handleImmediateAttributes($status, [
        'location' => [],
    ]);

    expect(Cache::has(PlaceService::STATUSES_CACHE_KEY.'100'))->toBeFalse('old place cache must be cleared on removal');
});
