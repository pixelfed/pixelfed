<?php

namespace App\Console\Commands\Admin;

use App\Jobs\ImageOptimizePipeline\ImageThumbnail;
use App\Models\Media;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('regenerate:thumbnails')]
#[Description('Regenerate thumbnails')]
class RegenerateThumbnails extends Command
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
        DB::transaction(function () {
            Media::whereIn('mime', ['image/jpeg', 'image/png', 'image/jpg'])
                ->chunk(50, function ($medias) {
                    foreach ($medias as $media) {
                        ImageThumbnail::dispatch($media);
                    }
                });
        });
    }
}
