<?php

namespace App\Jobs\StoryPipeline;

use App\Story;
use App\Status;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Util\ActivityPub\Helpers;

class StoryReactionDeliver implements ShouldQueue
{
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	protected $story;
	protected $status;

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
	public function __construct(Story $story, Status $status)
	{
		$this->story = $story;
		$this->status = $status;
	}

	/**
	 * Execute the job.
	 *
	 * @return void
	 */
	public function handle()
	{
		$story = $this->story;
		$status = $this->status;

		if($story->local == true) {
			return;
		}

		$target = $story->profile;
		$actor = $status->profile;
		$to = $target->inbox_url;

		$payload = [
			'@context' => 'https://www.w3.org/ns/activitystreams',
			'id' => $status->permalink(),
			'type' => 'Story:Reaction',
			'to' => $target->permalink(),
			'actor' => $actor->permalink(),
			'content' => $status->caption,
			'inReplyTo' => $story->object_id,
			'published' => $status->created_at->toAtomString()
		];

		Helpers::sendSignedObject($actor, $to, $payload);
	}
}
