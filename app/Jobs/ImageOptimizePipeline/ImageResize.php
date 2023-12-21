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
        if(!$media) {
            return;
        }
        $path = storage_path('app/'.$media->media_path);
        if (!is_file($path) || $media->skip_optimize) {
            Log::info('Tried to optimize media that does not exist or is not readable. ' . $path);
            return;
        }

        if(!config('pixelfed.optimize_image')) {
        	ImageThumbnail::dispatch($media)->onQueue('mmo');
        	return;
        }
        try {
            $img = new Image();
            $img->resizeImage($media);
        } catch (Exception $e) {
            Log::error($e);
        }

        ImageThumbnail::dispatch($media)->onQueue('mmo');
    }
}
