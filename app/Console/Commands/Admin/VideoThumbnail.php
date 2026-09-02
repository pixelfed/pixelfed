<?php

namespace App\Console\Commands\Admin;

use App\Jobs\VideoPipeline\VideoThumbnail as Pipeline;
use App\Models\Media;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('video:thumbnail')]
#[Description('Generate missing video thumbnails')]
class VideoThumbnail extends Command
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
        $limit = 10;
        $videos = Media::whereMime('video/mp4')
            ->whereNull('thumbnail_path')
            ->take($limit)
            ->get();
        foreach ($videos as $video) {
            Pipeline::dispatch($video);
        }
    }
}
