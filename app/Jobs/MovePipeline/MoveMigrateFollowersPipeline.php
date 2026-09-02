<?php

namespace App\Jobs\MovePipeline;

use App\Http\Controllers\FollowerController;
use App\Models\Follower;
use App\Models\Profile;
use App\Util\ActivityPub\Helpers;
use DateTime;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\MaxExceptions;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Queue\Middleware\ThrottlesExceptionsWithRedis;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

#[Tries(15)]
#[MaxExceptions(5)]
#[Timeout(900)]
class MoveMigrateFollowersPipeline implements ShouldQueue
{
    use Queueable;

    public $target;

    public $activity;

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

        // Verify target and actor are provided
        if (! $target) {
            Log::info('MoveMigrateFollowersPipeline: No target provided, skipping job');

            return;
        }
        if (! $actor) {
            Log::info('MoveMigrateFollowersPipeline: No actor provided, skipping job');

            return;
        }

        try {
            $targetAccount = Helpers::profileFetch($target);
            $actorAccount = Helpers::profileFetch($actor);
        } catch (Exception $e) {
            Log::warning('MoveMigrateFollowersPipeline: Failed to fetch profiles: '.$e->getMessage());
            throw $e;
        }

        if (! $targetAccount || ! $actorAccount) {
            Log::warning('MoveMigrateFollowersPipeline: Could not fetch target or actor accounts');
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
            ->select('profiles.id', 'profiles.user_id', 'profiles.username', 'profiles.private_key', 'profiles.status', 'followers.local_profile')
            ->chunkById(100, function ($followers) use ($targetInbox, $targetPid, $targetAccount) {
                foreach ($followers as $follower) {
                    if (! $follower->private_key || ! $follower->username || ! $follower->user_id || $follower->status === 'delete') {
                        continue;
                    }

                    Follower::updateOrCreate([
                        'profile_id' => $follower->id,
                        'following_id' => $targetPid,
                    ]);

                    $followerProfile = Profile::find($follower->id);
                    if ($followerProfile) {
                        $followerProfile->following_count = Follower::where('profile_id', $follower->id)->count();
                        $followerProfile->save();
                        Cache::forget('profile:following_count:'.$follower->id);
                    }

                    // If the remote user has migrated to a different instance,
                    // send a follow request for each local follower to the new
                    // instance
                    if ($targetInbox && $follower->local_profile) {
                        $followerProfile = Profile::find($follower->id);
                        (new FollowerController)->sendFollow($followerProfile, $targetAccount);
                    }
                }
            }, 'profiles.id', 'id');
    }
}
