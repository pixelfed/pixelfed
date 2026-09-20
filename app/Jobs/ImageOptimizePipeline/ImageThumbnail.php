<?php

namespace App\Jobs\ImageOptimizePipeline;

use App\Models\Media;
use App\Util\Media\Image;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ImageThumbnail implements ShouldQueue
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
        if (! $media) {
            return;
        }

        $localFs = config('filesystems.default') === 'local';

        if ($localFs) {
            $path = storage_path('app/'.$media->media_path);
            if (! is_file($path)) {
                return;
            }
        } else {
            $disk = Storage::disk(config('filesystems.default'));
            if (! $disk->exists($media->media_path)) {
                return;
            }
        }

        try {
            $img = new Image;
            $img->resizeThumbnail($media);
        } catch (\Throwable $e) {
            // Keep going: returning here left the media without processed_at and
            // never dispatched ImageUpdate, so it never reached cloud storage and
            // its status never federated.
            Log::error("ImageThumbnail: media {$media->id} has no thumbnail [".$e::class.']: '.$e->getMessage());
        }

        $media->processed_at = now();
        $media->save();

        ImageUpdate::dispatch($media)->onQueue('mmo');
    }
}
