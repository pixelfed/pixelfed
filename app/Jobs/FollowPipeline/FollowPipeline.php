<?php

namespace App\Jobs\FollowPipeline;

use App\Follower;
use App\Notification;
use Cache;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Log;
use Illuminate\Support\Facades\Redis;
use App\Services\AccountService;
use App\Services\FollowerService;

class FollowPipeline implements ShouldQueue
{
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	protected $follower;

	/**
	 * Delete the job if its models no longer exist.
	 *
	 * @var bool
	 */
	public $deleteWhenMissingModels = true;
	
	/**
	 * Create a new job instance.
	 *
	 * @return void
	 */
	public function __construct($follower)
	{
		$this->follower = $follower;
	}

	/**
	 * Execute the job.
	 *
	 * @return void
	 */
	public function handle()
	{
		$follower = $this->follower;
		$actor = $follower->actor;
		$target = $follower->target;

		if(!$actor || !$target) {
			return;
		}

		Cache::forget('profile:following:' . $actor->id);
		Cache::forget('profile:following:' . $target->id);

		FollowerService::add($actor->id, $target->id);

		$actorProfileSync = Cache::get(FollowerService::FOLLOWING_SYNC_KEY . $actor->id);
		if(!$actorProfileSync) {
			FollowServiceWarmCache::dispatch($actor->id)->onQueue('low');
		} else {
			if($actor->following_count) {
				$actor->increment('following_count');
			} else {
				$count = Follower::whereProfileId($actor->id)->count();
				$actor->following_count = $count;
				$actor->save();
			}
			Cache::put(FollowerService::FOLLOWING_SYNC_KEY . $actor->id, 1, 604800);
			AccountService::del($actor->id);
		}

		$targetProfileSync = Cache::get(FollowerService::FOLLOWERS_SYNC_KEY . $target->id);
		if(!$targetProfileSync) {
			FollowServiceWarmCache::dispatch($target->id)->onQueue('low');
		} else {
			if($target->followers_count) {
				$target->increment('followers_count');
			} else {
				$count = Follower::whereFollowingId($target->id)->count();
				$target->followers_count = $count;
				$target->save();
			}
			Cache::put(FollowerService::FOLLOWERS_SYNC_KEY . $target->id, 1, 604800);
			AccountService::del($target->id);
		}

		if($target->domain || !$target->private_key) {
			return;
		}

		try {
			$notification = new Notification();
			$notification->profile_id = $target->id;
			$notification->actor_id = $actor->id;
			$notification->action = 'follow';
			$notification->message = $follower->toText();
			$notification->rendered = $follower->toHtml();
			$notification->item_id = $target->id;
			$notification->item_type = "App\Profile";
			$notification->save();
		} catch (Exception $e) {
			Log::error($e);
		}
	}
}
