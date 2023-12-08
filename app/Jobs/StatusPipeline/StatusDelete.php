<?php

namespace App\Jobs\StatusPipeline;

use DB, Cache, Storage;
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
use App\Services\NotificationService;
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

    public $timeout = 900;
    public $tries = 2;

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
		if($profile) {
			if(in_array($status->type, ['photo', 'photo:album', 'video', 'video:album', 'photo:video:album'])) {
				$profile->status_count = $profile->status_count - 1;
				$profile->save();
			}
		}

		Cache::forget('pf:atom:user-feed:by-id:' . $status->profile_id);

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
            MediaDeletePipeline::dispatch($media);
        });

		if($status->in_reply_to_id) {
			$parent = Status::findOrFail($status->in_reply_to_id);
			--$parent->reply_count;
			$parent->save();
			StatusService::del($parent->id);
		}

        Bookmark::whereStatusId($status->id)->delete();

        CollectionItem::whereObjectType('App\Status')
            ->whereObjectId($status->id)
            ->get()
            ->each(function($col) {
                CollectionService::removeItem($col->collection_id, $col->object_id);
                $col->delete();
        });

        $dms = DirectMessage::whereStatusId($status->id)->get();
        foreach($dms as $dm) {
            $not = Notification::whereItemType('App\DirectMessage')
                ->whereItemId($dm->id)
                ->first();
            if($not) {
                NotificationService::del($not->profile_id, $not->id);
                $not->forceDeleteQuietly();
            }
            $dm->delete();
        }
        Like::whereStatusId($status->id)->delete();

        $mediaTags = MediaTag::where('status_id', $status->id)->get();
        foreach($mediaTags as $mtag) {
            $not = Notification::whereItemType('App\MediaTag')
                ->whereItemId($mtag->id)
                ->first();
            if($not) {
                NotificationService::del($not->profile_id, $not->id);
                $not->forceDeleteQuietly();
            }
            $mtag->delete();
        }
        Mention::whereStatusId($status->id)->forceDelete();

		Notification::whereItemType('App\Status')
			->whereItemId($status->id)
			->forceDelete();

		Report::whereObjectType('App\Status')
			->whereObjectId($status->id)
			->delete();

        StatusArchived::whereStatusId($status->id)->delete();
        $statusHashtags = StatusHashtag::whereStatusId($status->id)->get();
        foreach($statusHashtags as $stag) {
        	$stag->delete();
        }
        StatusView::whereStatusId($status->id)->delete();
		Status::whereInReplyToId($status->id)->update(['in_reply_to_id' => null]);

		AccountInterstitial::where('item_type', 'App\Status')
			->where('item_id', $status->id)
			->delete();

		$status->delete();

		return 1;
	}

	public function fanoutDelete($status)
	{
		$profile = $status->profile;

		if(!$profile) {
			return;
		}

		$audience = $status->profile->getAudienceInbox();

		$fractal = new Fractal\Manager();
		$fractal->setSerializer(new ArraySerializer());
		$resource = new Fractal\Resource\Item($status, new DeleteNote());
		$activity = $fractal->createData($resource)->toArray();

		$this->unlinkRemoveMedia($status);

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

        return 1;
	}
}
