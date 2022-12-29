<?php

namespace App\Jobs\MediaPipeline;

use App\Media;
use Cache;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Redis;
use App\Services\MediaStorageService;

class MediaStoragePipeline implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $media;
    public $deleteWhenMissingModels = true;

    public function __construct(Media $media)
    {
    	$this->media = $media;
    }

    public function handle()
    {
    	MediaStorageService::store($this->media);
    }

}
