<?php

namespace App\Jobs\ImageOptimizePipeline;

use App\Jobs\MediaPipeline\ProcessImage;
use App\Models\Media;
use App\Services\Media\ImageProcessor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ImageThumbnail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $media;

    public $deleteWhenMissingModels = true;

    public function __construct(Media $media)
    {
        $this->media = $media;
    }

    public function handle(): void
    {
        ProcessImage::dispatch($this->media, ImageProcessor::MODE_THUMBNAIL);
    }
}
