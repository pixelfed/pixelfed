<?php

namespace App\Jobs\MediaPipeline;

use App\Media;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class MediaFixLocalFilesystemCleanupPipeline implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 1800;

    public $tries = 5;

    public $maxExceptions = 1;

    public function handle()
    {
        if ((bool) config_cache('pixelfed.cloud_storage') == false) {
            // Only run if cloud storage is enabled
            return;
        }

        $disk = Storage::disk('local');
        $cloud = Storage::disk(config('filesystems.cloud'));

        Media::whereNotNull(['status_id', 'cdn_url', 'replicated_at'])
            ->chunk(20, function ($medias) use ($disk, $cloud) {
                foreach ($medias as $media) {
                    if (! str_starts_with($media->media_path, 'public')) {
                        continue;
                    }

                    if ($disk->exists($media->media_path) && $cloud->exists($media->media_path)) {
                        $disk->delete($media->media_path);
                    }

                    if ($media->thumbnail_path) {
                        if ($disk->exists($media->thumbnail_path)) {
                            $disk->delete($media->thumbnail_path);
                        }
                    }

                    $paths = explode('/', $media->media_path);
                    if (count($paths) === 7) {
                        array_pop($paths);
                        $baseDir = implode('/', $paths);

                        if (count($disk->allFiles($baseDir)) === 0) {
                            $disk->deleteDirectory($baseDir);

                            array_pop($paths);
                            $baseDir = implode('/', $paths);

                            if (count($disk->allFiles($baseDir)) === 0) {
                                $disk->deleteDirectory($baseDir);

                                array_pop($paths);
                                $baseDir = implode('/', $paths);

                                if (count($disk->allFiles($baseDir)) === 0) {
                                    $disk->deleteDirectory($baseDir);
                                }
                            }
                        }
                    }
                }
            });
    }
}
