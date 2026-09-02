<?php

namespace App\Jobs\HomeFeedPipeline;

use App\Services\HomeTimelineService;
use App\Services\StatusService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Attributes\FailOnTimeout;
use Illuminate\Queue\Attributes\MaxExceptions;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Queue\Attributes\UniqueFor;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

#[Timeout(900)]
#[Tries(3)]
#[MaxExceptions(1)]
#[FailOnTimeout]
#[UniqueFor(3600)]
class FeedUnfollowPipeline implements ShouldBeUniqueUntilProcessing, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $actorId;

    protected $followingId;

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return 'hts:feed:remove:follows:aid:'.$this->actorId.':fid:'.$this->followingId;
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [(new WithoutOverlapping("hts:feed:remove:follows:aid:{$this->actorId}:fid:{$this->followingId}"))->shared()->dontRelease()];
    }

    /**
     * Create a new job instance.
     */
    public function __construct($actorId, $followingId)
    {
        $this->actorId = $actorId;
        $this->followingId = $followingId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $actorId = $this->actorId;
        $followingId = $this->followingId;

        // Verify actor ID exists
        if (! $actorId) {
            Log::info('FeedUnfollowPipeline: Actor ID not provided, skipping job');

            return;
        }

        // Verify following ID exists
        if (! $followingId) {
            Log::info('FeedUnfollowPipeline: Following ID not provided, skipping job');

            return;
        }

        $ids = HomeTimelineService::get($actorId, 0, -1);
        foreach ($ids as $id) {
            $status = StatusService::get($id, false);
            if ($status && isset($status['account'], $status['account']['id'])) {
                if ($status['account']['id'] == $followingId) {
                    HomeTimelineService::rem($actorId, $id);
                }
            }
        }
    }
}
