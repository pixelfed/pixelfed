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
use App\Jobs\MentionPipeline\MentionPipeline;
use App\Mention;
use App\Services\AccountService;
use App\Hashtag;
use App\StatusHashtag;
use App\Services\TrendingHashtagService;

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
		$status = $this->status;
		$tags = collect($res['tag']);

		// Emoji
		$tags->filter(function($tag) {
			return $tag && isset($tag['id'], $tag['icon'], $tag['name'], $tag['type']) && $tag['type'] == 'Emoji';
		})
		->map(function($tag) {
			CustomEmojiService::import($tag['id'], $this->status->id);
		});

		// Hashtags
		$tags->filter(function($tag) {
			return $tag && $tag['type'] == 'Hashtag' && isset($tag['href'], $tag['name']);
		})
		->map(function($tag) use($status) {
			$name = substr($tag['name'], 0, 1) == '#' ?
				substr($tag['name'], 1) : $tag['name'];

			$banned = TrendingHashtagService::getBannedHashtagNames();

			if(count($banned)) {
                if(in_array(strtolower($name), array_map('strtolower', $banned))) {
                   	return;
                }
            }

			$hashtag = Hashtag::firstOrCreate([
				'slug' => str_slug($name)
			], [
				'name' => $name
			]);

			StatusHashtag::firstOrCreate([
				'status_id' => $status->id,
				'hashtag_id' => $hashtag->id,
				'profile_id' => $status->profile_id,
				'status_visibility' => $status->scope
			]);
		});

		// Mentions
		$tags->filter(function($tag) {
			return $tag &&
				$tag['type'] == 'Mention' &&
				isset($tag['href']) &&
				substr($tag['href'], 0, 8) === 'https://' &&
				parse_url($tag['href'], PHP_URL_HOST) == config('pixelfed.domain.app');
		})
		->map(function($tag) use($status) {
			$parts = explode('/', $status['href']);
			if(!$parts) {
				return;
			}
			$pid = AccountService::usernameToId(end($parts));
			if(!$pid) {
				return;
			}
			$mention = new Mention;
			$mention->status_id = $status->id;
			$mention->profile_id = $pid;
			$mention->save();
			MentionPipeline::dispatch($status, $mention);
		});
	}
}
