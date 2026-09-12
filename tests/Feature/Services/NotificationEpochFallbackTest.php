<?php

use App\Jobs\InternalPipeline\NotificationEpochUpdatePipeline;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| NotificationService epoch fallback
|--------------------------------------------------------------------------
|
| When the cached epoch id is missing/expired, getEpochId() must return a
| recent notification id (computed inline) so notification queries only scan
| the last ~6 months. Returning 1 would force `WHERE id > 1`, scanning the
| entire notifications table.
|
*/

beforeEach(function () {
    Queue::fake();
    Cache::forget(NotificationService::EPOCH_CACHE_KEY.'6');
});

it('returns a recent notification id on cache miss instead of 1', function () {
    $old = Notification::create([
        'profile_id' => 1,
        'actor_id' => 2,
        'action' => 'like',
        'created_at' => now()->subMonths(9),
    ]);

    $recent = Notification::create([
        'profile_id' => 1,
        'actor_id' => 2,
        'action' => 'like',
        'created_at' => now()->subMonth(),
    ]);

    $epoch = NotificationService::getEpochId();

    expect($epoch)->toBe($recent->id);
    expect($epoch)->not->toBe(1);
    expect($epoch)->toBeGreaterThan($old->id);

    Queue::assertPushed(NotificationEpochUpdatePipeline::class);
});

it('falls back to 1 when no recent notifications exist', function () {
    Notification::create([
        'profile_id' => 1,
        'actor_id' => 2,
        'action' => 'like',
        'created_at' => now()->subYear(),
    ]);

    expect(NotificationService::getEpochId())->toBe(1);
});
