<?php

use App\Jobs\StatusPipeline\StatusDelete;
use App\Models\DirectMessage;
use App\Models\Follower;
use App\Models\MediaTag;
use App\Models\Notification;
use App\Models\Profile;
use App\Models\Status;
use App\Models\StatusEdit;
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

it('removes edit history (status_edits) when a status is deleted', function () {
    $user = User::factory()->create();
    $user->refresh();

    $status = Status::factory()->create([
        'profile_id' => $user->profile_id,
        'type' => 'photo',
    ]);

    // Prior + current caption versions, the way UpdateStatusService records them.
    StatusEdit::create([
        'status_id' => $status->id,
        'profile_id' => $user->profile_id,
        'caption' => 'original sensitive text',
    ]);
    StatusEdit::create([
        'status_id' => $status->id,
        'profile_id' => $user->profile_id,
        'caption' => 'edited to redact',
    ]);

    expect(StatusEdit::whereStatusId($status->id)->count())->toBe(2);

    (new StatusDelete($status))->handle();

    // Edit history is hard-deleted alongside the status (no orphaned prior text).
    expect(StatusEdit::whereStatusId($status->id)->count())->toBe(0);
    expect(Status::find($status->id))->toBeNull();
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

/*
|--------------------------------------------------------------------------
| StatusDelete cleanup is isolated from federation delivery failures
|--------------------------------------------------------------------------
|
| Account deletion marks the profile inactive (status = 'delete') then dispatches
| StatusDelete jobs. With federation enabled and a warm, non-empty follower
| audience, fanoutDelete() calls ActivityPubDeliveryService::pool(), whose
| validateSender() throws for an inactive sender. That exception must not abort
| the job before unlinkRemoveMedia() runs, or the status leaks permanently.
|
*/

it('deletes the status even when fanout delivery throws for an inactive sender', function () {
    config(['federation.activitypub.enabled' => true]);

    $owner = User::factory()->create();
    $owner->refresh();
    $profile = $owner->profile;

    // Remote follower -> non-empty audience so fanoutDelete calls pool().
    $remote = Profile::factory()->remote()->create([
        'inbox_url' => 'https://remote.example/inbox',
        'sharedInbox' => null,
    ]);
    Follower::create([
        'profile_id' => $remote->id,
        'following_id' => $profile->id,
        'local_profile' => false,
    ]);

    expect($profile->fresh()->getAudienceInbox())->not->toBeEmpty();

    // Account-deletion state: inactive sender -> validateSender() throws in pool().
    $profile->status = 'delete';
    $profile->save();

    $status = Status::factory()->create(['profile_id' => $profile->id, 'type' => 'photo']);

    // Must not throw, and must complete local cleanup.
    (new StatusDelete($status))->handle();

    expect(Status::find($status->id))->toBeNull();
});

it('decrements status_count once when fanout delivery throws for an inactive sender', function () {
    config(['federation.activitypub.enabled' => true]);

    $owner = User::factory()->create();
    $owner->refresh();
    $profile = $owner->profile;

    $remote = Profile::factory()->remote()->create([
        'inbox_url' => 'https://remote.example/inbox',
        'sharedInbox' => null,
    ]);
    Follower::create([
        'profile_id' => $remote->id,
        'following_id' => $profile->id,
        'local_profile' => false,
    ]);

    $profile->status = 'delete';
    $profile->save();
    $profile->status_count = 5;
    $profile->saveQuietly();

    $status = Status::factory()->create(['profile_id' => $profile->id, 'type' => 'photo']);

    // The job completes (no throw), so the queue does not retry and re-decrement.
    (new StatusDelete($status))->handle();

    expect(Status::find($status->id))->toBeNull();
    expect((int) $profile->fresh()->status_count)->toBe(4);
});
