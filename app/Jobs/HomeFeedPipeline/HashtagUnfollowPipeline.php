<?php

namespace App\Jobs\HomeFeedPipeline;

use App\Models\Hashtag;
use App\Models\HashtagFollow;
use App\Services\FollowerService;
use App\Services\HomeTimelineService;
use App\Services\StatusService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

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

        $followingIds = FollowerService::getFollowingIds($pid);

        // Slugs of every other hashtag the viewer still follows. A status that
        // carries one of these is still sourced into the home feed and must not
        // be removed just because one of its tags was unfollowed.
        $otherFollowedSlugs = $this->otherFollowedHashtagSlugs($pid, $hid);

        foreach ($statusIds as $id) {
            $status = StatusService::get($id, false);

            if (! $status || ! isset($status['account']['id'])) {
                HomeTimelineService::rem($pid, $id);

                continue;
            }

            $following = in_array((int) $status['account']['id'], $followingIds);
            if ($following === true) {
                continue;
            }

            if (empty($status['tags'])) {
                continue;
            }

            $tags = collect($status['tags'])->map(function ($tag) {
                return strtolower($tag['name']);
            })->filter()->values()->toArray();

            if (! in_array($slug, $tags)) {
                continue;
            }

            // The unfollowed tag is present, but keep the member if any other
            // followed hashtag still supplies it.
            if (array_intersect($otherFollowedSlugs, $tags)) {
                continue;
            }

            HomeTimelineService::rem($pid, $id);
        }
    }

    protected function otherFollowedHashtagSlugs($pid, $hid): array
    {
        return HashtagFollow::where('profile_id', $pid)
            ->where('hashtag_id', '!=', $hid)
            ->pluck('hashtag_id')
            ->map(function ($id) {
                $tag = Hashtag::find($id);

                return $tag ? strtolower($tag->slug ?? $tag->name) : null;
            })
            ->filter()
            ->values()
            ->toArray();
    }
}
