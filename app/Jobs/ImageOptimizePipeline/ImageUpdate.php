<?php

namespace App\Jobs\ImageOptimizePipeline;

use App\Jobs\MediaPipeline\MediaStoragePipeline;
use App\Models\Media;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ImageUpdate implements ShouldQueue
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
        MediaStoragePipeline::dispatch($this->media);
    }
}
