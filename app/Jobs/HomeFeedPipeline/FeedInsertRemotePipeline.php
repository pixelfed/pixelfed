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
class FeedInsertRemotePipeline implements ShouldBeUniqueUntilProcessing, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $sid;

    protected $pid;

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
}
