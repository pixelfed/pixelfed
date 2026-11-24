<?php

namespace App\Jobs\MovePipeline;

use App\Exceptions\InvalidDeliveryDestinationException;
use App\Models\Profile;
use App\Services\ActivityPubDeliveryService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\ThrottlesExceptions;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;

class MoveSendUndoFollowPipeline implements ShouldQueue
{
    use Queueable;

    public $follower;

    public $targetInbox;

    public $targetPid;

    public $actor;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 5;

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     *
     * @var int
     */
    public $maxExceptions = 3;

    /**
     * Get the middleware the job should pass through.
     *
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [
            new WithoutOverlapping('move-send-unfollow:'.$this->follower->id.':actor:'.$this->actor),
            (new ThrottlesExceptions(2, 5 * 60))->backoff(5),
        ];
    }

    /**
     * Create a new job instance.
     */
    public function __construct($follower, $targetInbox, $targetPid, $actor)
    {
        $this->follower = $follower;
        $this->targetInbox = $targetInbox;
        $this->targetPid = $targetPid;
        $this->actor = $actor;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $follower = $this->follower;
        $targetPid = $this->targetPid;
        $targetInbox = $this->targetInbox;
        $actor = $this->actor;

        // Verify follower and actor exists
        if (! $follower) {
            Log::info('MoveSendUndoFollowPipeline: Follower no longer exists, skipping job');

            return;
        }
        if (! $actor) {
            Log::info('MoveSendUndoFollowPipeline: Actor no longer exists, skipping job');

            return;
        }

        // Verify follower username and private_key exists
        if (! $follower->username) {
            Log::info('MoveSendUndoFollowPipeline: Follower may no longer exists, skipping job');

            return;
        }
        if (! $follower->private_key) {
            Log::info('MoveSendUndoFollowPipeline: Follower may no longer exists, skipping job');

            return;
        }

        if (! is_string($targetInbox) || trim($targetInbox) === '') {
            Log::info('MoveSendUndoFollowPipeline: Target inbox is missing, skipping job');

            return;
        }

        $profile = Profile::whereNull('domain')->whereNull('status')->find($follower->id);

        if (! $profile || ! $profile->private_key) {
            Log::info('MoveSendUndoFollowPipeline: Follower profile is not deliverable, skipping job');

            return;
        }

        $permalink = 'https://'.config('pixelfed.domain.app').'/users/'.$follower->username;

        $activity = [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'type' => 'Undo',
            'id' => $permalink.'#follow/'.$targetPid.'/undo',
            'actor' => $permalink,
            'object' => [
                'type' => 'Follow',
                'id' => $permalink.'#follows/'.$targetPid,
                'object' => $actor,
                'actor' => $permalink,
            ],
        ];

        try {
            ActivityPubDeliveryService::queue()
                ->from($profile)
                ->to($targetInbox)
                ->payload($activity)
                ->send();
        } catch (InvalidDeliveryDestinationException $e) {
            Log::info('MoveSendUndoFollowPipeline: Invalid target inbox, skipping job', [
                'profile_id' => $profile->id,
                'inbox' => $targetInbox,
                'reason' => $e->reason,
            ]);
        }
    }
}
