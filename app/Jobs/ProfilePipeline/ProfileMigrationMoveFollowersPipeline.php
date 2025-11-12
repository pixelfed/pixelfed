<?php

namespace App\Jobs\ProfilePipeline;

use App\Follower;
use App\Http\Controllers\FollowerController;
use App\Profile;
use App\Services\AccountService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProfileMigrationMoveFollowersPipeline implements ShouldBeUniqueUntilProcessing, ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $oldPid;

    public $newPid;

    public $timeout = 1400;

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
        return 'profile:migration:move-followers:oldpid-'.$this->oldPid.':newpid-'.$this->newPid;
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [(new WithoutOverlapping('profile:migration:move-followers:oldpid-'.$this->oldPid.':newpid-'.$this->newPid))->shared()->dontRelease()];
    }

    /**
     * Create a new job instance.
     */
    public function __construct($oldPid, $newPid)
    {
        $this->oldPid = $oldPid;
        $this->newPid = $newPid;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->batch()->cancelled()) {
            return;
        }
        $og = Profile::find($this->oldPid);
        $ne = Profile::find($this->newPid);
        if (! $og || ! $ne || $og == $ne) {
            return;
        }
        $ne->followers_count = $og->followers_count;
        $ne->save();
        $og->followers_count = 0;
        $og->save();

        $targetInbox = $ne['sharedInbox'] ?? $ne['inbox_url'];
        foreach (Follower::whereFollowingId($this->oldPid)->lazyById(200, 'id') as $follower) {
            try {
                $follower->following_id = $this->newPid;
                $follower->save();

                // If a local user has migrated to a different instance, send a
                // follow request for each local follower to the new instance
                if ($targetInbox && $follower->local_profile) {
                    $followerProfile = Profile::find($follower->profile_id);
                    (new FollowerController)->sendFollow($followerProfile, $ne);
                }
            } catch (\Exception $e) {
                Log::error($e);
            }
        }
        AccountService::del($this->oldPid);
        AccountService::del($this->newPid);
    }
}
