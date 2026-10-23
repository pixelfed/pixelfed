<?php

use App\Jobs\ImportPipeline\ImportInstagram;
use App\Models\ImportData;
use App\Models\ImportJob;
use App\Models\Media;
use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| ImportInstagram retry safety
|--------------------------------------------------------------------------
|
| The job copies the source file inside the DB transaction and deletes the
| original only after the transaction commits. If a write fails and the
| transaction rolls back, the source must still exist so a retry can
| re-process the photo.
|
| With the old destructive move, case (1) fails: the source is gone after a
| rolled-back attempt. With copy-then-delete-after-commit it survives, and
| case (2) proves a retry then completes and cleans up the source.
|
*/

beforeEach(function () {
    config(['pixelfed.import.instagram.enabled' => true]);
    Queue::fake();
});

afterEach(function () {
    Media::flushEventListeners();

    $dir = storage_path('app/imports');
    if (is_dir($dir)) {
        exec('rm -rf '.escapeshellarg($dir));
    }
    $pub = storage_path('app/public/m');
    if (is_dir($pub)) {
        foreach (glob($pub.'/*', GLOB_ONLYDIR) as $hashDir) {
            exec('rm -rf '.escapeshellarg($hashDir));
        }
    }
});

function setupRetrySafetyImportJob(User $user, string $filename): ImportJob
{
    $uuid = (string) Str::uuid();
    $base = 'imports/'.$user->id.'/'.$uuid;
    $mediaDir = storage_path('app/'.$base);
    if (! is_dir($mediaDir)) {
        mkdir($mediaDir, 0755, true);
    }

    $sourceRel = $base.'/'.$filename;
    file_put_contents(storage_path('app/'.$sourceRel), 'FAKEJPEGBYTES');

    $mediaJsonRel = $base.'/media.json';
    file_put_contents(storage_path('app/'.$mediaJsonRel), json_encode([
        'photos' => [
            [
                'caption' => 'imported caption',
                'taken_at' => '2021-05-01T12:00:00',
                'path' => 'media/posts/'.$filename,
            ],
        ],
    ]));

    $job = new ImportJob;
    $job->profile_id = $user->profile_id;
    $job->service = 'instagram';
    $job->uuid = $uuid;
    $job->stage = 5;
    $job->media_json = $mediaJsonRel;
    $job->save();

    $data = new ImportData;
    $data->profile_id = $user->profile_id;
    $data->job_id = $job->id;
    $data->service = 'instagram';
    $data->stage = 5;
    $data->path = $sourceRel;
    $data->original_name = $filename;
    $data->save();

    return $job;
}

it('keeps the source file and writes no rows when the transaction rolls back', function () {
    $user = User::factory()->create();
    $user->refresh();

    $job = setupRetrySafetyImportJob($user, 'retry_rollback_1.jpg');
    $importData = ImportData::whereJobId($job->id)->first();
    $sourcePath = storage_path('app/'.$importData->path);

    expect(is_file($sourcePath))->toBeTrue();

    Media::saving(function () {
        throw new RuntimeException('forced');
    });

    try {
        (new ImportInstagram($job))->handle();
    } catch (RuntimeException $e) {
        // expected: the forced failure propagates out of the transaction
    }

    expect(is_file($sourcePath))->toBeTrue()
        ->and(Status::count())->toBe(0)
        ->and(Media::count())->toBe(0);
});

it('creates records and removes the source when a retry succeeds', function () {
    $user = User::factory()->create();
    $user->refresh();

    $job = setupRetrySafetyImportJob($user, 'retry_success_1.jpg');
    $importData = ImportData::whereJobId($job->id)->first();
    $sourcePath = storage_path('app/'.$importData->path);

    (new ImportInstagram($job))->handle();

    $status = Status::whereProfileId($user->profile_id)->first();
    $media = Media::whereProfileId($user->profile_id)->first();

    expect($status)->not->toBeNull()
        ->and($media)->not->toBeNull()
        ->and($media->media_path)->not->toBeNull()
        ->and(is_file(storage_path('app/'.$media->media_path)))->toBeTrue()
        ->and(is_file($sourcePath))->toBeFalse();
});

it('creates records and removes the source on a normal run', function () {
    $user = User::factory()->create();
    $user->refresh();

    $job = setupRetrySafetyImportJob($user, 'retry_control_1.jpg');
    $importData = ImportData::whereJobId($job->id)->first();
    $sourcePath = storage_path('app/'.$importData->path);

    (new ImportInstagram($job))->handle();

    expect(Status::whereProfileId($user->profile_id)->count())->toBe(1)
        ->and(Media::whereProfileId($user->profile_id)->count())->toBe(1)
        ->and(is_file($sourcePath))->toBeFalse();
});
