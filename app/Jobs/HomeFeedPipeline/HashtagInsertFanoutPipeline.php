<?php

namespace App\Jobs\HomeFeedPipeline;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Hashtag;
use App\StatusHashtag;
use App\UserFilter;
use App\Services\HashtagFollowService;
use App\Services\HomeTimelineService;
use App\Services\StatusService;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;

class HashtagInsertFanoutPipeline implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $hashtag;

    public $timeout = 900;
    public $tries = 3;
    public $maxExceptions = 1;
    public $failOnTimeout = true;

    /**
     * Delete the job if its models no longer exist.
     *
     * @var bool
     */
    public $deleteWhenMissingModels = true;

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
        return 'hfp:hashtag:fanout:insert:' . $this->hashtag->id;
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [(new WithoutOverlapping("hfp:hashtag:fanout:insert:{$this->hashtag->id}"))->shared()->dontRelease()];
    }

    /**
     * Create a new job instance.
     */
    public function __construct(StatusHashtag $hashtag)
    {
        $this->hashtag = $hashtag;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $hashtag = $this->hashtag;
        $sid = $hashtag->status_id;
        $status = StatusService::get($sid, false);

        if(!$status) {
            return;
        }

        if(!in_array($status['pf_type'], ['photo', 'photo:album', 'video', 'video:album', 'photo:video:album'])) {
            return;
        }

        $skipIds = UserFilter::whereFilterableType('App\Profile')->whereFilterableId($status['account']['id'])->whereIn('filter_type', ['mute', 'block'])->pluck('user_id')->toArray();

        $ids = HashtagFollowService::getPidByHid($hashtag->hashtag_id);

        if(!$ids || !count($ids)) {
            return;
        }

        foreach($ids as $id) {
            if(!in_array($id, $skipIds)) {
                HomeTimelineService::add($id, $hashtag->status_id);
            }
        }
    }
}
