<?php

namespace App\Jobs\ImageOptimizePipeline;

use App\Models\Media;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ImageOptimize implements ShouldQueue
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
        if (! $media) {
            Log::info('ImageOptimize: Media no longer exists, skipping job');

            return;
        }

        // Verify media has required path
        if (! $media->media_path) {
            Log::info("ImageOptimize: Media {$media->id} has no media_path, skipping job");

            return;
        }

        $localFs = config('filesystems.default') === 'local';

        if ($localFs) {
            $path = storage_path('app/'.$media->media_path);
            if (! is_file($path)) {
                // Source is gone, so mark the row to stop the hourly catch-up
                // from re-queueing it forever instead of silently returning.
                Log::error("ImageOptimize: media {$media->id} source file missing at {$media->media_path}, cannot process");
                $media->skip_optimize = true;
                $media->save();

                return;
            }
        } else {
            $disk = Storage::disk(config('filesystems.default'));
            if (! $disk->exists($media->media_path)) {
                Log::error("ImageOptimize: media {$media->id} source file missing at {$media->media_path}, cannot process");
                $media->skip_optimize = true;
                $media->save();

                return;
            }
        }

        if ($media->skip_optimize) {
            return;
        }

        if ((bool) config_cache('pixelfed.optimize_image') === false) {
            ImageThumbnail::dispatch($media)->onQueue('mmo');

            return;
        }
        ImageResize::dispatch($media)->onQueue('mmo');

    }
}
