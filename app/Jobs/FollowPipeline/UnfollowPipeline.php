<?php

namespace App\Jobs\FollowPipeline;

use App\Follower;
use App\FollowRequest;
use App\Notification;
use App\Profile;
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
use App\Services\NotificationService;
use App\Jobs\HomeFeedPipeline\FeedUnfollowPipeline;

class UnfollowPipeline implements ShouldQueue
{
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	protected $actor;
	protected $target;

	/**
	 * Create a new job instance.
	 *
	 * @return void
	 */
	public function __construct($actor, $target)
	{
		$this->actor = $actor;
		$this->target = $target;
	}

	/**
	 * Execute the job.
	 *
	 * @return void
	 */
	public function handle()
	{
		$actor = $this->actor;
		$target = $this->target;

		$actorProfile = Profile::find($actor);
		if(!$actorProfile) {
			return;
		}
		$targetProfile = Profile::find($target);
		if(!$targetProfile) {
			return;
		}

		FeedUnfollowPipeline::dispatch($actor, $target)->onQueue('follow');

		FollowerService::remove($actor, $target);

		$actorProfileSync = Cache::get(FollowerService::FOLLOWING_SYNC_KEY . $actor);
		if(!$actorProfileSync) {
			FollowServiceWarmCache::dispatch($actor)->onQueue('low');
		} else {
			if($actorProfile->following_count) {
				$actorProfile->decrement('following_count');
			} else {
				$count = Follower::whereProfileId($actor)->count();
				$actorProfile->following_count = $count;
				$actorProfile->save();
			}
			Cache::put(FollowerService::FOLLOWING_SYNC_KEY . $actor, 1, 604800);
			AccountService::del($actor);
		}

		$targetProfileSync = Cache::get(FollowerService::FOLLOWERS_SYNC_KEY . $target);
		if(!$targetProfileSync) {
			FollowServiceWarmCache::dispatch($target)->onQueue('low');
		} else {
			if($targetProfile->followers_count) {
				$targetProfile->decrement('followers_count');
			} else {
				$count = Follower::whereFollowingId($target)->count();
				$targetProfile->followers_count = $count;
				$targetProfile->save();
			}
			Cache::put(FollowerService::FOLLOWERS_SYNC_KEY . $target, 1, 604800);
			AccountService::del($target);
		}

		if($targetProfile->domain == null) {
			Notification::withTrashed()
				->whereProfileId($target)
				->whereAction('follow')
				->whereActorId($actor)
				->whereItemId($target)
				->whereItemType('App\Profile')
				->get()
				->each(function($n) {
					NotificationService::del($n->profile_id, $n->id);
					$n->forceDelete();
				});
		}

		if($actorProfile->domain == null && config('instance.timeline.home.cached')) {
			Cache::forget('pf:timelines:home:' . $actor);
		}

		FollowRequest::whereFollowingId($target)
			->whereFollowerId($actor)
			->delete();

		return;
	}
}
