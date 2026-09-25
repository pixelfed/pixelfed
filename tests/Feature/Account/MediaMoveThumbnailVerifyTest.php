<?php

use App\Models\Media;
use App\Models\Status;
use App\Models\User;
use App\Services\ResilientMediaStorageService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| admin:MediaMoveStorageLocalToCloud thumbnail verify (regression)
|--------------------------------------------------------------------------
|
| In resilient mode the thumbnail upload is routed to an alternate disk, so a
| successful copy does not mean the configured cloud disk holds it. The local
| thumbnail must not be deleted unless a verified copy exists on the configured
| cloud disk; otherwise thumbnails 404 and the row is marked replicated and
| never retried.
|
*/

beforeEach(function () {
    Config::set('filesystems.cloud', 's3');
    Config::set('pixelfed.cloud_storage', true);
    Config::set('instance.enable_cc', false);

    // Resilient mode routes the 2nd upload (the thumbnail) to an alt disk.
    Config::set('media.storage.remote.resilient_mode', true);
    Config::set('filesystems.disks.alt-primary.enabled', true);

    Storage::fake('local');
    Storage::fake('s3', ['url' => 'https://cdn.test']);
    Storage::fake('alt-primary', ['url' => 'https://alt.test']);

    // The attempt counter is a process-wide static; reset it so the primary
    // targets the configured cloud disk and the thumbnail targets the alt.
    ResilientMediaStorageService::$attempts = 0;

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

it('does not delete the local thumbnail when it is not on the configured cloud disk', function () {
    $media = makeLocalMediaWithThumb();

    $this->artisan('admin:MediaMoveStorageLocalToCloud', ['--force' => true, '--limit' => 1])
        ->assertExitCode(1);

    // Primary reached the configured cloud disk; thumbnail was routed to alt.
    expect(Storage::disk('s3')->exists($media->media_path))->toBeTrue();
    expect(Storage::disk('s3')->exists($media->thumbnail_path))->toBeFalse();

    // The local thumbnail must survive because no verified cloud copy exists.
    expect(Storage::disk('local')->exists($media->thumbnail_path))->toBeTrue();

    // The row must stay re-selectable (not marked replicated).
    $media->refresh();
    expect($media->cdn_url)->toBeNull();
    expect($media->replicated_at)->toBeNull();
    expect((string) $media->version)->toBe('3');
});
