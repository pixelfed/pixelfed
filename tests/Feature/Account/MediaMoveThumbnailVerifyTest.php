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
| admin:MediaMoveStorageLocalToCloud thumbnail verify (regression)
|--------------------------------------------------------------------------
|
| The local thumbnail must only be deleted once a copy is verified on the
| configured cloud disk. Both the primary and the thumbnail first-attempt to
| the configured cloud disk, so after a verified copy exists the local files
| are safe to remove.
|
*/

beforeEach(function () {
    Config::set('filesystems.cloud', 's3');
    Config::set('pixelfed.cloud_storage', true);
    Config::set('instance.enable_cc', false);

    Config::set('media.storage.remote.resilient_mode', true);
    Config::set('filesystems.disks.alt-primary.enabled', true);

    Storage::fake('local');
    Storage::fake('s3', ['url' => 'https://cdn.test']);
    Storage::fake('alt-primary', ['url' => 'https://alt.test']);

    $this->originalEnvPath = app()->environmentPath();
    $dir = sys_get_temp_dir().'/pf-env-thumb-'.uniqid();
    mkdir($dir);
    app()->useEnvironmentPath($dir);
    file_put_contents(app()->environmentFilePath(), "APP_KEY=base64:test\nPF_ENABLE_CLOUD=true\n");
});

afterEach(function () {
    if (isset($this->originalEnvPath)) {
        app()->useEnvironmentPath($this->originalEnvPath);
    }
});

function makeLocalMediaWithThumb(): Media
{
    $user = User::factory()->create();
    $user->refresh();
    $pid = $user->profile->id;
    $status = Status::factory()->create(['profile_id' => $pid, 'type' => 'photo']);

    $path = 'public/m/_v2/'.$pid.'/aa/bb/file.jpg';
    $thumb = 'public/m/_v2/'.$pid.'/aa/bb/file_thumb.jpeg';

    Storage::disk('local')->put($path, 'PRIMARY-BYTES-1234567890');
    Storage::disk('local')->put($thumb, 'THUMB-BYTES');

    return Media::create([
        'status_id' => $status->id,
        'profile_id' => $pid,
        'user_id' => $user->id,
        'media_path' => $path,
        'thumbnail_path' => $thumb,
        'cdn_url' => null,
        'mime' => 'image/jpeg',
        'size' => strlen('PRIMARY-BYTES-1234567890'),
        'remote_media' => false,
        'version' => 3,
        'replicated_at' => null,
        'order' => 0,
    ]);
}

it('deletes the local thumbnail only after it is verified on the configured cloud disk', function () {
    $media = makeLocalMediaWithThumb();

    $this->artisan('admin:MediaMoveStorageLocalToCloud', ['--force' => true, '--limit' => 1])
        ->assertExitCode(0);

    // Primary and thumbnail both first-attempt to the configured cloud disk.
    expect(Storage::disk('s3')->exists($media->media_path))->toBeTrue();
    expect(Storage::disk('s3')->exists($media->thumbnail_path))->toBeTrue();

    // Neither object leaks to an alternate disk on the first attempt.
    expect(Storage::disk('alt-primary')->exists($media->media_path))->toBeFalse();
    expect(Storage::disk('alt-primary')->exists($media->thumbnail_path))->toBeFalse();

    // The verified cloud copy allows the local files to be removed.
    expect(Storage::disk('local')->exists($media->media_path))->toBeFalse();
    expect(Storage::disk('local')->exists($media->thumbnail_path))->toBeFalse();

    // The row is marked replicated once the cloud copy is authoritative.
    $media->refresh();
    expect($media->cdn_url)->not->toBeNull();
    expect($media->replicated_at)->not->toBeNull();
    expect((string) $media->version)->toBe('4');
});
