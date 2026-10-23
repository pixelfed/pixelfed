<?php

use App\Jobs\ImageOptimizePipeline\ImageOptimize;
use App\Jobs\ImageOptimizePipeline\ImageThumbnail;
use App\Jobs\ImageOptimizePipeline\ImageUpdate;
use App\Models\Media;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Image optimize — missing source file
|--------------------------------------------------------------------------
|
| When the file backing a media row is gone, the optimize jobs used to
| bare-return: no log, no dispatch, and processed_at stayed null, so the
| hourly media:optimize catch-up re-queued the row forever. The jobs now
| log and set skip_optimize so the catch-up loop skips it for good.
|
*/

beforeEach(function () {
    Config::set('filesystems.default', 'local');
});

function seedStrandedImageMedia(): Media
{
    $user = User::factory()->create();
    $user->refresh();

    return Media::create([
        'profile_id' => $user->profile->id,
        'user_id' => $user->id,
        'media_path' => 'public/m/_v2/missing/does-not-exist.jpg',
        'mime' => 'image/jpeg',
        'size' => 0,
        'remote_media' => false,
        'order' => 0,
        'status_id' => 1,
        'remote_url' => null,
        'processed_at' => null,
    ]);
}

it('marks and logs a stranded row instead of silently returning', function () {
    Log::spy();
    Bus::fake([ImageUpdate::class]);

    $media = seedStrandedImageMedia();

    expect(fn () => (new ImageThumbnail($media))->handle())->not->toThrow(Throwable::class);

    $media->refresh();

    expect($media->skip_optimize)->toBeTrue();
    expect($media->processed_at)->toBeNull();

    Log::shouldHaveReceived('error')
        ->withArgs(fn ($message) => str_contains($message, "media {$media->id} source file missing"))
        ->once();

    Bus::assertNotDispatched(ImageUpdate::class);
});

it('stops the media:optimize catch-up from re-queueing a stranded row', function () {
    $media = seedStrandedImageMedia();

    (new ImageThumbnail($media))->handle();
    $media->refresh();

    // The row still matches the catch-up selection query by column, so it is
    // only the skip_optimize guard that prevents an infinite re-dispatch.
    $stillMatchesQuery = Media::query()
        ->whereNull('processed_at')
        ->whereNull('remote_url')
        ->whereNull('deleted_at')
        ->whereNotNull('status_id')
        ->whereNotNull('media_path')
        ->whereIn('mime', ['image/jpg', 'image/jpeg', 'image/png'])
        ->whereKey($media->id)
        ->exists();

    expect($stillMatchesQuery)->toBeTrue();

    Bus::fake([ImageOptimize::class]);
    Artisan::call('media:optimize');

    Bus::assertNotDispatched(ImageOptimize::class);
});
