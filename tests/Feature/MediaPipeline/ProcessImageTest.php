<?php

use App\Jobs\ImageOptimizePipeline\ImageOptimize;
use App\Jobs\ImageOptimizePipeline\ImageResize;
use App\Jobs\ImageOptimizePipeline\ImageThumbnail;
use App\Jobs\ImageOptimizePipeline\ImageUpdate;
use App\Jobs\MediaPipeline\MediaStoragePipeline;
use App\Jobs\MediaPipeline\ProcessImage;
use App\Models\Media;
use App\Models\User;
use App\Services\Media\ImageProcessor;
use App\Util\Media\Blurhash;
use App\Util\Media\ImageDriverManager;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Spatie\LaravelImageOptimizer\Facades\ImageOptimizer;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    try {
        ImageDriverManager::createImageManager()->createImage(1, 1);
    } catch (Throwable $e) {
        test()->markTestSkipped('Image driver "'.config('image.driver').'" is not available: '.$e->getMessage());
    }

    Config::set('filesystems.default', 's3');
    Config::set('pixelfed.optimize_image', true);
    Storage::fake('s3', ['url' => 'https://cdn.test']);
    Bus::fake([MediaStoragePipeline::class]);
});

function processImageJpeg(int $width, int $height): string
{
    $im = imagecreatetruecolor($width, $height);
    imagefilledrectangle($im, 0, 0, $width - 1, $height - 1, imagecolorallocate($im, 51, 102, 204));
    ob_start();
    imagejpeg($im, null, 90);

    return ob_get_clean();
}

function processImageSeed(string $bytes, string $filename = 'photo.jpg', string $mime = 'image/jpeg', array $attributes = []): Media
{
    $user = User::factory()->create();
    $user->refresh();
    $pid = $user->profile->id;
    $path = 'public/m/_v2/'.$pid.'/aa/bb/'.$filename;

    Storage::disk('s3')->put($path, $bytes);

    return Media::create(array_merge([
        'profile_id' => $pid,
        'user_id' => $user->id,
        'media_path' => $path,
        'mime' => $mime,
        'size' => strlen($bytes),
        'remote_media' => false,
        'order' => 0,
    ], $attributes));
}

it('resizes, thumbnails and hashes an upload in one job', function () {
    $media = processImageSeed(processImageJpeg(3000, 2000));
    $disk = Storage::disk('s3');

    (new ProcessImage($media))->handle();
    $media->refresh();

    expect($media->media_path)->toEndWith('/photo.jpg')
        ->and($media->thumbnail_path)->toEndWith('/photo_thumb.jpg')
        ->and($media->mime)->toBe('image/jpg')
        ->and([$media->width, $media->height])->toEqual([1620, 1080])
        ->and($media->orientation)->toBe('landscape')
        ->and($media->blurhash)->toBeString()->not->toBe(Blurhash::DEFAULT_HASH)
        ->and($media->processed_at)->not->toBeNull()
        ->and($media->size)->toEqual($disk->size($media->media_path) + $disk->size($media->thumbnail_path))
        ->and(array_slice(getimagesizefromstring($disk->get($media->media_path)), 0, 2))->toBe([1620, 1080])
        ->and(array_slice(getimagesizefromstring($disk->get($media->thumbnail_path)), 0, 2))->toBe([640, 640]);

    Bus::assertDispatched(MediaStoragePipeline::class);
});

it('removes the original when the output extension changes', function () {
    $media = processImageSeed(processImageJpeg(1200, 800), 'photo.jpeg');
    $original = $media->media_path;

    (new ProcessImage($media))->handle();
    $media->refresh();

    expect($media->media_path)->toEndWith('/photo.jpg')
        ->and(Storage::disk('s3')->exists($media->media_path))->toBeTrue()
        ->and(Storage::disk('s3')->exists($original))->toBeFalse();
});

it('skips media that was already processed unless forced', function () {
    $bytes = processImageJpeg(3000, 2000);
    $media = processImageSeed($bytes, attributes: ['processed_at' => now()]);

    (new ProcessImage($media))->handle();
    $media->refresh();

    expect($media->thumbnail_path)->toBeNull()
        ->and(Storage::disk('s3')->get($media->media_path))->toBe($bytes);

    Bus::assertNotDispatched(MediaStoragePipeline::class);

    (new ProcessImage($media, force: true))->handle();
    $media->refresh();

    expect($media->thumbnail_path)->not->toBeNull()
        ->and([$media->width, $media->height])->toEqual([1620, 1080]);
});

it('only rebuilds the thumbnail in thumbnail mode', function () {
    $bytes = processImageJpeg(1620, 1080);
    $media = processImageSeed($bytes, attributes: ['processed_at' => now()->subDay()]);

    (new ProcessImage($media, ImageProcessor::MODE_THUMBNAIL))->handle();
    $media->refresh();

    expect(Storage::disk('s3')->get($media->media_path))->toBe($bytes)
        ->and($media->width)->toBeNull()
        ->and($media->thumbnail_path)->toEndWith('/photo_thumb.jpg')
        ->and(Storage::disk('s3')->exists($media->thumbnail_path))->toBeTrue();
});

it('leaves the main image alone when optimize_image is off', function () {
    Config::set('pixelfed.optimize_image', false);

    $bytes = processImageJpeg(3000, 2000);
    $media = processImageSeed($bytes);

    (new ProcessImage($media))->handle();
    $media->refresh();

    expect(Storage::disk('s3')->get($media->media_path))->toBe($bytes)
        ->and([$media->width, $media->height])->toEqual([3000, 2000])
        ->and($media->thumbnail_path)->toEndWith('/photo_thumb.jpg')
        ->and($media->processed_at)->not->toBeNull();
});

it('keeps and stores the original when the image cannot be decoded', function () {
    $media = processImageSeed('definitely not an image');

    (new ProcessImage($media))->handle();
    $media->refresh();

    expect($media->processed_at)->not->toBeNull()
        ->and($media->thumbnail_path)->toBeNull()
        ->and(Storage::disk('s3')->get($media->media_path))->toBe('definitely not an image');

    Bus::assertDispatched(MediaStoragePipeline::class);
});

it('marks unsupported mime types as processed and stores them as they are', function () {
    $media = processImageSeed('GIF89a', 'photo.gif', 'image/gif');

    (new ProcessImage($media))->handle();
    $media->refresh();

    expect($media->processed_at)->not->toBeNull()
        ->and($media->thumbnail_path)->toBeNull();

    Bus::assertDispatched(MediaStoragePipeline::class);
});

it('runs the file optimizer for gd', function () {
    Config::set('image.driver', 'gd');
    ImageDriverManager::flush();
    ImageOptimizer::spy();

    (new ProcessImage(processImageSeed(processImageJpeg(3000, 2000))))->handle();

    ImageOptimizer::shouldHaveReceived('optimize')->twice();
});

it('does not run the file optimizer for vips', function () {
    try {
        ImageDriverManager::createImageManager([], 'vips')->createImage(1, 1);
    } catch (Throwable $e) {
        test()->markTestSkipped('libvips is not available: '.$e->getMessage());
    }

    Config::set('image.driver', 'vips');
    ImageDriverManager::flush();
    ImageOptimizer::spy();

    (new ProcessImage(processImageSeed(processImageJpeg(3000, 2000))))->handle();

    ImageOptimizer::shouldNotHaveReceived('optimize');
});

it('drops duplicate dispatches for the same media', function () {
    Bus::fake();
    $media = processImageSeed(processImageJpeg(800, 600));

    ProcessImage::dispatch($media);
    ProcessImage::dispatch($media);
    ProcessImage::dispatch($media, force: true);
    ProcessImage::dispatch($media, ImageProcessor::MODE_THUMBNAIL);

    Bus::assertDispatchedTimes(ProcessImage::class, 3);
});

it('forwards jobs queued by the old pipeline', function () {
    Bus::fake();

    (new ImageOptimize(processImageSeed(processImageJpeg(800, 600))))->handle();
    (new ImageResize(processImageSeed(processImageJpeg(800, 600))))->handle();
    (new ImageThumbnail(processImageSeed(processImageJpeg(800, 600))))->handle();
    (new ImageUpdate(processImageSeed(processImageJpeg(800, 600))))->handle();

    Bus::assertDispatchedTimes(ProcessImage::class, 3);
    Bus::assertDispatched(ProcessImage::class, fn (ProcessImage $job) => $job->mode === ImageProcessor::MODE_THUMBNAIL);
    Bus::assertDispatched(MediaStoragePipeline::class);
});

it('runs image processing and storage on separate queues', function () {
    $media = processImageSeed(processImageJpeg(800, 600));

    expect((new ProcessImage($media))->queue)->toBe('mmo')
        ->and((new MediaStoragePipeline($media))->queue)->toBe('media-store');
});
