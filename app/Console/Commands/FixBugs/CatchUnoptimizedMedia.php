<?php

namespace App\Console\Commands\FixBugs;

use App\Jobs\ImageOptimizePipeline\ImageOptimize;
use App\Models\Media;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('media:optimize')]
#[Description('Find and optimize media that has not yet been optimized.')]
class CatchUnoptimizedMedia extends Command
{
    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $hasLimit = (bool) config('media.image_optimize.catch_unoptimized_media_hour_limit');
        Media::whereNull('processed_at')
            ->when($hasLimit, function ($q, $hasLimit) {
                $q->where('created_at', '>', now()->subHours(1));
            })->whereNull('remote_url')
            ->whereNotNull('status_id')
            ->whereNotNull('media_path')
            ->whereIn('mime', [
                'image/jpg',
                'image/jpeg',
                'image/png',
            ])
            ->chunk(50, function ($medias) {
                foreach ($medias as $media) {
                    if ($media->skip_optimize) {
                        continue;
                    }
                    ImageOptimize::dispatch($media);
                }
            });
    }
}
