<?php

namespace App\Console\Commands;

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
        $medias = Media::whereNotNull('status_id')->whereNull('processed_at')->take(250)->get();
        foreach ($medias as $media) {
            ImageOptimize::dispatch($media);
        }
    }
}
