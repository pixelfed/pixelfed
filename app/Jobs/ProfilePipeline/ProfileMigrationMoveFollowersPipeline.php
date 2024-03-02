<?php

namespace App\Jobs\ProfilePipeline;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Follower;
use App\Profile;
use App\Services\AccountService;

class ProfileMigrationMoveFollowersPipeline implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $oldPid;
    public $newPid;

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
        $og = Profile::find($this->oldPid);
        $ne = Profile::find($this->newPid);
        if(!$og || !$ne || $og == $ne) {
            return;
        }
        $ne->followers_count = $og->followers_count;
        $ne->save();
        $og->followers_count = 0;
        $og->save();
        foreach (Follower::whereFollowingId($this->oldPid)->lazyById(200, 'id') as $follower) {
            try {
                $follower->following_id = $this->newPid;
                $follower->save();
            } catch (Exception $e) {
                $follower->delete();
            }
        }
        AccountService::del($this->oldPid);
        AccountService::del($this->newPid);
    }
}
