<?php

use App\Jobs\StatusPipeline\StatusDelete;
use App\Models\Notification;
use App\Models\Status;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| StatusDelete invalidates cached status notifications
|--------------------------------------------------------------------------
|
| Notifications cache a fully-transformed snapshot (with an embedded status
| payload) under ITEM_KEY for 24h, and the web feed serves that verbatim. When
| a status is deleted, its status-typed notifications must be removed per-row so
| NotificationObserver::forceDeleted -> NotificationService::del invalidates the
| cached snapshot. A bulk forceDelete() fires no model events and would leave
| the web feed serving the deleted status as a ghost for up to 24h.
|
*/

/**
 * Persist a status-typed like notification and warm its ITEM_KEY snapshot, the
 * way createNotification/setNotification does on the live path.
 */
function warmStatusNotification(int $profileId, int $actorId, int $statusId, string $itemType): Notification
{
    $n = new Notification;
    $n->profile_id = $profileId;
    $n->actor_id = $actorId;
    $n->action = 'like';
    $n->item_id = $statusId;
    $n->item_type = $itemType;
    $n->save();

    NotificationService::setNotification($n); // writes ITEM_KEY
    NotificationService::set($n->profile_id, $n->id);

    return $n;
}

it('invalidates the cached notification snapshot when its status is deleted', function () {
    $user = User::factory()->create();
    $user->refresh();
    $actor = User::factory()->create();
    $actor->refresh();

    $status = Status::factory()->create(['profile_id' => $user->profile_id, 'scope' => 'public']);
    $n = warmStatusNotification($user->profile_id, $actor->profile_id, $status->id, Status::class);

    // Snapshot is warm before deletion.
    expect(Cache::get(NotificationService::ITEM_KEY.$n->id))->not->toBeNull();

    (new StatusDelete($status))->unlinkRemoveMedia($status);

    // Row is force-deleted and the cached snapshot was invalidated by the
    // observer (no manual NotificationService::del in the test).
    expect(Notification::withTrashed()->whereId($n->id)->exists())->toBeFalse();
    expect(Cache::get(NotificationService::ITEM_KEY.$n->id))->toBeNull();
});

it('invalidates and removes legacy App\\Status notifications too', function () {
    $user = User::factory()->create();
    $user->refresh();
    $actor = User::factory()->create();
    $actor->refresh();

    $status = Status::factory()->create(['profile_id' => $user->profile_id, 'scope' => 'public']);
    // Legacy morph alias predating the App\ -> App\Models\ migration.
    $n = warmStatusNotification($user->profile_id, $actor->profile_id, $status->id, 'App\Status');

    expect(Cache::get(NotificationService::ITEM_KEY.$n->id))->not->toBeNull();

    (new StatusDelete($status))->unlinkRemoveMedia($status);

    // The alias-aware cleanup matches legacy rows: no DB orphan, cache cleared.
    expect(Notification::withTrashed()->whereId($n->id)->exists())->toBeFalse();
    expect(Cache::get(NotificationService::ITEM_KEY.$n->id))->toBeNull();
});
