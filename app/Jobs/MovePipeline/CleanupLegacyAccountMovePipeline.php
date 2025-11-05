<?php

namespace App\Jobs\MovePipeline;

use App\Follower;
use App\Profile;
use App\Services\AccountService;
use App\UserFilter;
use App\Util\ActivityPub\Helpers;
use DateTime;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\ThrottlesExceptions;
use Illuminate\Queue\Middleware\WithoutOverlapping;

class CleanupLegacyAccountMovePipeline implements ShouldQueue
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

        UserFilter::where('filterable_type', 'App\Profile')
            ->where('filterable_id', $actorAccount['id'])
            ->update(['filterable_id' => $targetAccount['id']]);

        Follower::whereFollowingId($actorAccount['id'])->delete();

        $oldProfile = Profile::find($actorAccount['id']);

        if ($oldProfile) {
            $oldProfile->moved_to_profile_id = $targetAccount['id'];
            $oldProfile->followers_count = 0;
            $oldProfile->save();
            AccountService::del($oldProfile->id);
            AccountService::del($targetAccount['id']);
        }
    }
}
