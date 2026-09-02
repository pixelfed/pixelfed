<?php

namespace App\Jobs\HomeFeedPipeline;

use App\Models\Hashtag;
use App\Services\HashtagFollowService;
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
class HashtagRemoveFanoutPipeline implements ShouldBeUniqueUntilProcessing, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $sid;

    protected $hid;

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return 'hfp:hashtag:fanout:remove:'.$this->hid.':'.$this->sid;
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [(new WithoutOverlapping("hfp:hashtag:fanout:remove:{$this->hid}:{$this->sid}"))->shared()->dontRelease()];
    }

    /**
     * Create a new job instance.
     */
    public function __construct($sid, $hid)
    {
        $this->sid = $sid;
        $this->hid = $hid;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $sid = $this->sid;
        $hid = $this->hid;

        // Verify status ID exists
        if (! $sid) {
            Log::info('HashtagRemoveFanoutPipeline: Status ID not provided, skipping job');

            return;
        }

        // Verify hashtag ID exists
        if (! $hid) {
            Log::info('HashtagRemoveFanoutPipeline: Hashtag ID not provided, skipping job');

            return;
        }

        $status = StatusService::get($sid, false);

        if (! $status || ! isset($status['account']) || ! isset($status['account']['id'])) {
            Log::info("HashtagRemoveFanoutPipeline: Status {$sid} not found or invalid, skipping job");

            return;
        }

        if (! in_array($status['pf_type'], ['photo', 'photo:album', 'video', 'video:album', 'photo:video:album'])) {
            return;
        }

        $ids = HashtagFollowService::getPidByHid($hid);

        if (! $ids || ! count($ids)) {
            return;
        }

        foreach ($ids as $id) {
            HomeTimelineService::rem($id, $sid);
        }
    }
}
