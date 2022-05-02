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

class StoryExpire implements ShouldQueue
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
			$this->handleRemoteExpiry();
			return;
		}

		if($story->active == false) {
			return;
		}

		if($story->expires_at->gt(now())) {
			return;
		}

		$story->active = false;
		$story->save();

		$this->rotateMediaPath();
		$this->fanoutExpiry();

		StoryService::delLatest($story->profile_id);
	}

	protected function rotateMediaPath()
	{
		$story = $this->story;
		$date = date('Y').date('m');
		$old = $story->path;
		$base = "story_archives/{$story->profile_id}/{$date}/";
		$paths = explode('/', $old);
		$path = array_pop($paths);
		$newPath = $base . $path;

		if(Storage::exists($old) == true) {
			$dir = implode('/', $paths);
			Storage::move($old, $newPath);
			Storage::delete($old);
			$story->bearcap_token = null;
			$story->path = $newPath;
			$story->save();
			Storage::deleteDirectory($dir);
		}
	}

	protected function fanoutExpiry()
	{
		$story = $this->story;
		$profile = $story->profile;

		if($story->local == false || $story->remote_url) {
			return;
		}

		$audience = FollowerService::softwareAudience($story->profile_id, 'pixelfed');

		if(empty($audience)) {
			// Return on profiles with no remote followers
			return;
		}

		$fractal = new Fractal\Manager();
		$fractal->setSerializer(new ArraySerializer());
		$resource = new Fractal\Resource\Item($story, new DeleteStory());
		$activity = $fractal->createData($resource)->toArray();

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

	protected function handleRemoteExpiry()
	{
		$story = $this->story;
		$story->active = false;
		$story->save();

		$path = $story->path;

		if(Storage::exists($path) == true) {
			Storage::delete($path);
		}

		$story->views()->delete();
		$story->delete();
	}
}
