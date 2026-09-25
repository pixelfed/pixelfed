<?php

use App\Jobs\StatusPipeline\StatusRemoteUpdatePipeline;
use App\Models\ModLog;
use App\Models\Profile;
use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Redis;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Remote-update admin NSFW lock
|--------------------------------------------------------------------------
|
| When an admin marks a remote status NSFW, a later remote Update carrying
| sensitive:false must not clear the mark. The lock read only matched the row
| shape written by one admin path; report-handling rows (keyed by profile_id,
| App\Models\Status literal) were invisible, so the mark could be lifted. The
| lock now matches both shapes and gates on metadata.action = 'cw'.
|
*/

beforeEach(function () {
    Redis::spy();
});

function nsfwLockRemoteStatus(): Status
{
    $user = User::factory()->create();
    $user->refresh();

    // cw=false so the profile-level CW override does not itself force is_nsfw.
    $profile = $user->profile;
    $profile->cw = false;
    $profile->save();

    $objectUrl = 'https://remote.example/users/bob/statuses/'.uniqid();

    return Status::factory()->create([
        'profile_id' => $user->profile_id,
        'type' => 'photo',
        'scope' => 'public',
        'visibility' => 'public',
        'local' => false,
        'uri' => $objectUrl,
        'object_url' => $objectUrl,
        'is_nsfw' => true,
    ]);
}

function modLog(Status $status, string $objectType, int $objectId, string $action): void
{
    $admin = User::factory()->create(['is_admin' => true]);

    $m = new ModLog;
    $m->user_id = $admin->id;
    $m->object_uid = $status->profile_id;
    $m->object_id = $objectId;
    $m->object_type = $objectType;
    $m->action = 'admin.status.moderate';
    $m->metadata = json_encode(['action' => $action, 'message' => 'Success!']);
    $m->access_level = 'admin';
    $m->save();
}

function deliverSensitiveFalse(Status $status): Status
{
    (new StatusRemoteUpdatePipeline([
        'id' => $status->object_url,
        'type' => 'Update',
        'content' => 'edited caption',
        'sensitive' => false,
    ]))->handle();

    return $status->fresh();
}

it('keeps the NSFW mark from a report-handling modlog (profile_id keyed, Models literal)', function () {
    $status = nsfwLockRemoteStatus();

    // Path B shape: object_id = profile_id, object_type = App\Models\Status.
    modLog($status, 'App\Models\Status::class', $status->profile_id, 'cw');

    expect((bool) deliverSensitiveFalse($status)->is_nsfw)->toBeTrue();
});

it('keeps the NSFW mark from a status-scoped modlog (addcw shape)', function () {
    $status = nsfwLockRemoteStatus();

    // Path A shape: object_id = status id, object_type = App\Status.
    modLog($status, 'App\Status::class', $status->id, 'cw');

    expect((bool) deliverSensitiveFalse($status)->is_nsfw)->toBeTrue();
});

it('clears NSFW when there is no admin moderation modlog', function () {
    $status = nsfwLockRemoteStatus();

    expect((bool) deliverSensitiveFalse($status)->is_nsfw)->toBeFalse();
});

it('does not treat a non-cw moderation action as an NSFW lock', function () {
    $status = nsfwLockRemoteStatus();

    // A report-handling unlist row shares action=admin.status.moderate but is
    // not an NSFW mark, so it must not re-lock is_nsfw.
    modLog($status, 'App\Status::class', $status->id, 'unlist');

    expect((bool) deliverSensitiveFalse($status)->is_nsfw)->toBeFalse();
});
