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
use App\Transformer\ActivityPub\Verb\UndoAnnounce;
use GuzzleHttp\{Pool, Client, Promise};
use App\Util\ActivityPub\HttpSignature;
use App\Services\StatusService;

class UndoSharePipeline implements ShouldQueue
{
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
	protected $status;
	public $deleteWhenMissingModels = true;

	public function __construct(Status $status)
	{
		$this->status = $status;
	}

	public function handle()
	{
		$status = $this->status;
		$actor = $status->profile;
		$parent = $status->parent();
		$target = $status->parent()->profile;

		if ($status->uri !== null) {
			return;
		}

		if($target->domain === null) {
			Notification::whereProfileId($target->id)
			->whereActorId($status->profile_id)
			->whereAction('share')
			->whereItemId($status->reblog_of_id)
			->whereItemType('App\Status')
			->delete();
		}

		$this->remoteAnnounceDeliver();

		if($parent->reblogs_count > 0) {
			$parent->reblogs_count = $parent->reblogs_count - 1;
			$parent->save();
			StatusService::del($parent->id);
		}

		$status->forceDelete();

		return 1;
	}

	public function remoteAnnounceDeliver()
	{
		if(config_cache('federation.activitypub.enabled') == false) {
			return 1;
		}

		$status = $this->status;
		$profile = $status->profile;

		$fractal = new Fractal\Manager();
		$fractal->setSerializer(new ArraySerializer());
		$resource = new Fractal\Resource\Item($status, new UndoAnnounce());
		$activity = $fractal->createData($resource)->toArray();

		$audience = $status->profile->getAudienceInbox();

		if(empty($audience) || $status->scope != 'public') {
			return 1;
		}

		$payload = json_encode($activity);

		$client = new Client([
			'timeout'  => config('federation.activitypub.delivery.timeout')
		]);

		$requests = function($audience) use ($client, $activity, $profile, $payload) {
			foreach($audience as $url) {
				$headers = HttpSignature::sign($profile, $url, $activity);
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
