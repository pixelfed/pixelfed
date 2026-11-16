<?php

use App\Jobs\ImportPipeline\ImportInstagram;
use App\Models\ImportData;
use App\Models\ImportJob;
use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    config(['pixelfed.import.instagram.enabled' => true]);
    Queue::fake(); // don't run ImageOptimize
});

function setupImportJobForSinglePhoto(User $user, string $filename): ImportJob
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

afterEach(function () {
    foreach (ImportJob::pluck('media_json') as $mediaJson) {
        File::deleteDirectory(storage_path('app/'.dirname($mediaJson)));
    }
    foreach (ImportData::pluck('original_name') as $name) {
        foreach (glob(storage_path('app/public/m/*/*/'.$name)) ?: [] as $file) {
            File::delete($file);
            @rmdir(dirname($file));
        }
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
