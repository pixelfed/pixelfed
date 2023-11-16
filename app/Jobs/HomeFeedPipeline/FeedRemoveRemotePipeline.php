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
use App\Services\FollowerService;
use App\Services\StatusService;
use App\Services\HomeTimelineService;

class FeedRemoveRemotePipeline implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $sid;
    protected $pid;

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
        return 'hts:feed:remove:remote:sid:' . $this->sid;
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [(new WithoutOverlapping("hts:feed:remove:remote:sid:{$this->sid}"))->shared()->dontRelease()];
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
        $ids = FollowerService::localFollowerIds($this->pid);

        foreach($ids as $id) {
            HomeTimelineService::rem($id, $this->sid);
        }
    }
}
