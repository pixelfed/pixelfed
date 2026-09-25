<?php

use App\Jobs\ImportPipeline\ImportInstagram;
use App\Models\ImportData;
use App\Models\ImportJob;
use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| ImportInstagram persists statuses (rendered NOT NULL)
|--------------------------------------------------------------------------
|
| statuses.rendered is TEXT NOT NULL. ImportInstagram created a Status without
| assigning rendered, so on strict DBs (SQLite/MariaDB/MySQL DB_STRICT=true)
| the INSERT was rejected and the import imported nothing. It must set rendered
| like every other Status writer.
|
*/

beforeEach(function () {
    config(['pixelfed.import.instagram.enabled' => true]);
    Queue::fake(); // don't run ImageOptimize
});

/**
 * Build an ImportJob for a single photo with a real media.json + source file
 * and a matching ImportData row on disk, so ImportInstagram::handle() reaches
 * the Status insert.
 */
function setupImportJobForSinglePhoto(User $user, string $filename): ImportJob
{
    $uuid = (string) Str::uuid();
    $base = 'imports/'.$user->id.'/'.$uuid;
    $mediaDir = storage_path('app/'.$base);
    if (! is_dir($mediaDir)) {
        mkdir($mediaDir, 0755, true);
    }

    // The source media file the job will move.
    $sourceRel = $base.'/'.$filename;
    file_put_contents(storage_path('app/'.$sourceRel), 'FAKEJPEGBYTES');

    // media.json describing one photo.
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

afterEach(function () {
    // Best-effort cleanup of any files created under the import dir.
    $dir = storage_path('app/imports');
    if (is_dir($dir)) {
        exec('rm -rf '.escapeshellarg($dir));
    }
    $pub = storage_path('app/public/m');
    if (is_dir($pub)) {
        exec('rm -rf '.escapeshellarg($pub));
    }
});

it('imports a photo status with rendered set (no NOT NULL violation)', function () {
    $user = User::factory()->create();
    $user->refresh();

    $job = setupImportJobForSinglePhoto($user, 'aaaaaaaaaaaaaaaaaaaaaaaa_1.jpg');

    (new ImportInstagram($job))->handle();

    $status = Status::whereProfileId($user->profile_id)->first();

    expect($status)->not->toBeNull()
        ->and($status->type)->toBe('photo')
        ->and($status->scope)->toBe('unlisted')
        ->and($status->rendered)->toBe('');
});
