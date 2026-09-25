<?php

use App\Jobs\StatusPipeline\RemoteStatusDelete;
use App\Models\Profile;
use App\Models\Status;
use App\Models\StatusEdit;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| RemoteStatusDelete must purge status_edits like StatusDelete does
|--------------------------------------------------------------------------
|
| status_edits has no FK/cascade, so edit-history rows must be deleted
| explicitly. The local StatusDelete path did this; the remote path did not,
| orphaning prior caption/CW versions forever.
|
*/

it('purges status_edits when force-deleting a remote status', function () {
    $profile = Profile::factory()->remote()->create();

    $status = Status::factory()->create([
        'profile_id' => $profile->id,
        'uri' => 'https://remote.example/status/'.$profile->id.'/123',
        'type' => 'photo',
    ]);

    StatusEdit::create([
        'status_id' => $status->id,
        'profile_id' => $profile->id,
        'caption' => 'original sensitive text',
    ]);
    StatusEdit::create([
        'status_id' => $status->id,
        'profile_id' => $profile->id,
        'caption' => 'edited to redact',
    ]);

    expect(StatusEdit::whereStatusId($status->id)->count())->toBe(2);

    (new RemoteStatusDelete($status))->handle();

    expect(Status::find($status->id))->toBeNull('remote status row should be force-deleted');
    expect(StatusEdit::whereStatusId($status->id)->count())
        ->toBe(0, 'orphaned status_edits rows should be purged');
});
