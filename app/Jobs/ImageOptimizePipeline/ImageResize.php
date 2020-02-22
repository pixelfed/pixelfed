<?php

namespace App\Jobs\ImageOptimizePipeline;

use App\Media;
use App\Util\Media\Image;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

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
        if (!is_file($path)) {
            return;
        }

        try {
            $img = new Image();
            $img->resizeImage($media);
        } catch (Exception $e) {
        }

        ImageThumbnail::dispatch($media);
    }
}
