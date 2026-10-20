<?php

use App\Jobs\VideoPipeline\VideoHlsPipeline;
use App\Models\Media;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| HLS transcode — resilient to a missing local source
|--------------------------------------------------------------------------
|
| On cloud-storage instances the local mp4 can be uploaded and unlinked
| before the HLS job runs (the two run on separate queues). The job used to
| open the local file with no existence check and no fallback, so with
| maxExceptions=1 it failed permanently the moment the source was gone.
|
| These tests assert the recovery logic only. Real HLS output needs ffmpeg
| and fixtures, so the ffmpeg call itself is mocked away — end-to-end
| transcoding is out of scope here.
|
*/

beforeEach(function () {
    Config::set('media.hls.enabled', true);
    Config::set('filesystems.cloud', 's3');

    // Pass the ffmpeg dependency gate without shelling out to a real binary.
    Cache::forever('video-pipeline:hls:depcheck', 'ok');
});

function makeMissingLocalVideoMedia(): Media
{
    $user = User::factory()->create();
    $user->refresh();
    $pid = $user->profile->id;

    return Media::create([
        'profile_id' => $pid,
        'user_id' => $user->id,
        'media_path' => 'public/m/_v/'.$pid.'/clip.mp4',
        'mime' => 'video/mp4',
        'size' => 1024,
        'remote_media' => false,
        'order' => 0,
        'hls_transcoded_at' => null,
    ]);
}

it('does not permanently fail when the local mp4 is missing and no cloud copy exists', function () {
    Storage::fake('local');
    Storage::fake('s3');

    $media = makeMissingLocalVideoMedia();

    // Stand in for real ffmpeg: opening a source that isn't on disk throws,
    // which with maxExceptions=1 is a permanent failure. The guard must stop
    // the job before it ever reaches this call.
    FFMpeg::shouldReceive('fromDisk')->andReturnUsing(function () {
        throw new RuntimeException('ffmpeg opened a missing source');
    });

    // Source is absent on both disks: the job must log and return instead of
    // throwing.
    (new VideoHlsPipeline($media))->handle();

    $media->refresh();

    expect($media->hls_transcoded_at)->toBeNull();
    expect($media->hls_path)->toBeNull();
});

it('fetches the mp4 from cloud to local before transcoding when the local copy is gone', function () {
    Storage::fake('local');
    Storage::fake('s3');

    $media = makeMissingLocalVideoMedia();

    // Only the cloud disk has the source, mirroring a post-upload state.
    Storage::disk('s3')->put($media->media_path, 'FAKE-MP4-BYTES');

    // Mock the transcode so the test asserts the fetch path, not ffmpeg.
    $opener = Mockery::mock();
    $opener->shouldReceive('open')->andReturnSelf();
    $opener->shouldReceive('exportForHLS')->andReturnSelf();
    $opener->shouldReceive('setSegmentLength')->andReturnSelf();
    $opener->shouldReceive('setKeyFrameInterval')->andReturnSelf();
    $opener->shouldReceive('addFormat')->andReturnSelf();
    $opener->shouldReceive('save')->once()->andReturnUsing(function () use ($media, &$opener) {
        // At save time the source must already be on the local disk.
        expect(Storage::disk('local')->exists($media->media_path))->toBeTrue();

        return $opener;
    });

    FFMpeg::shouldReceive('fromDisk')->with('local')->andReturn($opener);

    (new VideoHlsPipeline($media))->handle();

    $media->refresh();

    // Transcode ran against the fetched source and completion was recorded.
    expect($media->hls_transcoded_at)->not->toBeNull();
    expect($media->hls_path)->toBe(str_replace('.mp4', '.m3u8', $media->media_path));

    // The temporary local copy the job pulled down is cleaned up afterwards.
    expect(Storage::disk('local')->exists($media->media_path))->toBeFalse();
});
