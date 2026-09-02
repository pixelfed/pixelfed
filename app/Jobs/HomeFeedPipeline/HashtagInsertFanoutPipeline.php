<?php

namespace App\Jobs\HomeFeedPipeline;

use App\Models\Hashtag;
use App\Models\Profile;
use App\Models\StatusHashtag;
use App\Models\UserDomainBlock;
use App\Models\UserFilter;
use App\Services\HashtagFollowService;
use App\Services\HomeTimelineService;
use App\Services\StatusService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Attributes\DeleteWhenMissingModels;
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
#[DeleteWhenMissingModels]
#[UniqueFor(3600)]
class HashtagInsertFanoutPipeline implements ShouldBeUniqueUntilProcessing, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $hashtag;

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

        // Verify hashtag exists
        if (! $hashtag) {
            Log::info('HashtagInsertFanoutPipeline: Hashtag no longer exists, skipping job');

            return;
        }

        // Verify hashtag has status ID
        if (! $hashtag->status_id) {
            Log::info("HashtagInsertFanoutPipeline: Hashtag {$hashtag->id} has no status_id, skipping job");

            return;
        }

        $sid = $hashtag->status_id;
        $status = StatusService::get($sid, false);

        if (! $status || ! isset($status['account']) || ! isset($status['account']['id'], $status['url'])) {
            Log::info("HashtagInsertFanoutPipeline: Status {$sid} not found or invalid, skipping job");

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

        $filters = UserFilter::whereFilterableType(Profile::class)
            ->whereFilterableId($status['account']['id'])
            ->whereIn('filter_type', ['mute', 'block'])
            ->pluck('user_id')
            ->toArray();

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
