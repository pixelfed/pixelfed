<?php

namespace App\Jobs\HomeFeedPipeline;

use App\Models\Status;
use App\Services\HomeTimelineService;
use App\Services\SnowflakeService;
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
class FeedFollowPipeline implements ShouldBeUniqueUntilProcessing, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $actorId;

    protected $followingId;

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return 'hts:feed:insert:follows:aid:'.$this->actorId.':fid:'.$this->followingId;
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [(new WithoutOverlapping("hts:feed:insert:follows:aid:{$this->actorId}:fid:{$this->followingId}"))->shared()->dontRelease()];
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
            Log::info('FeedFollowPipeline: Actor ID not provided, skipping job');

            return;
        }

        // Verify following ID exists
        if (! $followingId) {
            Log::info('FeedFollowPipeline: Following ID not provided, skipping job');

            return;
        }

        $minId = SnowflakeService::byDate(now()->subWeeks(6));

        $ids = Status::where('id', '>', $minId)
            ->where('profile_id', $followingId)
            ->whereNull(['in_reply_to_id', 'reblog_of_id'])
            ->whereIn('type', ['photo', 'photo:album', 'video', 'video:album', 'photo:video:album'])
            ->whereIn('visibility', ['public', 'unlisted', 'private'])
            ->orderByDesc('id')
            ->limit(HomeTimelineService::FOLLOWER_FEED_POST_LIMIT)
            ->pluck('id');

        foreach ($ids as $id) {
            HomeTimelineService::add($actorId, $id);
        }
    }
}
