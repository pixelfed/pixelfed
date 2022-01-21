<?php

namespace App\Jobs\StatusPipeline;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\CustomEmojiService;
use App\Services\StatusService;

class StatusTagsPipeline implements ShouldQueue
{
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	protected $activity;
	protected $status;

	/**
	 * Create a new job instance.
	 *
	 * @return void
	 */
	public function __construct($activity, $status)
	{
		$this->activity = $activity;
		$this->status = $status;
	}

	/**
	 * Execute the job.
	 *
	 * @return void
	 */
	public function handle()
	{
		$res = $this->activity;

		collect($res['tag'])
		->filter(function($tag) {
			// todo: finish hashtag + mention import
			// return in_array($tag['type'], ['Emoji', 'Hashtag', 'Mention']);
			return $tag && $tag['type'] == 'Emoji';
		})
		->map(function($tag) {
			CustomEmojiService::import($tag['id'], $this->status->id);
		});
	}
}
