<?php

namespace App\Jobs\HomeFeedPipeline;

use App\Models\Profile;
use App\Models\UserDomainBlock;
use App\Models\UserFilter;
use App\Services\FollowerService;
use App\Services\HomeTimelineService;
use App\Services\StatusService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class FeedInsertRemotePipeline implements ShouldBeUniqueUntilProcessing, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Remote statuses published more than this many days ago are not
     * inserted into home feeds.
     *
     * The home timeline is scored by status id, and a remote status gets
     * its id when we first store it, not when it was published. Without
     * this guard an old post that is fetched for the first time (a boost,
     * an edit, a reply to it) lands at the top of every follower's feed.
     */
    public const MAX_AGE_DAYS = 7;

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
        return 'hts:feed:insert:remote:sid:'.$this->sid;
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
        $pid = $this->pid;

        // Verify status ID exists
        if (! $sid) {
            Log::info('FeedInsertRemotePipeline: Status ID not provided, skipping job');

            return;
        }

        // Verify profile ID exists
        if (! $pid) {
            Log::info('FeedInsertRemotePipeline: Profile ID not provided, skipping job');

            return;
        }

        $status = StatusService::get($sid, false);

        if (! $status || ! isset($status['account']) || ! isset($status['account']['id'], $status['url'])) {
            Log::info("FeedInsertRemotePipeline: Status {$sid} not found or invalid, skipping job");

            return;
        }

        if (! in_array($status['pf_type'], ['photo', 'photo:album', 'video', 'video:album', 'photo:video:album'])) {
            return;
        }

        if (self::isTooOld($status['created_at'] ?? null)) {
            return;
        }

        $ids = FollowerService::localFollowerIds($pid);

        if (! $ids || ! count($ids)) {
            return;
        }

        $domain = strtolower(parse_url($status['url'], PHP_URL_HOST));
        $skipIds = [];

        if (strtolower(config('pixelfed.domain.app')) !== $domain) {
            $skipIds = UserDomainBlock::where('domain', $domain)->pluck('profile_id')->toArray();
        }

        $filters = UserFilter::whereFilterableType(Profile::class)
            ->whereFilterableId($status['account']['id'])
            ->whereIn('filter_type', ['mute', 'block'])
            ->pluck('user_id')
            ->toArray();

        if ($filters && count($filters)) {
            $skipIds = array_merge($skipIds, $filters);
        }

        $skipIds = array_unique(array_values($skipIds));

        foreach ($ids as $id) {
            if (! in_array($id, $skipIds)) {
                HomeTimelineService::add($id, $sid);
            }
        }
    }

    public static function isTooOld(string|\DateTimeInterface|null $createdAt): bool
    {
        if ($createdAt === null || $createdAt === '') {
            return true;
        }

        try {
            $published = now()->parse($createdAt);
        } catch (Throwable) {
            return true;
        }

        return $published->lt(now()->subDays(self::MAX_AGE_DAYS));
    }
}
