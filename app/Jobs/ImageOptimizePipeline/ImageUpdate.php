<?php

namespace App\Jobs\ImageOptimizePipeline;

use App\Jobs\MediaPipeline\MediaStoragePipeline;
use App\Media;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use ImageOptimizer;
use Log;
use Storage;

class ImageUpdate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $media;

    protected $protectedMimes = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/avif',
    ];

    /**
     * Delete the job if its models no longer exist.
     *
     * @var bool
     */
    public $deleteWhenMissingModels = true;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Media $media)
    {
        $this->media = $media;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $media = $this->media;
        if (! $media) {
            return;
        }

        $disk = Storage::disk(config('filesystems.default'));
        $localFs = config('filesystems.default') === 'local';
        $mediaPath = $media->media_path;
        $fileSize = 0;

        if ($localFs) {
            $path = storage_path('app/'.$media->media_path);
            $thumbPath = storage_path('app/'.$media->thumbnail_path);
            if (! is_file($path)) {
                return;
            }
            $mediaPath = $path;
        } else {
            if (! $disk->exists($media->media_path)) {
                return;
            }
        }

        if ((bool) config_cache('pixelfed.optimize_image') && $localFs) {
            if (in_array($media->mime, $this->protectedMimes) == true) {
                try {
                    $thumbPath = storage_path('app/'.$media->thumbnail_path);
                    if (file_exists($thumbPath)) {
                        ImageOptimizer::optimize($thumbPath);
                    }

                    if (! $media->skip_optimize) {
                        $mediaPath = storage_path('app/'.$media->media_path);
                        ImageOptimizer::optimize($mediaPath);
                    }
                } catch (\Exception $e) {
                    if (config('app.dev_log')) {
                        Log::error('Image optimization failed: '.$e->getMessage());
                    }
                }
            }
        } elseif ((bool) config_cache('pixelfed.optimize_image') && ! $localFs) {
            if (in_array($media->mime, $this->protectedMimes) == true) {
                $this->optimizeRemoteImages($media, $disk);
            }
        }

        try {
            $photo_size = $this->getFileSize($media->media_path);
            $thumb_size = $media->thumbnail_path ? $this->getFileSize($media->thumbnail_path) : 0;
            $total = ($photo_size + $thumb_size);
            $media->size = $total;
            $media->save();
        } catch (\Exception $e) {
            if (config('app.dev_log')) {
                Log::error('Failed to calculate media sizes: '.$e->getMessage());
            }
        }

        MediaStoragePipeline::dispatch($media);
    }

    protected function getFileSize($path)
    {
        $disk = Storage::disk(config('filesystems.default'));
        $localFs = config('filesystems.default') === 'local';

        if (! $path) {
            return 0;
        }

        if ($localFs) {
            return filesize(storage_path('app/'.$path)) ?? 0;
        } else {
            return $disk->size($path) ?? 0;
        }
    }

    /**
     * Optimize images stored on remote storage (S3, etc)
     */
    protected function optimizeRemoteImages($media, $disk)
    {
        try {
            $tempDir = sys_get_temp_dir().'/pixelfed_optimize_'.Str::random(18);
            mkdir($tempDir, 0755, true);

            if ($media->thumbnail_path) {
                $tempThumb = $tempDir.'/thumb_'.basename($media->thumbnail_path);
                $thumbContents = $disk->get($media->thumbnail_path);
                file_put_contents($tempThumb, $thumbContents);

                ImageOptimizer::optimize($tempThumb);

                $disk->put($media->thumbnail_path, file_get_contents($tempThumb));
                unlink($tempThumb);
            }

            if (! $media->skip_optimize) {
                $tempMedia = $tempDir.'/media_'.basename($media->media_path);
                $mediaContents = $disk->get($media->media_path);
                file_put_contents($tempMedia, $mediaContents);

                ImageOptimizer::optimize($tempMedia);

                $disk->put($media->media_path, file_get_contents($tempMedia));
                unlink($tempMedia);
            }

            rmdir($tempDir);

        } catch (\Exception $e) {
            if (isset($tempDir) && is_dir($tempDir)) {
                array_map('unlink', glob($tempDir.'/*'));
                rmdir($tempDir);
            }
            if (config('app.dev_log')) {
                Log::error('Remote image optimization failed: '.$e->getMessage());
            }
        }
    }
}
