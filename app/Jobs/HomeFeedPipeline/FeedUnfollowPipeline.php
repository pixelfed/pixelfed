<?php

namespace App\Jobs\HomeFeedPipeline;

use App\Models\Hashtag;
use App\Models\HashtagFollow;
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

class FeedUnfollowPipeline implements ShouldBeUniqueUntilProcessing, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $actorId;

    protected $followingId;

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
        return 'hts:feed:remove:follows:aid:'.$this->actorId.':fid:'.$this->followingId;
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [(new WithoutOverlapping("hts:feed:remove:follows:aid:{$this->actorId}:fid:{$this->followingId}"))->shared()->expireAfter($this->timeout)->dontRelease()];
    }

    /**
     * Create a new job instance.
     */
    public function __construct($actorId, $followingId)
    {
        $this->actorId = $actorId;
        $this->followingId = $followingId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $actorId = $this->actorId;
        $followingId = $this->followingId;

        // Verify actor ID exists
        if (! $actorId) {
            Log::info('FeedUnfollowPipeline: Actor ID not provided, skipping job');

            return;
        }

        // Verify following ID exists
        if (! $followingId) {
            Log::info('FeedUnfollowPipeline: Following ID not provided, skipping job');

            return;
        }

        // Slugs of hashtags the viewer follows. A post from the unfollowed
        // author that carries one of these is still sourced into the home feed
        // through the hashtag and must survive the unfollow.
        $followedTagSlugs = $this->followedHashtagSlugs($actorId);

        $ids = HomeTimelineService::get($actorId, 0, -1);
        foreach ($ids as $id) {
            $status = StatusService::get($id, false);
            if (! $status || ! isset($status['account'], $status['account']['id'])) {
                continue;
            }

            if ($status['account']['id'] != $followingId) {
                continue;
            }

            if ($followedTagSlugs && $this->statusMatchesFollowedTag($status, $followedTagSlugs)) {
                continue;
            }

            HomeTimelineService::rem($actorId, $id);
        }
    }

    protected function statusMatchesFollowedTag(array $status, array $followedTagSlugs): bool
    {
        if (empty($status['tags'])) {
            return false;
        }

        $tags = collect($status['tags'])->map(function ($tag) {
            return strtolower($tag['name']);
        })->filter()->values()->toArray();

        return (bool) array_intersect($followedTagSlugs, $tags);
    }

    protected function followedHashtagSlugs($pid): array
    {
        return HashtagFollow::where('profile_id', $pid)
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
