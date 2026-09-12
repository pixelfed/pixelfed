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
| Media storage migration commands
|--------------------------------------------------------------------------
|
| admin:MediaMoveStorageLocalToCloud / unstable:MediaMoveStorageCloudToLocal
| Move media between local and cloud disks, verify by size/sha256, GC the
| source, and manage the PF_ENABLE_CLOUD .env flag for hot migrations.
|
*/

beforeEach(function () {
    Config::set('filesystems.cloud', 's3');
    Storage::fake('local');
    Storage::fake('s3', ['url' => 'https://cdn.test']);

    // Use a throwaway env file so the command's env edits don't touch the
    // real one. App::useEnvironmentPath expects a directory; the app resolves
    // the environment-specific filename (e.g. .env.testing) itself.
    $this->originalEnvPath = app()->environmentPath();
    $dir = sys_get_temp_dir().'/pf-env-test-'.uniqid();
    mkdir($dir);
    app()->useEnvironmentPath($dir);
    file_put_contents(app()->environmentFilePath(), "APP_KEY=base64:test\nPF_ENABLE_CLOUD=false\n");
});

afterEach(function () {
    // Restore the real environment path so we don't leak into other test files.
    if (isset($this->originalEnvPath)) {
        app()->useEnvironmentPath($this->originalEnvPath);
    }
});

function makeCloudMedia(): Media
{
    $user = User::factory()->create();
    $user->refresh();
    $pid = $user->profile->id;
    $status = Status::factory()->create(['profile_id' => $pid, 'type' => 'photo']);

    $path = 'public/m/_v2/'.$pid.'/aa/bb/file.jpg';
    $thumb = 'public/m/_v2/'.$pid.'/aa/bb/file_thumb.jpeg';

    // Put the files on the cloud disk only.
    Storage::disk('s3')->put($path, 'PRIMARY-BYTES-1234567890');
    Storage::disk('s3')->put($thumb, 'THUMB-BYTES');

    return Media::create([
        'status_id' => $status->id,
        'profile_id' => $pid,
        'user_id' => $user->id,
        'media_path' => $path,
        'thumbnail_path' => $thumb,
        'cdn_url' => Storage::disk('s3')->url($path),
        'thumbnail_url' => Storage::disk('s3')->url($thumb),
        'optimized_url' => Storage::disk('s3')->url($path),
        'mime' => 'image/jpeg',
        'size' => strlen('PRIMARY-BYTES-1234567890'),
        'remote_media' => false,
        'version' => 4,
        'replicated_at' => now(),
        'order' => 0,
    ]);
}

describe('unstable:MediaMoveStorageCloudToLocal', function () {
    it('downloads cloud media to local, clears cloud urls and deletes the cloud copy', function () {
        $media = makeCloudMedia();

        $this->artisan('unstable:MediaMoveStorageCloudToLocal', ['--force' => true])
            ->assertExitCode(0);

        // File is now on local disk.
        expect(Storage::disk('local')->exists($media->media_path))->toBeTrue();
        // Cloud copy removed (GC), thumbnail too.
        expect(Storage::disk('s3')->exists($media->media_path))->toBeFalse();

        $media->refresh();
        expect($media->cdn_url)->toBeNull();
        expect($media->optimized_url)->toBeNull();
        expect($media->thumbnail_url)->toBeNull();
        expect($media->replicated_at)->toBeNull();
        expect((string) $media->version)->toBe('3');
    });

    it('keeps the cloud copy with --keep-cloud', function () {
        $media = makeCloudMedia();

        $this->artisan('unstable:MediaMoveStorageCloudToLocal', ['--force' => true, '--keep-cloud' => true])
            ->assertExitCode(0);

        expect(Storage::disk('local')->exists($media->media_path))->toBeTrue();
        expect(Storage::disk('s3')->exists($media->media_path))->toBeTrue();
    });

    it('does not modify anything in dry-run', function () {
        $media = makeCloudMedia();

        $this->artisan('unstable:MediaMoveStorageCloudToLocal', ['--force' => true, '--dry-run' => true])
            ->assertExitCode(0);

        expect(Storage::disk('local')->exists($media->media_path))->toBeFalse();
        expect($media->fresh()->cdn_url)->not->toBeNull();
    });

    it('sets PF_ENABLE_CLOUD=false in .env and runtime when cloud is enabled', function () {
        // Start with cloud enabled.
        file_put_contents(app()->environmentFilePath(), "APP_KEY=base64:test\nPF_ENABLE_CLOUD=true\n");
        Config::set('pixelfed.cloud_storage', true);
        makeCloudMedia();

        $this->artisan('unstable:MediaMoveStorageCloudToLocal', ['--force' => true])
            ->assertExitCode(0);

        expect(file_get_contents(app()->environmentFilePath()))->toContain('PF_ENABLE_CLOUD="false"');
        expect(config('pixelfed.cloud_storage'))->toBeFalse();
    });
});

describe('admin:MediaMoveStorageLocalToCloud', function () {
    it('requires a configured cloud disk', function () {
        // Fake s3 disk has no url() host resolvable? Storage::fake provides a
        // url, so instead point cloud at a disk that throws.
        Config::set('filesystems.cloud', 'does-not-exist');

        $this->artisan('admin:MediaMoveStorageLocalToCloud', ['--force' => true])
            ->assertExitCode(1);
    });

    it('enables cloud storage before migrating (dry-run reports it)', function () {
        // cloud currently disabled (config_cache resolves falsy in tests).
        Config::set('pixelfed.cloud_storage', false);

        $this->artisan('admin:MediaMoveStorageLocalToCloud', ['--dry-run' => true])
            ->expectsOutputToContain('Cloud storage')
            ->assertExitCode(0);

        // dry-run must not write the .env.
        expect(file_get_contents(app()->environmentFilePath()))->toContain('PF_ENABLE_CLOUD=false');
    });

    it('does not fatal when there is no .env file (containerized deploy)', function () {
        // Point the app at an environment directory with no env file, so any
        // attempt to read/parse .env would throw file_get_contents() errors.
        // Cloud is disabled, so the command takes the setStorageEnv() path.
        $emptyDir = sys_get_temp_dir().'/pf-env-missing-'.uniqid();
        mkdir($emptyDir);
        app()->useEnvironmentPath($emptyDir);
        expect(is_file(app()->environmentFilePath()))->toBeFalse();

        Config::set('pixelfed.cloud_storage', false);

        // Regression: previously threw
        // "file_get_contents(.env): Failed to open stream" and exited 1.
        $this->artisan('admin:MediaMoveStorageLocalToCloud', ['--force' => true])
            ->assertExitCode(0);

        // The runtime config was still flipped even without a writable .env.
        expect(config('pixelfed.cloud_storage'))->toBeTrue();
    });

    it('migrates media whose local file no longer matches original_sha256 by default', function () {
        // The optimize pipeline (ImageResize/ImageUpdate) rewrites the local
        // file in place after upload but never updates original_sha256, so the
        // stored hash describes the pre-optimization bytes. The sha check must
        // therefore be OFF by default, otherwise every optimized image fails
        // verify (pixelfed#7203 follow-up: moved=0, failed=N).
        Config::set('pixelfed.cloud_storage', true);
        $media = makeLocalMediaWithStaleSha();

        $this->artisan('admin:MediaMoveStorageLocalToCloud', ['--force' => true])
            ->assertExitCode(0);

        // Uploaded to cloud and marked replicated despite the stale hash.
        expect(Storage::disk('s3')->exists($media->media_path))->toBeTrue();

        $media->refresh();
        expect((string) $media->version)->toBe('4');
        expect($media->cdn_url)->not->toBeNull();
        expect($media->replicated_at)->not->toBeNull();
    });

    it('fails verify for a stale original_sha256 only when --verify-sha256 is passed', function () {
        Config::set('pixelfed.cloud_storage', true);
        $media = makeLocalMediaWithStaleSha();

        $this->artisan('admin:MediaMoveStorageLocalToCloud', [
            '--force' => true,
            '--verify-sha256' => true,
        ])->assertExitCode(1);

        // Local copy left intact; row not marked migrated.
        expect(Storage::disk('local')->exists($media->media_path))->toBeTrue();

        $media->refresh();
        expect((string) $media->version)->not->toBe('4');
        expect($media->replicated_at)->toBeNull();
    });
});

/**
 * A local (not-yet-migrated) media row whose on-disk bytes intentionally do
 * NOT match its stored original_sha256, mirroring the post-optimization state
 * where the file was rewritten in place but original_sha256 kept the original
 * upload's hash.
 */
function makeLocalMediaWithStaleSha(): Media
{
    $user = User::factory()->create();
    $user->refresh();
    $pid = $user->profile->id;
    $status = Status::factory()->create(['profile_id' => $pid, 'type' => 'photo']);

    $path = 'public/m/_v2/'.$pid.'/cc/dd/optimized.jpg';
    $currentBytes = 'OPTIMIZED-BYTES-AFTER-RESIZE';

    // File on local disk holds the optimized bytes.
    Storage::disk('local')->put($path, $currentBytes);

    return Media::create([
        'status_id' => $status->id,
        'profile_id' => $pid,
        'user_id' => $user->id,
        'media_path' => $path,
        'cdn_url' => null,
        'mime' => 'image/jpeg',
        'size' => strlen($currentBytes),
        // Hash of some other (original upload) content, guaranteed to differ.
        'original_sha256' => hash('sha256', 'ORIGINAL-UPLOAD-BYTES-BEFORE-RESIZE'),
        'remote_media' => false,
        'version' => 3,
        'replicated_at' => null,
        'order' => 0,
    ]);
}
