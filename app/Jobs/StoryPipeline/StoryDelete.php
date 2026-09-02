<?php

namespace App\Jobs\StoryPipeline;

use App\Models\Story;
use App\Services\ActivityPubDeliveryService;
use App\Services\FollowerService;
use App\Services\StoryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Attributes\DeleteWhenMissingModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

#[DeleteWhenMissingModels]
class StoryDelete implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $story;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Story $story)
    {
        $this->story = $story;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $story = $this->story;

        if ($story->local == false) {
            return;
        }

        StoryService::removeRotateQueue($story->id);
        StoryService::delLatest($story->profile_id);
        StoryService::delById($story->id);

        if ($story->path && Storage::exists($story->path) == true) {
            Storage::delete($story->path);

            // Remove the now-empty leaf dir this story's media lived in (either
            // the live public/_esm.t3 tree or the story_archives tree once the
            // story has been rotated on expiry).
            $dir = implode('/', array_slice(explode('/', $story->path), 0, -1));
            if ($dir !== '' && empty(Storage::files($dir))) {
                Storage::deleteDirectory($dir);
            }
        }

        $story->views()->delete();

        $profile = $story->profile;

        $activity = [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id' => $story->url().'#delete',
            'type' => 'Delete',
            'actor' => $profile->permalink(),
            'object' => [
                'id' => $story->url(),
                'type' => 'Story',
            ],
        ];

        $audience = FollowerService::softwareAudience($profile->id, 'pixelfed');

        if (! empty($audience)) {
            ActivityPubDeliveryService::pool($profile, $audience, $activity);
        }

        $story->delete();
    }
}
