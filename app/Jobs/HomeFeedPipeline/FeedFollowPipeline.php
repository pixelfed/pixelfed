<?php

namespace App\Jobs\HomeFeedPipeline;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use App\Services\AccountService;
use App\Services\HomeTimelineService;
use App\Services\SnowflakeService;
use App\Status;

class FeedFollowPipeline implements ShouldQueue, ShouldBeUniqueUntilProcessing
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
        return 'hts:feed:insert:follows:aid:' . $this->actorId . ':fid:' . $this->followingId;
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

        $minId = SnowflakeService::byDate(now()->subMonths(6));

        $ids = Status::where('id', '>', $minId)
            ->where('profile_id', $followingId)
            ->whereNull(['in_reply_to_id', 'reblog_of_id'])
            ->whereIn('type', ['photo', 'photo:album', 'video', 'video:album', 'photo:video:album'])
            ->whereIn('visibility',['public', 'unlisted', 'private'])
            ->orderByDesc('id')
            ->limit(HomeTimelineService::FOLLOWER_FEED_POST_LIMIT)
            ->pluck('id');

        foreach($ids as $id) {
            HomeTimelineService::add($actorId, $id);
        }
    }
}
