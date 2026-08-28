<?php

namespace App\Jobs\StoryPipeline;

use App\Services\ActivityPubDeliveryService;
use App\Services\FollowerService;
use App\Services\StoryService;
use App\Story;
use App\Transformer\ActivityPub\Verb\CreateStory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use League\Fractal;
use League\Fractal\Serializer\ArraySerializer;

class StoryFanout implements ShouldQueue
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
        $profile = $story->profile;

        if ($story->local == false || $story->remote_url) {
            return;
        }

        StoryService::delLatest($story->profile_id);

        $audience = FollowerService::softwareAudience($story->profile_id, 'pixelfed');

        if (empty($audience)) {
            return;
        }

        $fractal = new Fractal\Manager;
        $fractal->setSerializer(new ArraySerializer);
        $resource = new Fractal\Resource\Item($story, new CreateStory);
        $activity = $fractal->createData($resource)->toArray();

        ActivityPubDeliveryService::pool($profile, $audience, $activity);
    }
}
