<?php

namespace App\Jobs\StoryPipeline;

use App\Models\Story;
use App\Services\ActivityPubDeliveryService;
use App\Services\FollowerService;
use App\Services\FractalService;
use App\Services\StoryService;
use App\Transformer\ActivityPub\Verb\CreateStory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Attributes\DeleteWhenMissingModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

#[DeleteWhenMissingModels]
class StoryFanout implements ShouldQueue
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
        $profile = $story->profile;

        if ($story->local == false || $story->remote_url) {
            return;
        }

        StoryService::delLatest($story->profile_id);

        $audience = FollowerService::softwareAudience($story->profile_id, 'pixelfed');

        if (empty($audience)) {
            return;
        }

        $activity = FractalService::item($story, new CreateStory);

        ActivityPubDeliveryService::pool($profile, $audience, $activity);
    }
}
