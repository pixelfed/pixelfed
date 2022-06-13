<?php

namespace App\Jobs\FollowPipeline;

use App\Notification;
use Cache;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Log;
use Illuminate\Support\Facades\Redis;

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

		Cache::forget('profile:following:' . $actor->id);
		Cache::forget('profile:following:' . $target->id);

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
