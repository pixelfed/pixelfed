<?php

use App\Jobs\MediaPipeline\MediaStoragePipeline;
use App\Jobs\VideoPipeline\VideoThumbnail;
use App\Models\Media;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Bus;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;

uses(LazilyRefreshDatabase::class);

// Builds the media row the job deserializes. A status_id is set so the job's
// cache-busting branch runs, matching production.
function strandedVideoMedia(): Media
{
    $user = User::factory()->create();
    $user->refresh();

    return Media::create([
        'profile_id' => $user->profile->id,
        'user_id' => $user->id,
        'status_id' => null,
        'media_path' => 'public/m/_v2/1/aa/bb/clip.mp4',
        'mime' => 'video/mp4',
        'size' => 1024,
        'remote_media' => false,
        'order' => 0,
    ]);
}

it('generates a frame-0 thumbnail for a sub-1s video and dispatches storage', function () {
    Bus::fake();

    $media = strandedVideoMedia();

    $requestedSeconds = null;

    $exporter = Mockery::mock();
    $exporter->shouldReceive('export')->andReturnSelf();
    $exporter->shouldReceive('toDisk')->andReturnSelf();
    $exporter->shouldReceive('save')->andReturn('saved');

    $opened = Mockery::mock();
    $opened->shouldReceive('getDurationInSeconds')->andReturn(0);
    $opened->shouldReceive('getFrameFromSeconds')->andReturnUsing(function ($seconds) use (&$requestedSeconds, $exporter) {
        $requestedSeconds = $seconds;

        return $exporter;
    });

    $spy = Mockery::mock();
    $spy->shouldReceive('open')->andReturn($opened);
    FFMpeg::swap($spy);

    (new VideoThumbnail($media))->handle();

    expect($requestedSeconds)->toBe(0);
    expect($media->fresh()->thumbnail_path)->toBe('public/m/_v2/1/aa/bb/clip_thumb.jpeg');

    Bus::assertDispatched(MediaStoragePipeline::class);
});

it('does not strand the video when thumbnail extraction fails', function () {
    Bus::fake();

    $media = strandedVideoMedia();

    $exporter = Mockery::mock();
    $exporter->shouldReceive('export')->andReturnSelf();
    $exporter->shouldReceive('toDisk')->andReturnSelf();
    $exporter->shouldReceive('save')->andThrow(new RuntimeException('seek past EOF'));

    $opened = Mockery::mock();
    $opened->shouldReceive('getDurationInSeconds')->andReturn(0);
    $opened->shouldReceive('getFrameFromSeconds')->andReturn($exporter);

    $spy = Mockery::mock();
    $spy->shouldReceive('open')->andReturn($opened);
    FFMpeg::swap($spy);

    (new VideoThumbnail($media))->handle();

    expect($media->fresh()->thumbnail_path)->toBeNull();

    Bus::assertDispatched(MediaStoragePipeline::class);
});
