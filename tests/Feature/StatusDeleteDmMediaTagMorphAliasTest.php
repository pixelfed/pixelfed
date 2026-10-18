<?php

use App\Jobs\StatusPipeline\RemoteStatusDelete;
use App\Jobs\StatusPipeline\StatusDelete;
use App\Models\DirectMessage;
use App\Models\MediaTag;
use App\Models\Notification;
use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| StatusDelete DM / MediaTag legacy morph alias cleanup - regression
|--------------------------------------------------------------------------
|
| notifications.item_type for direct-message and media-tag rows may hold the
| pre-namespace-migration alias ('App\DirectMessage' / 'App\MediaTag') as well
| as the current DirectMessage::class / MediaTag::class. The single-alias
| cleanup skipped legacy rows while still deleting the underlying DM / media_tag
| rows, leaving orphan notifications. Cleanup must match both aliases (as the
| adjacent Status sweep already does).
|
*/

function makeDmNotification(Status $status, int $profileId, string $itemType, int $seq): Notification
{
    $dm = new DirectMessage;
    $dm->to_id = $profileId + $seq;
    $dm->from_id = $profileId;
    $dm->status_id = $status->id;
    $dm->type = 'text';
    $dm->save();

    $n = new Notification;
    $n->profile_id = $profileId;
    $n->actor_id = $profileId;
    $n->action = 'dm';
    $n->item_id = $dm->id;
    $n->item_type = $itemType;
    $n->save();

    return $n;
}

function makeTagNotification(Status $status, int $profileId, string $itemType, int $seq): Notification
{
    $tag = new MediaTag;
    $tag->status_id = $status->id;
    $tag->media_id = $seq;
    $tag->profile_id = $profileId;
    $tag->tagged_username = 'tagged';
    $tag->is_public = 1;
    $tag->save();

    $n = new Notification;
    $n->profile_id = $profileId;
    $n->actor_id = $profileId;
    $n->action = 'tagged';
    $n->item_id = $tag->id;
    $n->item_type = $itemType;
    $n->save();

    return $n;
}

it('deletes both legacy App\\DirectMessage and modern DM notifications on status delete', function () {
    config(['federation.activitypub.enabled' => false]);

    $user = User::factory()->create();
    $user->refresh();

    $status = Status::factory()->create([
        'profile_id' => $user->profile_id,
        'type' => 'photo',
    ]);

    $legacy = makeDmNotification($status, $user->profile_id, 'App\DirectMessage', 1);
    $modern = makeDmNotification($status, $user->profile_id, DirectMessage::class, 2);

    (new StatusDelete($status))->handle();

    expect(Notification::find($modern->id))->toBeNull('modern DM notification should be removed');
    expect(Notification::find($legacy->id))->toBeNull('legacy App\\DirectMessage notification should also be removed');
});

it('deletes both legacy App\\MediaTag and modern tag notifications on status delete', function () {
    config(['federation.activitypub.enabled' => false]);

    $user = User::factory()->create();
    $user->refresh();

    $status = Status::factory()->create([
        'profile_id' => $user->profile_id,
        'type' => 'photo',
    ]);

    $legacy = makeTagNotification($status, $user->profile_id, 'App\MediaTag', 1);
    $modern = makeTagNotification($status, $user->profile_id, MediaTag::class, 2);

    (new StatusDelete($status))->handle();

    expect(Notification::find($modern->id))->toBeNull('modern tag notification should be removed');
    expect(Notification::find($legacy->id))->toBeNull('legacy App\\MediaTag notification should also be removed');
});

it('deletes both legacy and modern DM notifications on remote status delete', function () {
    config(['federation.activitypub.enabled' => false]);

    $user = User::factory()->create();
    $user->refresh();

    $status = Status::factory()->create([
        'profile_id' => $user->profile_id,
        'type' => 'photo',
    ]);

    $legacy = makeDmNotification($status, $user->profile_id, 'App\DirectMessage', 1);
    $modern = makeDmNotification($status, $user->profile_id, DirectMessage::class, 2);

    (new RemoteStatusDelete($status))->handle();

    expect(Notification::find($modern->id))->toBeNull('modern DM notification should be removed');
    expect(Notification::find($legacy->id))->toBeNull('legacy App\\DirectMessage notification should also be removed');
});

it('deletes both legacy and modern tag notifications on remote status delete', function () {
    config(['federation.activitypub.enabled' => false]);

    $user = User::factory()->create();
    $user->refresh();

    $status = Status::factory()->create([
        'profile_id' => $user->profile_id,
        'type' => 'photo',
    ]);

    $legacy = makeTagNotification($status, $user->profile_id, 'App\MediaTag', 1);
    $modern = makeTagNotification($status, $user->profile_id, MediaTag::class, 2);

    (new RemoteStatusDelete($status))->handle();

    expect(Notification::find($modern->id))->toBeNull('modern tag notification should be removed');
    expect(Notification::find($legacy->id))->toBeNull('legacy App\\MediaTag notification should also be removed');
});
