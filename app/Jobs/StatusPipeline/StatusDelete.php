<?php

namespace App\Jobs\StatusPipeline;

use DB, Storage;
use App\{
	AccountInterstitial,
	CollectionItem,
	MediaTag,
	Notification,
	Report,
	Status,
	StatusHashtag,
};
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use League\Fractal;
use Illuminate\Support\Str;
use League\Fractal\Serializer\ArraySerializer;
use App\Transformer\ActivityPub\Verb\DeleteNote;
use App\Util\ActivityPub\Helpers;
use GuzzleHttp\Pool;
use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use App\Util\ActivityPub\HttpSignature;
use App\Services\CollectionService;
use App\Services\StatusService;
use App\Services\MediaStorageService;

class StatusDelete implements ShouldQueue
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
		$profile = $this->status->profile;

		StatusService::del($status->id, true);

		if(in_array($status->type, ['photo', 'photo:album', 'video', 'video:album', 'photo:video:album'])) {
			$profile->status_count = $profile->status_count - 1;
			$profile->save();
		}

		if(config_cache('federation.activitypub.enabled') == true) {
			$this->fanoutDelete($status);
		} else {
			$this->unlinkRemoveMedia($status);
		}

	}

	public function unlinkRemoveMedia($status)
	{
		foreach ($status->media as $media) {
			MediaStorageService::delete($media, true);
		}

		if($status->in_reply_to_id) {
			DB::transaction(function() use($status) {
				$parent = Status::findOrFail($status->in_reply_to_id);
				--$parent->reply_count;
				$parent->save();
			});
		}

        DB::transaction(function() use($status) {
            CollectionItem::whereObjectType('App\Status')
                ->whereObjectId($status->id)
                ->get()
                ->each(function($col) {
                    $id = $col->collection_id;
                    $sid = $col->object_id;
                    $col->delete();
                    CollectionService::removeItem($id, $sid);
                });
        });

		DB::transaction(function() use($status) {
			$comments = Status::where('in_reply_to_id', $status->id)->get();
			foreach ($comments as $comment) {
				$comment->in_reply_to_id = null;
				$comment->save();
				Notification::whereItemType('App\Status')
					->whereItemId($comment->id)
					->delete();
			}
			$status->likes()->delete();
			Notification::whereItemType('App\Status')
				->whereItemId($status->id)
				->delete();
			StatusHashtag::whereStatusId($status->id)->delete();
			Report::whereObjectType('App\Status')
				->whereObjectId($status->id)
				->delete();
			MediaTag::where('status_id', $status->id)
				->cursor()
				->each(function($tag) {
					Notification::where('item_type', 'App\MediaTag')
						->where('item_id', $tag->id)
						->forceDelete();
					$tag->delete();
			});
			AccountInterstitial::where('item_type', 'App\Status')
				->where('item_id', $status->id)
				->delete();

			$status->forceDelete();
		});

		return true;
	}

	protected function fanoutDelete($status)
	{
		$audience = $status->profile->getAudienceInbox();
		$profile = $status->profile;

		$fractal = new Fractal\Manager();
		$fractal->setSerializer(new ArraySerializer());
		$resource = new Fractal\Resource\Item($status, new DeleteNote());
		$activity = $fractal->createData($resource)->toArray();

		$this->unlinkRemoveMedia($status);

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
