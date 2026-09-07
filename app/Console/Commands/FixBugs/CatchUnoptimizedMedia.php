<?php

namespace App\Console\Commands\FixBugs;

use App\Jobs\ImageOptimizePipeline\ImageOptimize;
use App\Models\Media;
use Illuminate\Console\Command;

class CatchUnoptimizedMedia extends Command
{
    protected $signature = 'media:optimize
        {--limit=1000 : Maximum number of media items to queue}';

    protected $description = 'Find and optimize media that has not yet been optimized.';

    public function handle(): int
    {
        $hasHourLimit = (bool) config(
            'media.image_optimize.catch_unoptimized_media_hour_limit'
        );

        $limit = max(1, (int) $this->option('limit'));

        $query = Media::query()
            ->whereNull('processed_at')
            ->whereNull('remote_url')
            ->whereNull('deleted_at')
            ->whereNotNull('status_id')
            ->whereNotNull('media_path')
            ->whereIn('mime', [
                'image/jpg',
                'image/jpeg',
                'image/png',
            ]);

        if ($hasHourLimit) {
            $query->where('created_at', '>', now()->subHour());
        }

        $medias = $query
            ->orderBy('created_at')
            ->orderBy('id')
            ->limit($limit)
            ->get();

        foreach ($medias as $media) {
            if ($media->skip_optimize) {
                continue;
            }

            ImageOptimize::dispatch($media);
        }

        $this->info("Queued {$medias->count()} media candidates.");

        return self::SUCCESS;
    }
}
