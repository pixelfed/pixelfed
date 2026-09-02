<?php

namespace App\Jobs\HomeFeedPipeline;

use App\Models\Follower;
use App\Models\Hashtag;
use App\Services\HomeTimelineService;
use App\Services\StatusService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Attributes\FailOnTimeout;
use Illuminate\Queue\Attributes\MaxExceptions;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

#[Timeout(900)]
#[Tries(3)]
#[MaxExceptions(1)]
#[FailOnTimeout]
class HashtagUnfollowPipeline implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $pid;

    protected $hid;

    protected $slug;

    /**
     * Create a new job instance.
     */
    public function __construct($hid, $pid, $slug)
    {
        $this->hid = $hid;
        $this->pid = $pid;
        $this->slug = $slug;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $hid = $this->hid;
        $pid = $this->pid;
        $slug = $this->slug;

        // Verify hashtag ID exists
        if (! $hid) {
            Log::info('HashtagUnfollowPipeline: Hashtag ID not provided, skipping job');

            return;
        }

        // Verify profile ID exists
        if (! $pid) {
            Log::info('HashtagUnfollowPipeline: Profile ID not provided, skipping job');

            return;
        }

        // Verify slug exists
        if (! $slug) {
            Log::info('HashtagUnfollowPipeline: Slug not provided, skipping job');

            return;
        }

        $slug = strtolower($slug);

        $statusIds = HomeTimelineService::get($pid, 0, -1);

        $followingIds = Cache::remember('profile:following:'.$pid, 1209600, function () use ($pid) {
            $following = Follower::whereProfileId($pid)->pluck('following_id');

            return $following->push($pid)->toArray();
        });

        foreach ($statusIds as $id) {
            $status = StatusService::get($id, false);
            if (! $status || empty($status['tags'])) {
                HomeTimelineService::rem($pid, $id);

                continue;
            }
            $following = in_array((int) $status['account']['id'], $followingIds);
            if ($following === true) {
                continue;
            }

            $tags = collect($status['tags'])->map(function ($tag) {
                return strtolower($tag['name']);
            })->filter()->values()->toArray();

            if (in_array($slug, $tags)) {
                HomeTimelineService::rem($pid, $id);
            }
        }
    }
}
