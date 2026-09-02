<?php

namespace App\Jobs\FollowPipeline;

use App\Models\FollowRequest;
use App\Services\FractalService;
use App\Transformer\ActivityPub\Verb\AcceptFollow;
use App\Util\ActivityPub\Helpers;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Attributes\DeleteWhenMissingModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

#[DeleteWhenMissingModels]
class FollowAcceptPipeline implements ShouldQueue
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

        // Verify follow request exists
        if (! $follow) {
            Log::info('FollowAcceptPipeline: Follow request no longer exists, skipping job');

            return;
        }

        $actor = $follow->actor;
        $target = $follow->target;

        // Verify actor and target exist
        if (! $actor) {
            Log::info("FollowAcceptPipeline: Actor no longer exists for follow request {$follow->id}, skipping job");

            return;
        }
        if (! $target) {
            Log::info("FollowAcceptPipeline: Target no longer exists for follow request {$follow->id}, skipping job");

            return;
        }

        if ($actor->domain == null || $actor->inbox_url == null || ! $target->private_key) {
            Log::info("FollowAcceptPipeline: Missing required fields for follow request {$follow->id}, skipping job");

            return;
        }

        try {
            $activity = FractalService::item($follow, new AcceptFollow);
            $url = $actor->sharedInbox ?? $actor->inbox_url;

            Helpers::sendSignedObject($target, $url, $activity);

            $follow->delete();
        } catch (\Exception $e) {
            Log::warning("FollowAcceptPipeline: Failed to process follow request {$follow->id}: ".$e->getMessage());
            throw $e;
        }

    }
}
