<?php

namespace App\Jobs\MovePipeline;

use App\Util\ActivityPub\Helpers;
use DateTime;
use DB;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\ThrottlesExceptions;
use Illuminate\Queue\Middleware\WithoutOverlapping;

class UnfollowLegacyAccountMovePipeline implements ShouldQueue
{
    use Queueable;

    public $target;

    public $activity;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 6;

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     *
     * @var int
     */
    public $maxExceptions = 3;

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
            new WithoutOverlapping('process-move-undo-legacy-followers:'.$this->target),
            (new ThrottlesExceptions(2, 5 * 60))->backoff(5),
        ];
    }

    /**
     * Determine the time at which the job should timeout.
     */
    public function retryUntil(): DateTime
    {
        return now()->addMinutes(5);
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

        $version = config('pixelfed.version');
        $appUrl = config('app.url');
        $userAgent = "(Pixelfed/{$version}; +{$appUrl})";
        $addlHeaders = [
            'Content-Type' => 'application/ld+json; profile="https://www.w3.org/ns/activitystreams"',
            'User-Agent' => $userAgent,
        ];
        $targetInbox = $actorAccount['sharedInbox'] ?? $actorAccount['inbox_url'];
        $targetPid = $actorAccount['id'];

        DB::table('followers')
            ->join('profiles', 'followers.profile_id', '=', 'profiles.id')
            ->where('followers.following_id', $actorAccount['id'])
            ->whereNotNull('profiles.user_id')
            ->whereNull('profiles.deleted_at')
            ->select('profiles.id', 'profiles.user_id', 'profiles.username', 'profiles.private_key', 'profiles.status')
            ->chunkById(100, function ($followers) use ($actor, $targetInbox, $targetPid) {
                foreach ($followers as $follower) {
                    if (! $follower->id || ! $follower->private_key || ! $follower->username || ! $follower->user_id || $follower->status === 'delete') {
                        continue;
                    }

                    MoveSendUndoFollowPipeline::dispatch($follower, $targetInbox, $targetPid, $actor)->onQueue('move');
                }
            }, 'profiles.id', 'id');
    }
}
