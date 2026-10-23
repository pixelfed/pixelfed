<?php

use App\Models\Media;
use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| unstable:MediaMoveStorageCloudToCloud
|--------------------------------------------------------------------------
|
| Cold S3->S3 migration: copy existing objects from the old bucket (s3-old)
| to the current cloud bucket (s3), verify, rewrite media URLs, GC the source.
|
*/

beforeEach(function () {
    Config::set('filesystems.cloud', 's3');
    // Destination (new) bucket.
    Storage::fake('s3', ['url' => 'https://cdneast.pixelfed.au']);
    // Source (old) bucket.
    Storage::fake('s3-old', ['url' => 'https://cdn.pixelfed.au']);
});

function makeOldBucketMedia(string $oldHost = 'https://cdn.pixelfed.au'): Media
{
    $user = User::factory()->create();
    $user->refresh();
    $pid = $user->profile->id;
    $status = Status::factory()->create(['profile_id' => $pid, 'type' => 'photo']);

    $path = 'public/m/_v2/'.$pid.'/aa/bb/file.jpg';
    $thumb = 'public/m/_v2/'.$pid.'/aa/bb/file_thumb.jpeg';

    // Files exist only on the OLD bucket.
    Storage::disk('s3-old')->put($path, 'PRIMARY-BYTES-1234567890');
    Storage::disk('s3-old')->put($thumb, 'THUMB-BYTES');

    return Media::create([
        'status_id' => $status->id,
        'profile_id' => $pid,
        'user_id' => $user->id,
        'media_path' => $path,
        'thumbnail_path' => $thumb,
        'cdn_url' => $oldHost.'/'.$path,
        'thumbnail_url' => $oldHost.'/'.$thumb,
        'optimized_url' => $oldHost.'/'.$path,
        'mime' => 'image/jpeg',
        'size' => strlen('PRIMARY-BYTES-1234567890'),
        'remote_media' => false,
        'version' => 4,
        'replicated_at' => now(),
        'order' => 0,
    ]);
}

it('copies old-bucket media to the new bucket, rewrites urls and GCs the source', function () {
    $media = makeOldBucketMedia();

    $this->artisan('unstable:MediaMoveStorageCloudToCloud', ['--force' => true])
        ->assertExitCode(0);

    // Copied to destination.
    expect(Storage::disk('s3')->exists($media->media_path))->toBeTrue();
    // Removed from source (GC).
    expect(Storage::disk('s3-old')->exists($media->media_path))->toBeFalse();

    $media->refresh();
    expect(parse_url($media->cdn_url, PHP_URL_HOST))->toBe('cdneast.pixelfed.au');
    expect(parse_url($media->thumbnail_url, PHP_URL_HOST))->toBe('cdneast.pixelfed.au');
    expect(parse_url($media->optimized_url, PHP_URL_HOST))->toBe('cdneast.pixelfed.au');
});

it('keeps the source objects with --keep-source', function () {
    $media = makeOldBucketMedia();

    $this->artisan('unstable:MediaMoveStorageCloudToCloud', ['--force' => true, '--keep-source' => true])
        ->assertExitCode(0);

    expect(Storage::disk('s3')->exists($media->media_path))->toBeTrue();
    expect(Storage::disk('s3-old')->exists($media->media_path))->toBeTrue();
});

it('makes no changes in dry-run', function () {
    $media = makeOldBucketMedia();

    $this->artisan('unstable:MediaMoveStorageCloudToCloud', ['--force' => true, '--dry-run' => true])
        ->assertExitCode(0);

    expect(Storage::disk('s3')->exists($media->media_path))->toBeFalse();
    expect(parse_url($media->fresh()->cdn_url, PHP_URL_HOST))->toBe('cdn.pixelfed.au');
});

it('skips media already pointing at the destination host', function () {
    // cdn_url already on the destination host -> nothing to do.
    $media = makeOldBucketMedia('https://cdneast.pixelfed.au');
    $before = $media->cdn_url;

    $this->artisan('unstable:MediaMoveStorageCloudToCloud', ['--force' => true])
        ->assertExitCode(0);

    expect($media->fresh()->cdn_url)->toBe($before);
    // Not copied (was skipped).
    expect(Storage::disk('s3')->exists($media->media_path))->toBeFalse();
});

it('errors when source and destination are the same disk', function () {
    $this->artisan('unstable:MediaMoveStorageCloudToCloud', ['--sourceDisk' => 's3', '--force' => true])
        ->assertExitCode(1);
});
