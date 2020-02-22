<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Media;
use App\Jobs\VideoPipeline\VideoThumbnail as Pipeline;

class VideoThumbnail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'video:thumbnail';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate missing video thumbnails';

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
            Pipeline::dispatchNow($video);
        }
    }
}
