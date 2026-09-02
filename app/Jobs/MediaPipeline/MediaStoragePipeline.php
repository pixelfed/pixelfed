<?php

namespace App\Jobs\MediaPipeline;

use App\Models\Media;
use App\Services\MediaStorageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Attributes\DeleteWhenMissingModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

#[DeleteWhenMissingModels]
class MediaStoragePipeline implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $media;

    public function __construct(Media $media)
    {
        $this->media = $media;
    }

    public function handle()
    {
        $media = $this->media;

        // Verify media exists
        if (! $media) {
            Log::info('MediaStoragePipeline: Media no longer exists, skipping job');

            return;
        }

        // Verify media has required fields
        if (! $media->media_path) {
            Log::info("MediaStoragePipeline: Media {$media->id} has no media_path, skipping job");

            return;
        }

        try {
            MediaStorageService::store($media);
        } catch (\Exception $e) {
            Log::warning("MediaStoragePipeline: Failed to store media {$media->id}: ".$e->getMessage());
            throw $e;
        }
    }
}
