<?php

namespace App\Jobs\FollowPipeline;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use App\Services\AccountService;
use App\Services\FollowerService;
use Cache;
use DB;
use Storage;
use App\Follower;
use App\Profile;

class FollowServiceWarmCache implements ShouldQueue
{
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	public $profileId;
	public $tries = 5;
	public $timeout = 5000;
	public $failOnTimeout = false;

    /**
     * Get the middleware the job should pass through.
     *
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [(new WithoutOverlapping($this->profileId))->dontRelease()];
    }

	/**
	 * Create a new job instance.
	 *
	 * @return void
	 */
	public function __construct($profileId)
	{
		$this->profileId = $profileId;
	}

	/**
	 * Execute the job.
	 *
	 * @return void
	 */
	public function handle()
	{
		$id = $this->profileId;

        if(Cache::has(FollowerService::FOLLOWERS_SYNC_KEY . $id) && Cache::has(FollowerService::FOLLOWING_SYNC_KEY . $id)) {
            return;
        }

		$account = AccountService::get($id, true);

		if(!$account) {
			Cache::put(FollowerService::FOLLOWERS_SYNC_KEY . $id, 1, 604800);
			Cache::put(FollowerService::FOLLOWING_SYNC_KEY . $id, 1, 604800);
			return;
		}

        $hasFollowerPostProcessing = false;
        $hasFollowingPostProcessing = false;

        if(Follower::whereProfileId($id)->orWhere('following_id', $id)->count()) {
            $following = [];
            $followers = [];
    		foreach(Follower::lazy() as $follow) {
                if($follow->following_id != $id && $follow->profile_id != $id) {
                    continue;
                }
                if($follow->profile_id == $id) {
                    $following[] = $follow->following_id;
                } else {
                    $followers[] = $follow->profile_id;
                }
            }

            if(count($followers) > 100) {
                // store follower ids and process in another job
                Storage::put('follow-warm-cache/' . $id . '/followers.json', json_encode($followers));
                $hasFollowerPostProcessing = true;
            } else {
                foreach($followers as $follower) {
                    FollowerService::add($follower, $id);
                }
            }

            if(count($following) > 100) {
                // store following ids and process in another job
                Storage::put('follow-warm-cache/' . $id . '/following.json', json_encode($following));
                $hasFollowingPostProcessing = true;
            } else {
                foreach($following as $following) {
                    FollowerService::add($id, $following);
                }
            }
        }

		Cache::put(FollowerService::FOLLOWERS_SYNC_KEY . $id, 1, 604800);
		Cache::put(FollowerService::FOLLOWING_SYNC_KEY . $id, 1, 604800);

		$profile = Profile::find($id);
		if($profile) {
			$profile->following_count = DB::table('followers')->whereProfileId($id)->count();
			$profile->followers_count = DB::table('followers')->whereFollowingId($id)->count();
			$profile->save();
		}

		AccountService::del($id);

        if($hasFollowingPostProcessing) {
            FollowServiceWarmCacheLargeIngestPipeline::dispatch($id, 'following')->onQueue('follow');
        }

        if($hasFollowerPostProcessing) {
            FollowServiceWarmCacheLargeIngestPipeline::dispatch($id, 'followers')->onQueue('follow');
        }

		return;
	}
}
