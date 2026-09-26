<?php

namespace App\Jobs\HomeFeedPipeline;

use App\Models\Status;
use App\Services\FollowerService;
use App\Services\HomeTimelineService;
use App\Services\SnowflakeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FeedFollowPipeline implements ShouldBeUniqueUntilProcessing, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $actorId;

    protected $followingId;

    public $timeout = 900;

    public $tries = 3;

    public $maxExceptions = 1;

    public $failOnTimeout = true;

    /**
     * The number of seconds after which the job's unique lock will be released.
     *
     * @var int
     */
    public $uniqueFor = 3600;

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

        // Only backfill when a real relationship exists. This job is also
        // dispatched on unblock/unmute, where no follow may exist — backfilling
        // then would leak the target's posts (including followers-only ones)
        // into a non-follower's home feed.
        $isSelf = $actorId == $followingId;
        $isFollowing = $isSelf || FollowerService::follows($actorId, $followingId);

        if (! $isFollowing) {
            return;
        }

        // Followers-only (private) posts require an accepted follow. A pending
        // follow request creates no Follower row, so follows() is false for it;
        // include private only for self or an accepted follower.
        $visibility = ['public', 'unlisted'];
        if ($isSelf || FollowerService::follows($actorId, $followingId)) {
            $visibility[] = 'private';
        }

        $minId = SnowflakeService::byDate(now()->subWeeks(6));

        $ids = Status::where('id', '>', $minId)
            ->where('profile_id', $followingId)
            ->whereNull(['in_reply_to_id', 'reblog_of_id'])
            ->whereIn('type', ['photo', 'photo:album', 'video', 'video:album', 'photo:video:album'])
            ->whereIn('visibility', $visibility)
            ->orderByDesc('id')
            ->limit(HomeTimelineService::FOLLOWER_FEED_POST_LIMIT)
            ->pluck('id');

        foreach ($ids as $id) {
            HomeTimelineService::add($actorId, $id);
        }
    }
}
