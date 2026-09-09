<?php

use App\Jobs\ImportPipeline\ImportInstagram;
use App\Models\ImportJob;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| ImportInstagram missing/soft-deleted profile handling
|--------------------------------------------------------------------------
|
| A queued Instagram import must not crash (and retry into failed_jobs) when
| its Profile has been soft-deleted after queueing. It should drop the job.
|
*/

beforeEach(function () {
    config(['pixelfed.import.instagram.enabled' => true]);
});

it('drops the import job when the profile has been soft-deleted', function () {
    $user = User::factory()->create();
    $user->refresh();

    $job = new ImportJob;
    $job->profile_id = $user->profile_id;
    $job->service = 'instagram';
    $job->uuid = (string) \Illuminate\Support\Str::uuid();
    $job->stage = 0;
    $job->save();

    // Soft-delete the profile, as DeleteAccountPipeline does.
    Profile::whereId($user->profile_id)->delete();

    // Must not throw.
    (new ImportInstagram($job))->handle();

    // The orphaned job is cleaned up.
    expect(ImportJob::find($job->id))->toBeNull();
});

it('drops the import job when the job no longer exists', function () {
    $user = User::factory()->create();
    $user->refresh();

    $job = new ImportJob;
    $job->profile_id = $user->profile_id;
    $job->service = 'instagram';
    $job->uuid = (string) \Illuminate\Support\Str::uuid();
    $job->stage = 0;
    $job->save();

    $jobId = $job->id;
    $job->delete();

    // Must not throw even though the ImportJob row is gone.
    (new ImportInstagram($job))->handle();

    expect(ImportJob::find($jobId))->toBeNull();
});
