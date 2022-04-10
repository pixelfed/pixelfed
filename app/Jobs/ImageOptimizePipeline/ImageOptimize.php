<?php

namespace App\Jobs\ImageOptimizePipeline;

use App\Media;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

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
    	if(config('pixelfed.optimize_image') == false) {
    		return;
    	}

        $media = $this->media;
        $path = storage_path('app/'.$media->media_path);
        if (!is_file($path) || $media->skip_optimize) {
            return;
        }

        ImageResize::dispatch($media);
    }
}
