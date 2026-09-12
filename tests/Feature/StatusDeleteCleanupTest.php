<?php

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
| StatusDelete cleanup
|--------------------------------------------------------------------------
|
| unlinkRemoveMedia() previously looped over each associated DirectMessage
| and MediaTag, running a per-row Notification lookup and delete. It now
| fetches the ids, resolves notifications in a single query, clears each
| notification (cache + redis) via cursor, then bulk deletes. These tests
| lock in the observable cleanup behaviour.
|
*/

it('removes associated dms and their notifications when a status is deleted', function () {
    $user = User::factory()->create();
    $user->refresh();

    $status = Status::factory()->create([
        'profile_id' => $user->profile_id,
        'type' => 'photo',
    ]);

    $dm = new DirectMessage;
    $dm->to_id = $user->profile_id;
    $dm->from_id = $user->profile_id;
    $dm->status_id = $status->id;
    $dm->save();

    $notification = Notification::create([
        'profile_id' => $user->profile_id,
        'actor_id' => $user->profile_id,
        'action' => 'dm',
        'item_type' => DirectMessage::class,
        'item_id' => $dm->id,
    ]);

    (new StatusDelete($status))->handle();

    expect(DirectMessage::find($dm->id))->toBeNull();
    expect(Notification::find($notification->id))->toBeNull();
    expect(Status::find($status->id))->toBeNull();
});

it('removes associated media tags and their notifications when a status is deleted', function () {
    $user = User::factory()->create();
    $user->refresh();

    $status = Status::factory()->create([
        'profile_id' => $user->profile_id,
        'type' => 'photo',
    ]);

    $tag = MediaTag::create([
        'status_id' => $status->id,
        'media_id' => 1,
        'profile_id' => $user->profile_id,
        'tagged_username' => 'someone',
    ]);

    $notification = Notification::create([
        'profile_id' => $user->profile_id,
        'actor_id' => $user->profile_id,
        'action' => 'tagged',
        'item_type' => MediaTag::class,
        'item_id' => $tag->id,
    ]);

    (new StatusDelete($status))->handle();

    expect(MediaTag::find($tag->id))->toBeNull();
    expect(Notification::find($notification->id))->toBeNull();
});

it('still deletes a status without dms or tags', function () {
    $user = User::factory()->create();
    $user->refresh();

    $status = Status::factory()->create([
        'profile_id' => $user->profile_id,
        'type' => 'photo',
    ]);

    (new StatusDelete($status))->handle();

    expect(Status::find($status->id))->toBeNull();
});

it('cleans up even when the owning profile is soft deleted', function () {
    // Mirrors the account-deletion flow on AP-enabled instances: the
    // owning profile is soft-deleted before the queued StatusDelete runs.
    config(['federation.activitypub.enabled' => true]);

    $user = User::factory()->create();
    $user->refresh();

    $status = Status::factory()->create([
        'profile_id' => $user->profile_id,
        'type' => 'photo',
    ]);

    // Soft-delete the owning profile, as DeleteAccountPipeline does.
    $user->profile->delete();

    (new StatusDelete($status))->handle();

    expect(Status::find($status->id))->toBeNull();
});
