<?php

namespace App\Jobs\SharePipeline;

use Cache, Log;
use Illuminate\Support\Facades\Redis;
use App\{Status, Notification};
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use League\Fractal;
use League\Fractal\Serializer\ArraySerializer;
use App\Transformer\ActivityPub\Verb\Announce;
use GuzzleHttp\{Pool, Client, Promise};
use App\Util\ActivityPub\HttpSignature;
use App\Services\ReblogService;
use App\Services\StatusService;
use App\Jobs\HomeFeedPipeline\FeedInsertPipeline;

class SharePipeline implements ShouldQueue
{
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
	public function __construct(Status $status)
	{
		$this->status = $status;
	}

	/**
	 * Execute the job.
	 *
	 * @return void
	 */
	public function handle()
	{
		$status = $this->status;
		$parent = Status::find($this->status->reblog_of_id);
        if(!$parent) {
            return;
        }
		$actor = $status->profile;
		$target = $parent->profile;

		if ($status->uri !== null) {
			// Ignore notifications to remote statuses
			return;
		}

		if($target->id === $status->profile_id) {
			$this->remoteAnnounceDeliver();
			return true;
		}

		ReblogService::addPostReblog($parent->profile_id, $status->id);

		$parent->reblogs_count = $parent->reblogs_count + 1;
		$parent->save();
		StatusService::del($parent->id);

		Notification::firstOrCreate(
			[
				'profile_id' => $target->id,
				'actor_id' => $actor->id,
				'action' => 'share',
				'item_type' => 'App\Status',
				'item_id' => $status->reblog_of_id ?? $status->id,
			]
		);

		FeedInsertPipeline::dispatch($status->id, $status->profile_id)->onQueue('feed');

		return $this->remoteAnnounceDeliver();
	}

	public function remoteAnnounceDeliver()
	{
		if(config('app.env') !== 'production' || config_cache('federation.activitypub.enabled') == false) {
			return true;
		}
		$status = $this->status;
		$profile = $status->profile;

		$fractal = new Fractal\Manager();
		$fractal->setSerializer(new ArraySerializer());
		$resource = new Fractal\Resource\Item($status, new Announce());
		$activity = $fractal->createData($resource)->toArray();

		$audience = $status->profile->getAudienceInbox();

		if(empty($audience) || $status->scope != 'public') {
			// Return on profiles with no remote followers
			return;
		}

		$payload = json_encode($activity);

		$client = new Client([
			'timeout'  => config('federation.activitypub.delivery.timeout')
		]);

		$version = config('pixelfed.version');
		$appUrl = config('app.url');
		$userAgent = "(Pixelfed/{$version}; +{$appUrl})";

		$requests = function($audience) use ($client, $activity, $profile, $payload, $userAgent) {
			foreach($audience as $url) {
				$headers = HttpSignature::sign($profile, $url, $activity, [
					'Content-Type'	=> 'application/ld+json; profile="https://www.w3.org/ns/activitystreams"',
					'User-Agent'	=> $userAgent,
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
