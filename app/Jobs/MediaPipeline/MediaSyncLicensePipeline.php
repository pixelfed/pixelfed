<?php

namespace App\Jobs\MediaPipeline;

use App\Media;
use App\User;
use Cache;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Services\StatusService;

class MediaSyncLicensePipeline implements ShouldQueue
{
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	protected $userId;
	protected $licenseId;

	public function __construct($userId, $licenseId)
	{
		$this->userId = $userId;
		$this->licenseId = $licenseId;
	}

	public function handle()
	{
		$licenseId = $this->licenseId;
		$userId = $this->userId;

		// Verify required data exists
		if (!$licenseId) {
			Log::info("MediaSyncLicensePipeline: License ID not provided, skipping job");
			return 1;
		}
		if (!$userId) {
			Log::info("MediaSyncLicensePipeline: User ID not provided, skipping job");
			return 1;
		}

		Media::whereUserId($this->userId)
			->chunk(100, function($medias) use($licenseId) {
				foreach($medias as $media) {
					$media->license = $licenseId;
					$media->save();
					Cache::forget('status:transformer:media:attachments:'. $media->status_id);
					StatusService::del($media->status_id);
				}
		});
	}

}
