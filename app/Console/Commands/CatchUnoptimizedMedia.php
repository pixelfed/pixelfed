<?php

namespace App\Console\Commands;

use DB;
use App\Jobs\ImageOptimizePipeline\ImageOptimize;
use App\Media;
use Illuminate\Console\Command;

class CatchUnoptimizedMedia extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'media:optimize';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Find and optimize media that has not yet been optimized.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(Media $media)
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
        Media::whereNull('processed_at')
            ->where('created_at', '>', now()->subHours(1))
            ->where('skip_optimize', '!=', true)
            ->whereNull('remote_url')
            ->whereNotNull('status_id')
            ->whereNotNull('media_path')
            ->whereIn('mime', [
                'image/jpeg',
                'image/png',
            ])
            ->chunk(50, function($medias) {
                foreach ($medias as $media) {
                    ImageOptimize::dispatch($media);
                }
            });
    }
}
