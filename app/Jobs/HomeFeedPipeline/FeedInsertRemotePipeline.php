<?php

namespace App\Jobs\HomeFeedPipeline;

use App\Models\Profile;
use App\Models\UserDomainBlock;
use App\Models\UserFilter;
use App\Services\FollowerService;
use App\Services\HomeTimelineService;
use App\Services\StatusService;
use DateTimeInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

class FeedInsertRemotePipeline implements ShouldBeUniqueUntilProcessing, ShouldQueue
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

        $type = $status['pf_type'] ?? null;

        if (! in_array($type, [
            'photo',
            'photo:album',
            'video',
            'video:album',
            'photo:video:album',
        ], true)) {
            return;
        }

        if (self::isTooOld($status['created_at'] ?? null)) {
            return;
        }

        $ids = FollowerService::localFollowerIds($pid);

        if (! $ids || ! count($ids)) {
            return;
        }

        $domain = parse_url($status['url'], PHP_URL_HOST);

        if (! is_string($domain) || $domain === '') {
            return;
        }

        $domain = strtolower($domain);
        $skipIds = [];

        if (strtolower(config('pixelfed.domain.app')) !== $domain) {
            $skipIds = UserDomainBlock::where('domain', $domain)->pluck('profile_id')->toArray();
        }

        $filterableIds = [$status['account']['id']];

        // For a reblog, also honor mutes/blocks against the ORIGINAL author,
        // not just the sharer.
        if (isset($status['reblog']['account']['id'])) {
            $filterableIds[] = $status['reblog']['account']['id'];
        }

        $filters = UserFilter::whereFilterableType(Profile::class)
            ->whereIn('filterable_id', array_unique($filterableIds))
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

    /**
     * Keep stale remote posts out of home feeds.
     *
     * Home is scored by local ingest id, so an old post fetched for the first time
     * would otherwise jump to the top of every follower's feed. The cutoff follows
     * the configurable home window (instance.timeline.home.max_backfill_days).
     */
    public static function isTooOld(mixed $createdAt): bool
    {
        if ($createdAt === null || $createdAt === '') {
            return true;
        }

        if (is_array($createdAt)) {
            $createdAt = $createdAt['date']
                ?? $createdAt['created_at']
                ?? null;
        }

        if ($createdAt === null || $createdAt === '') {
            return true;
        }

        try {
            if ($createdAt instanceof DateTimeInterface) {
                $published = Carbon::instance($createdAt);
            } elseif (is_string($createdAt)) {
                $published = Carbon::parse($createdAt);
            } else {
                return true;
            }
        } catch (Throwable) {
            return true;
        }

        return $published->lt(now()->subDays((int) config('instance.timeline.home.max_backfill_days')));
    }
}
