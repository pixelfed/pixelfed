<?php

namespace App\Console\Commands;

use App\Media;
use DB;
use Illuminate\Console\Command;

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
        DB::transaction(function () {
            Media::whereIn('mime', ['image/jpeg', 'image/png', 'image/jpg'])
                ->chunk(50, function ($medias) {
                    foreach ($medias as $media) {
                        \App\Jobs\ImageOptimizePipeline\ImageThumbnail::dispatch($media);
                    }
                });
        });
    }
}
