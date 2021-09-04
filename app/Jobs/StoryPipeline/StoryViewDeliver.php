<?php

namespace App\Jobs\StoryPipeline;

use App\Story;
use App\Profile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Util\ActivityPub\Helpers;

class StoryViewDeliver implements ShouldQueue
{
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	protected $story;
	protected $profile;

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
	public function __construct(Story $story, Profile $profile)
	{
		$this->story = $story;
		$this->profile = $profile;
	}

	/**
	 * Execute the job.
	 *
	 * @return void
	 */
	public function handle()
	{
		$story = $this->story;

		if($story->local == true) {
			return;
		}

		$actor = $this->profile;
		$target = $story->profile;
		$to = $target->inbox_url;

		$payload = [
			'@context' => 'https://www.w3.org/ns/activitystreams',
			'id' => $actor->permalink('#stories/' . $story->id . '/view'),
			'type' => 'View',
			'to' => $target->permalink(),
			'actor' => $actor->permalink(),
			'object' => [
				'type' => 'Story',
				'object' => $story->object_id
			]
		];

		Helpers::sendSignedObject($actor, $to, $payload);
	}
}
