<?php

namespace App\Jobs\ModPipeline;

use Cache;
use App\Profile;
use App\Status;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\StatusService;

class HandleSpammerPipeline implements ShouldQueue
{
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	protected $profile;

	public $deleteWhenMissingModels = true;

	public function __construct(Profile $profile)
	{
		$this->profile = $profile;
	}

	public function handle()
	{
		$profile = $this->profile;

		$profile->unlisted = true;
		$profile->cw = true;
		$profile->no_autolink = true;
		$profile->save();

		Status::whereProfileId($profile->id)
			->chunk(50, function($statuses) {
				foreach($statuses as $status) {
					$status->is_nsfw = true;
					$status->scope = $status->scope === 'public' ? 'unlisted' : $status->scope;
					$status->visibility = $status->scope;
					$status->save();
					StatusService::del($status->id, true);
				}
		});

		Cache::forget('_api:statuses:recent_9:'.$profile->id);

		return 1;
	}
}
