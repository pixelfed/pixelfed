<?php

namespace App\Jobs\FollowPipeline;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\AccountService;
use App\Services\FollowerService;
use Cache;
use DB;
use App\Profile;

class FollowServiceWarmCache implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $profileId;
    public $tries = 5;
    public $timeout = 300;
    public $failOnTimeout = true;

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

        $account = AccountService::get($id, true);

        if(!$account) {
            Cache::put(FollowerService::FOLLOWERS_SYNC_KEY . $id, 1);
            Cache::put(FollowerService::FOLLOWING_SYNC_KEY . $id, 1);
            return;
        }

        DB::table('followers')
            ->select('id', 'following_id', 'profile_id')
            ->whereFollowingId($id)
            ->orderBy('id')
            ->chunk(200, function($followers) use($id) {
            foreach($followers as $follow) {
                FollowerService::add($follow->profile_id, $id);
            }
        });

        DB::table('followers')
            ->select('id', 'following_id', 'profile_id')
            ->whereProfileId($id)
            ->orderBy('id')
            ->chunk(200, function($followers) use($id) {
            foreach($followers as $follow) {
                FollowerService::add($id, $follow->following_id);
            }
        });

        Cache::put(FollowerService::FOLLOWERS_SYNC_KEY . $id, 1);
        Cache::put(FollowerService::FOLLOWING_SYNC_KEY . $id, 1);

        $profile = Profile::find($id);
        if($profile) {
            $profile->following_count = DB::table('followers')->whereProfileId($id)->count();
            $profile->followers_count = DB::table('followers')->whereFollowingId($id)->count();
            $profile->save();
        }

        AccountService::del($id);

        return;
    }
}
