<?php

namespace App\Console\Commands\Internal;

use App\Jobs\StoryPipeline\StoryExpire;
use App\Jobs\StoryPipeline\StoryRotateMedia;
use App\Models\Story;
use App\Services\StoryService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('story:gc')]
#[Description('Clear expired Stories')]
class GarbageCollectorStory extends Command
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
        $this->archiveExpiredStories();
        $this->rotateMedia();
    }

    protected function archiveExpiredStories()
    {
        $stories = Story::whereActive(true)
            ->where('expires_at', '<', now())
            ->get();

        foreach ($stories as $story) {
            StoryExpire::dispatch($story)->onQueue('story');
        }
    }

    protected function rotateMedia()
    {
        $queue = StoryService::rotateQueue();

        if (! $queue || count($queue) == 0) {
            return;
        }

        collect($queue)
            ->each(function ($id) {
                $story = StoryService::getById($id);
                if (! $story) {
                    StoryService::removeRotateQueue($id);

                    return;
                }
                if ($story->created_at->gt(now()->subMinutes(20))) {
                    return;
                }
                StoryRotateMedia::dispatch($story)->onQueue('story');
                StoryService::removeRotateQueue($id);

            });
    }
}
