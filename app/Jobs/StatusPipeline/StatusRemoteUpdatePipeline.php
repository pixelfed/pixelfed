<?php

namespace App\Jobs\StatusPipeline;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Media;
use App\ModLog;
use App\Profile;
use App\Status;
use App\Models\StatusEdit;
use App\Services\StatusService;
use Purify;
use Illuminate\Support\Facades\Http;

class StatusRemoteUpdatePipeline implements ShouldQueue
{
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	public $activity;

	/**
	 * Create a new job instance.
	 */
	public function __construct($activity)
	{
		$this->activity = $activity;
	}

	/**
	 * Execute the job.
	 */
	public function handle(): void
	{
		$activity = $this->activity;
		$status = Status::with('media')->whereObjectUrl($activity['id'])->first();
		if(!$status) {
			return;
		}
		$this->createPreviousEdit($status);
		$this->updateMedia($status, $activity);
		$this->updateImmediateAttributes($status, $activity);
		$this->createEdit($status, $activity);
	}

	protected function createPreviousEdit($status)
	{
		if(!$status->edits()->count()) {
			StatusEdit::create([
				'status_id' => $status->id,
				'profile_id' => $status->profile_id,
				'caption' => $status->caption,
				'spoiler_text' => $status->cw_summary,
				'is_nsfw' => $status->is_nsfw,
				'ordered_media_attachment_ids' => $status->media()->orderBy('order')->pluck('id')->toArray(),
				'created_at' => $status->created_at
			]);
		}
	}

	protected function updateMedia($status, $activity)
	{
		if(!isset($activity['attachment'])) {
			return;
		}
		$ogm = $status->media->count() ? $status->media()->orderBy('order')->get() : collect([]);
		$nm = collect($activity['attachment'])->filter(function($nm) {
			return isset(
				$nm['type'],
				$nm['mediaType'],
				$nm['url']
			) &&
			in_array($nm['type'], ['Document', 'Image', 'Video']) &&
			in_array($nm['mediaType'], explode(',', config('pixelfed.media_types')));
		});

		// Skip when no media
		if(!$ogm->count() && !$nm->count()) {
			return;
		}

		Media::whereProfileId($status->profile_id)
			->whereStatusId($status->id)
			->update([
				'status_id' => null
			]);

		$nm->each(function($n, $key) use($status) {
			$res = Http::retry(3, 100, throw: false)->head($n['url']);

			if(!$res->successful()) {
				return;
			}

			if(!in_array($res->header('content-type'), explode(',',config('pixelfed.media_types')))) {
				return;
			}

			$m = new Media;
			$m->status_id = $status->id;
			$m->profile_id = $status->profile_id;
			$m->remote_media = true;
			$m->media_path = $n['url'];
            $m->mime = $res->header('content-type');
            $m->size = $res->hasHeader('content-length') ? $res->header('content-length') : null;
			$m->caption = isset($n['name']) && !empty($n['name']) ? Purify::clean($n['name']) : null;
			$m->remote_url = $n['url'];
            $m->blurhash = isset($n['blurhash']) && (strlen($n['blurhash']) < 50) ? $n['blurhash'] : null;
			$m->width = isset($n['width']) && !empty($n['width']) ? $n['width'] : null;
			$m->height = isset($n['height']) && !empty($n['height']) ? $n['height'] : null;
			$m->skip_optimize = true;
			$m->order = $key + 1;
			$m->save();
		});
	}

	protected function updateImmediateAttributes($status, $activity)
	{
		if(isset($activity['content'])) {
			$status->caption = strip_tags($activity['content']);
			$status->rendered = Purify::clean($activity['content']);
		}

		if(isset($activity['sensitive'])) {
			if((bool) $activity['sensitive'] == false) {
				$status->is_nsfw = false;
				$exists = ModLog::whereObjectType('App\Status::class')
					->whereObjectId($status->id)
					->whereAction('admin.status.moderate')
					->exists();
				if($exists == true) {
					$status->is_nsfw = true;
				}
				$profile = Profile::find($status->profile_id);
				if(!$profile || $profile->cw == true) {
					$status->is_nsfw = true;
				}
			} else {
				$status->is_nsfw = true;
			}
		}

		if(isset($activity['summary'])) {
			$status->cw_summary = Purify::clean($activity['summary']);
		} else {
			$status->cw_summary = null;
		}

		$status->edited_at = now();
		$status->save();
		StatusService::del($status->id);
	}

	protected function createEdit($status, $activity)
	{
		$cleaned = isset($activity['content']) ? Purify::clean($activity['content']) : null;
		$spoiler_text = isset($activity['summary']) ? Purify::clean($activity['summary']) : null;
		$sensitive = isset($activity['sensitive']) ? $activity['sensitive'] : null;
		$mids = $status->media()->count() ? $status->media()->orderBy('order')->pluck('id')->toArray() : null;
		StatusEdit::create([
			'status_id' => $status->id,
			'profile_id' => $status->profile_id,
			'caption' => $cleaned,
			'spoiler_text' => $spoiler_text,
			'is_nsfw' => $sensitive,
			'ordered_media_attachment_ids' => $mids
		]);
	}
}
