<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Media;
use App\Jobs\ImageOptimizePipeline\ImageOptimize;

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
        $medias = Media::whereNull('processed_at')->take(50)->get();
        foreach($medias as $media) {
            ImageOptimize::dispatch($media);
        }
    }
}
