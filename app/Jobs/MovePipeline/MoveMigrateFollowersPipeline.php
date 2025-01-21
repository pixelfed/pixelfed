<?php

namespace App\Jobs\MovePipeline;

use App\Follower;
use App\Util\ActivityPub\Helpers;
use DateTime;
use DB;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\ThrottlesExceptionsWithRedis;
use Illuminate\Queue\Middleware\WithoutOverlapping;

class MoveMigrateFollowersPipeline implements ShouldQueue
{
    use Queueable;

    public $target;

    public $activity;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 15;

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     *
     * @var int
     */
    public $maxExceptions = 5;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 900;

    /**
     * Create a new job instance.
     */
    public function __construct($target, $activity)
    {
        $this->target = $target;
        $this->activity = $activity;
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [
            new WithoutOverlapping('process-move-migrate-followers:'.$this->target),
            (new ThrottlesExceptionsWithRedis(5, 2 * 60))->backoff(1),
        ];
    }

    /**
     * Determine the time at which the job should timeout.
     */
    public function retryUntil(): DateTime
    {
        return now()->addMinutes(15);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (config('app.env') !== 'production' || (bool) config_cache('federation.activitypub.enabled') == false) {
            throw new Exception('Activitypub not enabled');
        }

        $target = $this->target;
        $actor = $this->activity;

        $targetAccount = Helpers::profileFetch($target);
        $actorAccount = Helpers::profileFetch($actor);

        if (! $targetAccount || ! $actorAccount) {
            throw new Exception('Invalid move accounts');
        }

        $activity = [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'type' => 'Follow',
            'actor' => null,
            'object' => $target,
        ];

        $version = config('pixelfed.version');
        $appUrl = config('app.url');
        $userAgent = "(Pixelfed/{$version}; +{$appUrl})";
        $addlHeaders = [
            'Content-Type' => 'application/ld+json; profile="https://www.w3.org/ns/activitystreams"',
            'User-Agent' => $userAgent,
        ];
        $targetInbox = $targetAccount['sharedInbox'] ?? $targetAccount['inbox_url'];
        $targetPid = $targetAccount['id'];

        DB::table('followers')
            ->join('profiles', 'followers.profile_id', '=', 'profiles.id')
            ->where('followers.following_id', $actorAccount['id'])
            ->whereNotNull('profiles.user_id')
            ->whereNull('profiles.deleted_at')
            ->select('profiles.id', 'profiles.user_id', 'profiles.username', 'profiles.private_key', 'profiles.status')
            ->chunkById(100, function ($followers) use ($targetInbox, $targetPid, $target) {
                foreach ($followers as $follower) {
                    if (! $follower->private_key || ! $follower->username || ! $follower->user_id || $follower->status === 'delete') {
                        continue;
                    }

                    Follower::updateOrCreate([
                        'profile_id' => $follower->id,
                        'following_id' => $targetPid,
                    ]);

                    MoveSendFollowPipeline::dispatch($follower, $targetInbox, $targetPid, $target)->onQueue('follow');
                }
            }, 'id');
    }
}
