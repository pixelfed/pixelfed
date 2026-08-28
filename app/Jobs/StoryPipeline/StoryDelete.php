<?php

namespace App\Jobs\StoryPipeline;

use App\Services\ActivityPubDeliveryService;
use App\Services\FollowerService;
use App\Services\StoryService;
use App\Story;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class StoryDelete implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $story;

    /**
     * Delete the job if its models no longer exist.
     *
     * @var bool
     */
    public $deleteWhenMissingModels = true;

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

        if (Storage::exists($story->path) == true) {
            Storage::delete($story->path);
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
