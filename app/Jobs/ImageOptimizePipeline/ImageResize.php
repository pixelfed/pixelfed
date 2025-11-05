<?php

namespace App\Jobs\ImageOptimizePipeline;

use App\Media;
use App\Util\Media\Image;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Log;
use Storage;

class ImageResize implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $media;

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
        
        // Verify media exists
        if (!$media) {
            Log::info("ImageResize: Media no longer exists, skipping job");
            return;
        }

        // Verify media has required path
        if (!$media->media_path) {
            Log::info("ImageResize: Media {$media->id} has no media_path, skipping job");
            return;
        }

        $localFs = config('filesystems.default') === 'local';

        if ($localFs) {
            $path = storage_path('app/'.$media->media_path);
            if (! is_file($path) || $media->skip_optimize) {
                return;
            }
        } else {
            $disk = Storage::disk(config('filesystems.default'));
            if (! $disk->exists($media->media_path) || $media->skip_optimize) {
                return;
            }
        }

        if ((bool) config_cache('pixelfed.optimize_image') === false) {
            ImageThumbnail::dispatch($media)->onQueue('mmo');

            return;
        }

        try {
            $img = new Image;
            $img->resizeImage($media);
        } catch (\Exception $e) {
            if (config('app.dev_log')) {
                Log::error('Image resize failed: '.$e->getMessage());
            }
        }

        ImageThumbnail::dispatch($media)->onQueue('mmo');
    }
}
