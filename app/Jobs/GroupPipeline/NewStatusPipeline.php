<?php

namespace App\Jobs\GroupPipeline;

use App\Notification;
use App\Hashtag;
use App\Mention;
use App\Profile;
use App\Status;
use App\StatusHashtag;
use App\Models\GroupPostHashtag;
use App\Models\GroupPost;
use Cache;
use DB;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Redis;
use App\Services\MediaStorageService;
use App\Services\NotificationService;
use App\Services\StatusService;
use App\Util\Lexer\Autolink;
use App\Util\Lexer\Extractor;

class NewStatusPipeline implements ShouldQueue
{
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	protected $status;
	protected $gp;
	protected $tags;
	protected $mentions;

	public function __construct(Status $status, GroupPost $gp)
	{
		$this->status = $status;
		$this->gp = $gp;
	}

	public function handle()
	{
		$status = $this->status;

		$autolink = Autolink::create()
			->setAutolinkActiveUsersOnly(true)
			->setBaseHashPath("/groups/{$status->group_id}/topics/")
			->setBaseUserPath("/groups/{$status->group_id}/username/")
			->autolink($status->caption);

        $entities = Extractor::create()->extract($status->caption);

		$autolink = str_replace('/discover/tags/', '/groups/' . $status->group_id . '/topics/', $autolink);

		$status->rendered = nl2br($autolink);
		$status->entities = null;
		$status->save();

		$this->tags = array_unique($entities['hashtags']);
		$this->mentions = array_unique($entities['mentions']);

		if(count($this->tags)) {
			$this->storeHashtags();
		}

		if(count($this->mentions)) {
			$this->storeMentions($this->mentions);
		}
	}

	protected function storeHashtags()
	{
		$tags = $this->tags;
		$status = $this->status;
		$gp = $this->gp;

		foreach ($tags as $tag) {
			if(mb_strlen($tag) > 124) {
				continue;
			}

			DB::transaction(function () use ($status, $tag, $gp) {
				$slug = str_slug($tag, '-', false);
				$hashtag = Hashtag::firstOrCreate(
					['name' => $tag, 'slug' => $slug]
				);
				GroupPostHashtag::firstOrCreate(
					[
						'group_id' => $status->group_id,
						'group_post_id' => $gp->id,
						'status_id' => $status->id,
						'hashtag_id' => $hashtag->id,
						'profile_id' => $status->profile_id,
					]
				);

			});
		}

		if(count($this->mentions)) {
			$this->storeMentions();
		}
		StatusService::del($status->id);
	}

	protected function storeMentions()
	{
		$mentions = $this->mentions;
		$status = $this->status;

		foreach ($mentions as $mention) {
			$mentioned = Profile::whereUsername($mention)->first();

			if (empty($mentioned) || !isset($mentioned->id)) {
				continue;
			}

			DB::transaction(function () use ($status, $mentioned) {
				$m = new Mention();
				$m->status_id = $status->id;
				$m->profile_id = $mentioned->id;
				$m->save();

				MentionPipeline::dispatch($status, $m);
			});
		}
		StatusService::del($status->id);
	}
}
