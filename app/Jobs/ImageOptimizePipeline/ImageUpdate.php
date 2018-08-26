<?php

namespace App\Jobs\ImageOptimizePipeline;

use ImageOptimizer;
use App\{Media, Status};
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ImageUpdate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $media;

    protected $protectedMimes = [
        'image/gif',
        'image/bmp',
        'video/mp4'
    ];

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
        $thumb = storage_path('app/'. $media->thumbnail_path);
        try {
            if(!in_array($media->mime, $this->protectedMimes))
            {
                ImageOptimizer::optimize($thumb);
                ImageOptimizer::optimize($path);
            }
        } catch (Exception $e) {
            return;
        }
        if(!is_file($path) || !is_file($thumb)) {
            return;
        }
        $photo_size = filesize($path);
        $thumb_size = filesize($thumb);
        $total = ($photo_size + $thumb_size);
        $media->size = $total;
        $media->save();
    }
}
