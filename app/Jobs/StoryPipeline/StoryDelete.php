<?php

namespace App\Jobs\StoryPipeline;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Storage;
use App\Story;
use League\Fractal;
use League\Fractal\Serializer\ArraySerializer;
use App\Transformer\ActivityPub\Verb\DeleteStory;
use App\Util\ActivityPub\Helpers;
use GuzzleHttp\Pool;
use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use App\Util\ActivityPub\HttpSignature;
use App\Services\FollowerService;
use App\Services\StoryService;

class StoryDelete implements ShouldQueue
{
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	protected $story;

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
	public function __construct(Story $story)
	{
		$this->story = $story;
	}

	/**
	 * Execute the job.
	 *
	 * @return void
	 */
	public function handle()
	{
		$story = $this->story;

		if($story->local == false) {
			return;
		}

		StoryService::removeRotateQueue($story->id);
		StoryService::delLatest($story->profile_id);
		StoryService::delById($story->id);

		if(Storage::exists($story->path) == true) {
			Storage::delete($story->path);
		}

		$story->views()->delete();

		$profile = $story->profile;

		$activity = [
			'@context' => 'https://www.w3.org/ns/activitystreams',
			'id' => $story->url() . '#delete',
			'type' => 'Delete',
			'actor' => $profile->permalink(),
			'object' => [
				'id' => $story->url(),
				'type' => 'Story',
			],
		];

		$this->fanoutExpiry($profile, $activity);

		// delete notifications
		// delete polls
		// delete reports

		$story->delete();

		return;
	}

	protected function fanoutExpiry($profile, $activity)
	{
		$audience = FollowerService::softwareAudience($profile->id, 'pixelfed');

		if(empty($audience)) {
			// Return on profiles with no remote followers
			return;
		}

		$payload = json_encode($activity);

		$client = new Client([
			'timeout'  => config('federation.activitypub.delivery.timeout')
		]);

		$requests = function($audience) use ($client, $activity, $profile, $payload) {
			foreach($audience as $url) {
				$version = config('pixelfed.version');
				$appUrl = config('app.url');
				$headers = HttpSignature::sign($profile, $url, $activity, [
					'Content-Type'	=> 'application/ld+json; profile="https://www.w3.org/ns/activitystreams"',
					'User-Agent'	=> "(Pixelfed/{$version}; +{$appUrl})",
				]);
				yield function() use ($client, $url, $headers, $payload) {
					return $client->postAsync($url, [
						'curl' => [
							CURLOPT_HTTPHEADER => $headers,
							CURLOPT_POSTFIELDS => $payload,
							CURLOPT_HEADER => true
						]
					]);
				};
			}
		};

		$pool = new Pool($client, $requests($audience), [
			'concurrency' => config('federation.activitypub.delivery.concurrency'),
			'fulfilled' => function ($response, $index) {
			},
			'rejected' => function ($reason, $index) {
			}
		]);

		$promise = $pool->promise();

		$promise->wait();
	}
}
