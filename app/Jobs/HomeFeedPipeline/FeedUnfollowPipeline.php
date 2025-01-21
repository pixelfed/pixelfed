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
use App\Services\StatusService;
use App\Services\HomeTimelineService;

class FeedUnfollowPipeline implements ShouldQueue, ShouldBeUniqueUntilProcessing
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
        return 'hts:feed:remove:follows:aid:' . $this->actorId . ':fid:' . $this->followingId;
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

        $ids = HomeTimelineService::get($actorId, 0, -1);
        foreach($ids as $id) {
            $status = StatusService::get($id, false);
            if($status && isset($status['account'], $status['account']['id'])) {
                if($status['account']['id'] == $followingId) {
                    HomeTimelineService::rem($actorId, $id);
                }
            }
        }
    }
}
