<?php

use App\Models\Media;
use App\Models\Status;
use App\Models\User;
use App\Services\MediaStorageService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Cloud move AP object cache invalidation
|--------------------------------------------------------------------------
|
| When a local media file is moved to cloud storage the ActivityPub object
| cache (pf:status:ap:v1:sid:{id}) must be cleared so remote instances stop
| receiving attachment URLs that point at the now-removed local files.
|
*/

beforeEach(function () {
    Config::set('filesystems.cloud', 's3');
    Config::set('filesystems.default', 'local');
    Config::set('pixelfed.cloud_storage', true);
    Config::set('media.storage.remote.resilient_mode', false);
    Config::set('media.delete_local_after_cloud', false);

    Storage::fake('local');
    Storage::fake('s3', ['url' => 'https://cdn.test']);

    $this->localPaths = [];
});

afterEach(function () {
    foreach ($this->localPaths ?? [] as $p) {
        if (is_file($p)) {
            @unlink($p);
        }
    }
});

it('clears the ActivityPub object cache when moving media to cloud storage', function () {
    $user = User::factory()->create();
    $user->refresh();
    $pid = $user->profile->id;
    $status = Status::factory()->create(['profile_id' => $pid, 'type' => 'photo']);

    $path = 'public/m/_v2/'.$pid.'/aa/bb/file.jpg';
    $bytes = 'PRIMARY-BYTES-1234567890';

    // cloudMove() checks Storage::exists() on the default (local) disk and
    // reads the file via storage_path(); seed both so the move can proceed.
    Storage::disk('local')->put($path, $bytes);
    $realPath = storage_path('app/'.$path);
    if (! is_dir(dirname($realPath))) {
        mkdir(dirname($realPath), 0755, true);
    }
    file_put_contents($realPath, $bytes);
    $this->localPaths[] = $realPath;

    $media = Media::create([
        'status_id' => $status->id,
        'profile_id' => $pid,
        'user_id' => $user->id,
        'media_path' => $path,
        'cdn_url' => null,
        'mime' => 'image/jpeg',
        'size' => strlen($bytes),
        'remote_media' => false,
        'version' => 3,
        'replicated_at' => null,
        'order' => 0,
    ]);

    $key = 'pf:status:ap:v1:sid:'.$status->id;
    Cache::put($key, ['stale' => 'ap-object'], 3600);
    expect(Cache::has($key))->toBeTrue();

    expect(MediaStorageService::move($media))->toBe('success');

    expect(Cache::has($key))->toBeFalse();
});
