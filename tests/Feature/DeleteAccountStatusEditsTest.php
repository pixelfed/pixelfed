<?php

use App\Jobs\DeletePipeline\DeleteAccountPipeline;
use App\Models\Status;
use App\Models\StatusEdit;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| DeleteAccountPipeline edit-history cleanup
|--------------------------------------------------------------------------
|
| Account deletion must purge status_edits (prior caption/CW versions the user
| edited). The table has no FK/cascade and StatusEdit has no SoftDeletes, so it
| was left behind indefinitely while the pipeline scrubbed comparable
| user-authored tables (DMs, mentions, notifications, stories, collections).
|
*/

it('removes status_edits rows when an account is deleted', function () {
    $user = User::factory()->create();
    $user->refresh();
    $pid = $user->profile_id;

    $status = Status::factory()->create([
        'profile_id' => $pid,
        'caption' => 'edited to redact',
        'type' => 'text',
        'scope' => 'public',
    ]);

    // Prior + current caption versions, as UpdateStatusService records them.
    StatusEdit::create([
        'status_id' => $status->id,
        'profile_id' => $pid,
        'caption' => 'original sensitive text',
    ]);
    StatusEdit::create([
        'status_id' => $status->id,
        'profile_id' => $pid,
        'caption' => 'edited to redact',
    ]);

    expect(StatusEdit::whereProfileId($pid)->count())->toBe(2);

    (new DeleteAccountPipeline($user))->handle();

    // Edit history is hard-deleted; no prior caption text is left behind.
    expect(StatusEdit::whereProfileId($pid)->count())->toBe(0);
});
