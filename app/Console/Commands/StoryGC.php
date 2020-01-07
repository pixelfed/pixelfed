<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\{
    DB,
    Storage
};
use App\{
    Story,
    StoryView
};

class StoryGC extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'story:gc';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear expired Stories';

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
        $stories = Story::where('expires_at', '<', now())->take(50)->get();

        if($stories->count() == 0) {
            exit;
        }

        foreach($stories as $story) {
            if(Storage::exists($story->path) == true) {
                Storage::delete($story->path);
            }
            DB::transaction(function() use($story) {
                StoryView::whereStoryId($story->id)->delete();
                $story->delete();
            });
        }
    }
}
