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
use App\UserFilter;
use App\Services\FollowerService;
use App\Services\HomeTimelineService;
use App\Services\StatusService;

class FeedInsertRemotePipeline implements ShouldQueue, ShouldBeUniqueUntilProcessing
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
        return 'hts:feed:insert:remote:sid:' . $this->sid;
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [(new WithoutOverlapping("hts:feed:insert:remote:sid:{$this->sid}"))->shared()->dontRelease()];
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
        $status = StatusService::get($sid, false);

        if(!$status || !isset($status['account']) || !isset($status['account']['id'])) {
            return;
        }

        if(!in_array($status['pf_type'], ['photo', 'photo:album', 'video', 'video:album', 'photo:video:album'])) {
            return;
        }

        $ids = FollowerService::localFollowerIds($this->pid);

        if(!$ids || !count($ids)) {
            return;
        }

        $skipIds = UserFilter::whereFilterableType('App\Profile')->whereFilterableId($status['account']['id'])->whereIn('filter_type', ['mute', 'block'])->pluck('user_id')->toArray();

        foreach($ids as $id) {
            if(!in_array($id, $skipIds)) {
                HomeTimelineService::add($id, $this->sid);
            }
        }
    }
}
