<?php

namespace App\Jobs\StatusPipeline;

use DB, Storage;
use App\{
	AccountInterstitial,
    Bookmark,
	CollectionItem,
    DirectMessage,
    Like,
    Media,
	MediaTag,
    Mention,
	Notification,
	Report,
	Status,
    StatusArchived,
	StatusHashtag,
    StatusView
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
use App\Jobs\MediaPipeline\MediaDeletePipeline;

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
			return $this->fanoutDelete($status);
		} else {
			return $this->unlinkRemoveMedia($status);
		}
	}

	public function unlinkRemoveMedia($status)
	{
        Media::whereStatusId($status->id)
        ->get()
        ->each(function($media) {
            MediaDeletePipeline::dispatchNow($media);
        });

		if($status->in_reply_to_id) {
			$parent = Status::findOrFail($status->in_reply_to_id);
			--$parent->reply_count;
			$parent->save();
		}

        Bookmark::whereStatusId($status->id)->delete();

        CollectionItem::whereObjectType('App\Status')
            ->whereObjectId($status->id)
            ->get()
            ->each(function($col) {
                CollectionService::removeItem($col->collection_id, $col->object_id);
                $col->delete();
        });

        DirectMessage::whereStatusId($status->id)->delete();
        Like::whereStatusId($status->id)->delete();

		MediaTag::where('status_id', $status->id)->delete();
        Mention::whereStatusId($status->id)->forceDelete();

		Notification::whereItemType('App\Status')
			->whereItemId($status->id)
			->forceDelete();

		Report::whereObjectType('App\Status')
			->whereObjectId($status->id)
			->delete();

        StatusArchived::whereStatusId($status->id)->delete();
        StatusHashtag::whereStatusId($status->id)->delete();
        StatusView::whereStatusId($status->id)->delete();
		Status::whereInReplyToId($status->id)->update(['in_reply_to_id' => null]);

		AccountInterstitial::where('item_type', 'App\Status')
			->where('item_id', $status->id)
			->delete();

		$status->forceDelete();

		return 1;
	}

	public function fanoutDelete($status)
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

        return 1;
	}
}
