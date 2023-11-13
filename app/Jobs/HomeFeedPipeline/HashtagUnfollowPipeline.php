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
use Illuminate\Support\Facades\Cache;
use App\Follower;
use App\Hashtag;
use App\StatusHashtag;
use App\Services\HashtagFollowService;
use App\Services\StatusService;
use App\Services\HomeTimelineService;

class HashtagUnfollowPipeline implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $pid;
    protected $hid;

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
        return 'hfp:hashtag:unfollow:' . $this->hid . ':' . $this->pid;
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [(new WithoutOverlapping("hfp:hashtag:unfollow:{$this->hid}:{$this->pid}"))->shared()->dontRelease()];
    }

    /**
     * Create a new job instance.
     */
    public function __construct($hid, $pid)
    {
        $this->hid = $hid;
        $this->pid = $pid;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $hid = $this->hid;
        $pid = $this->pid;

        $statusIds = HomeTimelineService::get($pid, 0, -1);

        if(!$statusIds || !count($statusIds)) {
            return;
        }

        $followingIds = Cache::remember('profile:following:'.$pid, 1209600, function() use($pid) {
            $following = Follower::whereProfileId($pid)->pluck('following_id');
            return $following->push($pid)->toArray();
        });

        foreach($statusIds as $id) {
            $status = StatusService::get($id, false);
            if(!$status) {
                HomeTimelineService::rem($pid, $id);
                continue;
            }
            if(!in_array($status['account']['id'], $followingIds)) {
                HomeTimelineService::rem($pid, $id);
            }
        }
    }
}
