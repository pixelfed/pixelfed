<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Story;
use App\StoryView;

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
        $this->directoryScan();
        $this->deleteViews();
        $this->deleteStories();
    }

    protected function directoryScan()
    {
        $day = now()->day;

        if($day !== 3) {
            return;
        }

        $monthHash = substr(hash('sha1', date('Y').date('m')), 0, 12);

        $t1 = Storage::directories('public/_esm.t1');
        $t2 = Storage::directories('public/_esm.t2');

        $dirs = array_merge($t1, $t2);

        foreach($dirs as $dir) {
            $hash = last(explode('/', $dir));
            if($hash != $monthHash) {
                $this->info('Found directory to delete: ' . $dir);
                $this->deleteDirectory($dir);
            }
        }
    }

    protected function deleteDirectory($path)
    {
        Storage::deleteDirectory($path);
    }

    protected function deleteViews()
    {
        StoryView::where('created_at', '<', now()->subDays(2))->delete();
    }

    protected function deleteStories()
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
