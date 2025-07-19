<?php

namespace App\Jobs\HomeFeedPipeline;

use App\Models\UserDomainBlock;
use App\Services\HashtagFollowService;
use App\Services\HomeTimelineService;
use App\Services\StatusService;
use App\StatusHashtag;
use App\UserFilter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class HashtagInsertFanoutPipeline implements ShouldBeUniqueUntilProcessing, ShouldQueue
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
        return 'hfp:hashtag:fanout:insert:'.$this->hashtag->id;
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

        if (! $status || ! isset($status['account']) || ! isset($status['account']['id'], $status['url'])) {
            return;
        }

        if (! in_array($status['pf_type'], ['photo', 'photo:album', 'video', 'video:album', 'photo:video:album'])) {
            return;
        }

        $domain = strtolower(parse_url($status['url'], PHP_URL_HOST));
        $skipIds = [];

        if (strtolower(config('pixelfed.domain.app')) !== $domain) {
            $skipIds = UserDomainBlock::where('domain', $domain)->pluck('profile_id')->toArray();
        }

        $filters = UserFilter::whereFilterableType('App\Profile')->whereFilterableId($status['account']['id'])->whereIn('filter_type', ['mute', 'block'])->pluck('user_id')->toArray();

        if ($filters && count($filters)) {
            $skipIds = array_merge($skipIds, $filters);
        }

        $skipIds = array_unique(array_values($skipIds));

        $ids = HashtagFollowService::getPidByHid($hashtag->hashtag_id);

        if (! $ids || ! count($ids)) {
            return;
        }

        foreach ($ids as $id) {
            if (! in_array($id, $skipIds)) {
                HomeTimelineService::add($id, $hashtag->status_id);
            }
        }
    }
}
