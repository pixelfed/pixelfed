<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Media;
use App\Jobs\VideoPipeline\VideoPostProcess as Pipeline;

class VideoPostProcess extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'video:postprocess';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Apply required postprocessing (convert video/quicktime to video/mp4)';

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
        $videos = Media::whereMime('video/quicktime')
                        ->take($limit)
                        ->get();
        foreach($videos as $video) {
            Pipeline::dispatchNow($video);
        }
    }
}
