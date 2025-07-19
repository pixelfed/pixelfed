<?php

namespace App\Jobs\HomeFeedPipeline;

use App\Follower;
use App\Services\HomeTimelineService;
use App\Services\StatusService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class HashtagUnfollowPipeline implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $pid;

    protected $hid;

    protected $slug;

    public $timeout = 900;

    public $tries = 3;

    public $maxExceptions = 1;

    public $failOnTimeout = true;

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
        $slug = strtolower($this->slug);

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
