<?php

namespace App\Jobs\ProfilePipeline;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use App\Follower;
use App\Profile;
use App\Notification;
use DB;
use App\Services\AccountService;
use App\Services\FollowerService;
use App\Services\NotificationService;

class ProfilePurgeFollowersByDomain implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $pid;
    protected $domain;

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
        return 'followers:v1:purge-by-domain:' . $this->pid . ':d-' . $this->domain;
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [(new WithoutOverlapping("followers:v1:purge-by-domain:{$this->pid}:d-{$this->domain}"))->shared()->dontRelease()];
    }

    /**
     * Create a new job instance.
     */
    public function __construct($pid, $domain)
    {
        $this->pid = $pid;
        $this->domain = $domain;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->batch()->cancelled()) {
            return;
        }

        $pid = $this->pid;
        $domain = $this->domain;

        $query = 'SELECT f.*
            FROM followers f
            JOIN profiles p ON p.id = f.profile_id OR p.id = f.following_id
            WHERE (f.profile_id = ? OR f.following_id = ?)
            AND p.domain = ?;';
        $params = [$pid, $pid, $domain];

        foreach(DB::cursor($query, $params) as $n) {
            if(!$n || !$n->id) {
                continue;
            }
            $follower = Follower::find($n->id);
            if($follower->following_id == $pid && $follower->profile_id) {
                FollowerService::remove($follower->profile_id, $pid, true);
                $follower->delete();
            } else if ($follower->profile_id == $pid && $follower->following_id) {
                FollowerService::remove($follower->following_id, $pid, true);
                $follower->delete();
            }
        }

        $profile = Profile::find($pid);

        $followerCount = DB::table('profiles')
            ->join('followers', 'profiles.id', '=', 'followers.following_id')
            ->where('followers.following_id', $pid)
            ->count();

        $followingCount = DB::table('profiles')
            ->join('followers', 'profiles.id', '=', 'followers.following_id')
            ->where('followers.profile_id', $pid)
            ->count();

        $profile->followers_count = $followerCount;
        $profile->following_count = $followingCount;
        $profile->save();

        AccountService::del($profile->id);
    }
}
