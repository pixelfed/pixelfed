<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Media;
use DB;

class RegenerateThumbnails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'regenerate:thumbnails';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Regenerate thumbnails';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        DB::transaction(function() {
            Media::whereIn('mime', ['image/jpeg', 'image/png'])
                ->chunk(50, function($medias) {
                    foreach($medias as $media) {
                        \App\Jobs\ImageOptimizePipeline\ImageThumbnail::dispatch($media);
                    }
                });
        });
    }
}
