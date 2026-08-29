<?php

use App\Models\Media;
use App\Models\Status;
use App\Models\User;
use App\Services\ConfigCacheService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Config;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| admin:MigrateLocalMediaURL
|--------------------------------------------------------------------------
|
| Rebuilds stale local media URLs (cdn_url, thumbnail_url, optimized_url)
| from their storage paths using the configured cloud disk host.
|
*/

beforeEach(function () {
    // Point the cloud disk at a deterministic host with path-style urls so
    // Storage::disk('s3')->url($path) == https://cdn.test/<path>.
    Config::set('filesystems.cloud', 's3');
    Config::set('filesystems.disks.s3', [
        'driver' => 's3',
        'key' => 'test',
        'secret' => 'test',
        'region' => 'us-east-1',
        'bucket' => 'bucket',
        'url' => 'https://cdn.test',
        'endpoint' => 'https://cdn.test',
        'use_path_style_endpoint' => true,
        'visibility' => 'public',
    ]);
    // Enable cloud storage (config_cache reads from ConfigCacheService).
    ConfigCacheService::put('pixelfed.cloud_storage', true);
});

function makeStatusWithStaleMedia(string $staleHost = 'https://s3.old.example'): Media
{
    $user = User::factory()->create();
    $user->refresh();
    $pid = $user->profile->id;

    $status = Status::factory()->create(['profile_id' => $pid, 'type' => 'video']);

    $path = 'public/m/_v2/'.$pid.'/aa/bb/file.mp4';
    $thumbPath = 'public/m/_v2/'.$pid.'/aa/bb/file_thumb.jpeg';

    return Media::create([
        'status_id' => $status->id,
        'profile_id' => $pid,
        'user_id' => $user->id,
        'media_path' => $path,
        'thumbnail_path' => $thumbPath,
        'cdn_url' => 'https://cdn.test/'.$path,          // already correct
        'thumbnail_url' => $staleHost.'/'.$thumbPath,     // stale
        'optimized_url' => $staleHost.'/'.$path,          // stale
        'mime' => 'video/mp4',
        'remote_media' => false,
        'order' => 0,
    ]);
}

it('rebuilds stale thumbnail_url and optimized_url but leaves correct cdn_url', function () {
    $media = makeStatusWithStaleMedia();

    $this->artisan('admin:MigrateLocalMediaURL', ['id' => (string) $media->status_id, '--force' => true])
        ->assertExitCode(0);

    $media->refresh();
    expect($media->cdn_url)->toBe('https://cdn.test/'.$media->media_path);
    expect($media->thumbnail_url)->toBe('https://cdn.test/'.$media->thumbnail_path);
    expect($media->optimized_url)->toBe('https://cdn.test/'.$media->media_path);
    expect(parse_url($media->thumbnail_url, PHP_URL_HOST))->toBe('cdn.test');
    expect(parse_url($media->optimized_url, PHP_URL_HOST))->toBe('cdn.test');
});

it('does not change anything in dry-run mode', function () {
    $media = makeStatusWithStaleMedia();
    $originalThumb = $media->thumbnail_url;

    $this->artisan('admin:MigrateLocalMediaURL', ['id' => (string) $media->status_id, '--dry-run' => true])
        ->assertExitCode(0);

    expect($media->fresh()->thumbnail_url)->toBe($originalThumb);
});

it('leaves already-correct media untouched', function () {
    $user = User::factory()->create();
    $user->refresh();
    $pid = $user->profile->id;
    $status = Status::factory()->create(['profile_id' => $pid, 'type' => 'photo']);
    $path = 'public/m/_v2/'.$pid.'/aa/bb/ok.jpg';

    $media = Media::create([
        'status_id' => $status->id,
        'profile_id' => $pid,
        'media_path' => $path,
        'cdn_url' => 'https://cdn.test/'.$path,
        'thumbnail_url' => 'https://cdn.test/'.$path,
        'mime' => 'image/jpeg',
        'remote_media' => false,
        'order' => 0,
    ]);

    $updatedAt = $media->fresh()->updated_at;

    $this->artisan('admin:MigrateLocalMediaURL', ['id' => (string) $status->id, '--force' => true])
        ->assertExitCode(0);

    expect($media->fresh()->updated_at->eq($updatedAt))->toBeTrue();
});

it('never rewrites remote media', function () {
    $user = User::factory()->create();
    $user->refresh();
    $pid = $user->profile->id;
    $status = Status::factory()->create(['profile_id' => $pid, 'type' => 'photo']);

    $media = Media::create([
        'status_id' => $status->id,
        'profile_id' => $pid,
        'media_path' => 'https://remote.example/image.jpg',
        'cdn_url' => 'https://s3.old.example/image.jpg',
        'remote_media' => true,
        'remote_url' => 'https://remote.example/image.jpg',
        'mime' => 'image/jpeg',
        'order' => 0,
    ]);

    $this->artisan('admin:MigrateLocalMediaURL', ['id' => (string) $status->id, '--force' => true])
        ->assertExitCode(0);

    // Unchanged: remote media is skipped.
    expect($media->fresh()->cdn_url)->toBe('https://s3.old.example/image.jpg');
});

it('requires an id or --all', function () {
    $this->artisan('admin:MigrateLocalMediaURL')
        ->assertExitCode(1);
});

it('refuses to run on a local-storage instance', function () {
    // Simulate local storage: cloud disabled.
    ConfigCacheService::put('pixelfed.cloud_storage', false);

    $this->artisan('admin:MigrateLocalMediaURL', ['--all' => true, '--force' => true])
        ->expectsOutputToContain('Cloud storage is not enabled')
        ->assertExitCode(1);
});

it('with --oldDomain only rewrites URLs on that host', function () {
    // thumbnail_url on s3.old.example, optimized_url on other.example.
    $media = makeStatusWithStaleMedia('https://s3.old.example');
    $media->optimized_url = 'https://other.example/'.$media->media_path;
    $media->save();

    $this->artisan('admin:MigrateLocalMediaURL', [
        'id' => (string) $media->status_id,
        '--oldDomain' => 's3.old.example',
        '--force' => true,
    ])->assertExitCode(0);

    $media->refresh();
    // Matched the filter -> rewritten.
    expect(parse_url($media->thumbnail_url, PHP_URL_HOST))->toBe('cdn.test');
    // Did NOT match the filter -> left as-is.
    expect(parse_url($media->optimized_url, PHP_URL_HOST))->toBe('other.example');
});

it('with --newDomain override rewrites to the given host', function () {
    $media = makeStatusWithStaleMedia('https://s3.old.example');

    $this->artisan('admin:MigrateLocalMediaURL', [
        'id' => (string) $media->status_id,
        '--newDomain' => 'media.example',
        '--force' => true,
    ])->assertExitCode(0);

    $media->refresh();
    expect(parse_url($media->thumbnail_url, PHP_URL_HOST))->toBe('media.example');
    expect(parse_url($media->optimized_url, PHP_URL_HOST))->toBe('media.example');
});
