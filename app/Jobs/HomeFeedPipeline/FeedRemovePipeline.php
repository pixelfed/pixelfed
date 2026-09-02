<?php

namespace App\Jobs\HomeFeedPipeline;

use App\Services\FollowerService;
use App\Services\HomeTimelineService;
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
class FeedRemovePipeline implements ShouldBeUniqueUntilProcessing, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $sid;

    protected $pid;

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return 'hts:feed:remove:sid:'.$this->sid;
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [(new WithoutOverlapping("hts:feed:remove:sid:{$this->sid}"))->shared()->dontRelease()];
    }

    /**
     * Create a new job instance.
     */
    public function __construct($sid, $pid)
    {
        $this->sid = $sid;
        $this->pid = $pid;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $sid = $this->sid;
        $pid = $this->pid;

        // Verify status ID exists
        if (! $sid) {
            Log::info('FeedRemovePipeline: Status ID not provided, skipping job');

            return;
        }

        // Verify profile ID exists
        if (! $pid) {
            Log::info('FeedRemovePipeline: Profile ID not provided, skipping job');

            return;
        }

        $ids = FollowerService::localFollowerIds($pid);

        HomeTimelineService::rem($pid, $sid);

        foreach ($ids as $id) {
            HomeTimelineService::rem($id, $sid);
        }
    }
}
