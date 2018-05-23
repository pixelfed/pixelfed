<?php

namespace App\Jobs\ImageOptimizePipeline;

use Carbon\Carbon;
use ImageOptimizer;
use App\{Media, Status};
use App\Util\Media\Image;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ImageResize implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $media;

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
        $path = storage_path('app/'. $media->media_path);
        if(!is_file($path)) {
            return;
        }

        try {
            $img = new Image;
            $img->resizeImage($media);
        } catch (Exception $e) {
            
        }

        ImageThumbnail::dispatch($media);
    }
}
