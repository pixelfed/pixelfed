<?php

namespace App\Jobs\LikePipeline;

use Cache, DB, Log;
use Illuminate\Support\Facades\Redis;
use App\{Like, Notification};
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Util\ActivityPub\Helpers;
use League\Fractal;
use League\Fractal\Serializer\ArraySerializer;
use App\Transformer\ActivityPub\Verb\UndoLike as LikeTransformer;
use App\Services\StatusService;

class UnlikePipeline implements ShouldQueue
{
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	protected $like;

	/**
	 * Delete the job if its models no longer exist.
	 *
	 * @var bool
	 */
	public $deleteWhenMissingModels = true;

	public $timeout = 5;
	public $tries = 1;

	/**
	 * Create a new job instance.
	 *
	 * @return void
	 */
	public function __construct(Like $like)
	{
		$this->like = $like;
	}

	/**
	 * Execute the job.
	 *
	 * @return void
	 */
	public function handle()
	{
		$like = $this->like;

		// Check if like still exists (in case it was already deleted)
		if (!$like) {
			Log::info("UnlikePipeline: Like no longer exists, skipping job");
			return;
		}

		$status = $this->like->status;
		$actor = $this->like->actor;

		// Verify both status and actor exist
		if (!$status) {
			Log::info("UnlikePipeline: Status no longer exists for like {$like->id}, skipping job");
			return;
		}
		if (!$actor) {
			Log::info("UnlikePipeline: Actor no longer exists for like {$like->id}, skipping job");
			return;
		}

		$status->likes_count = DB::table('likes')->whereStatusId($status->id)->count();
        $status->save();

		StatusService::refresh($status->id);

		if($actor->id !== $status->profile_id && $status->url && $actor->domain == null) {
			$this->remoteLikeDeliver();
		}

		$exists = Notification::whereProfileId($status->profile_id)
				  ->whereActorId($actor->id)
				  ->whereAction('like')
				  ->whereItemId($status->id)
				  ->whereItemType('App\Status')
				  ->first();

		if($exists) {
			$exists->delete();
		}

		$like = Like::whereProfileId($actor->id)->whereStatusId($status->id)->first();

		if(!$like) {
			return;
		}

		$like->forceDelete();

		return;
	}

	public function remoteLikeDeliver()
	{
		$like = $this->like;
		$status = $this->like->status;
		$actor = $this->like->actor;

		// Verify all required models exist before attempting delivery
		if (!$like) {
			Log::info("UnlikePipeline: Like missing for remote delivery, skipping");
			return;
		}
		if (!$status) {
			Log::info("UnlikePipeline: Status missing for remote delivery, skipping");
			return;
		}
		if (!$actor) {
			Log::info("UnlikePipeline: Actor missing for remote delivery, skipping");
			return;
		}

		// Verify status has a profile
		if (!$status->profile) {
			Log::info("UnlikePipeline: Status profile missing for like {$like->id}, skipping remote delivery");
			return;
		}

		$fractal = new Fractal\Manager();
		$fractal->setSerializer(new ArraySerializer());
		$resource = new Fractal\Resource\Item($like, new LikeTransformer());
		$activity = $fractal->createData($resource)->toArray();

		$url = $status->profile->sharedInbox ?? $status->profile->inbox_url;

		if (!$url) {
			Log::info("UnlikePipeline: No inbox URL available for like {$like->id}, skipping remote delivery");
			return;
		}

		Helpers::sendSignedObject($actor, $url, $activity);
	}
}
