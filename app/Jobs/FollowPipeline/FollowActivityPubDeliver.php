<?php

namespace App\Jobs\FollowPipeline;

use App\Models\FollowRequest;
use App\Services\FractalService;
use App\Transformer\ActivityPub\Verb\Follow;
use App\Util\ActivityPub\Helpers;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Attributes\DeleteWhenMissingModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

#[DeleteWhenMissingModels]
class FollowActivityPubDeliver implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $followRequest;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(FollowRequest $followRequest)
    {
        $this->followRequest = $followRequest;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $follow = $this->followRequest;
        $actor = $follow->actor;
        $target = $follow->target;

        if ($target->domain == null || $target->inbox_url == null || ! $actor->private_key) {
            return;
        }

        $activity = FractalService::item($follow, new Follow);
        $url = $target->sharedInbox ?? $target->inbox_url;

        Helpers::sendSignedObject($actor, $url, $activity);
    }
}
