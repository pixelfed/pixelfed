<?php

namespace App\Jobs\AvatarPipeline;

use App\Avatar;
use App\Profile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Util\ActivityPub\Helpers;
use Illuminate\Support\Str;
use Zttp\Zttp;
use App\Http\Controllers\AvatarController;
use Storage;
use Log;
use Illuminate\Http\File;
use App\Services\MediaStorageService;
use App\Services\ActivityPubFetchService;

class RemoteAvatarFetch implements ShouldQueue
{
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	protected $profile;

	/**
	* Delete the job if its models no longer exist.
	*
	* @var bool
	*/
	public $deleteWhenMissingModels = true;

	/**
	 * The number of times the job may be attempted.
	 *
	 * @var int
	 */
	public $tries = 1;
	public $timeout = 300;
	public $maxExceptions = 1;

	/**
	* Create a new job instance.
	*
	* @return void
	*/
	public function __construct(Profile $profile)
	{
		$this->profile = $profile;
	}

	/**
	* Execute the job.
	*
	* @return void
	*/
	public function handle()
	{
		$profile = $this->profile;

		if(boolval(config_cache('pixelfed.cloud_storage')) == false && boolval(config_cache('federation.avatars.store_local')) == false) {
			return 1;
		}

		if($profile->domain == null || $profile->private_key) {
			return 1;
		}

		$avatar = Avatar::whereProfileId($profile->id)->first();

		if(!$avatar) {
			$avatar = new Avatar;
			$avatar->profile_id = $profile->id;
			$avatar->save();
		}

		if($avatar->media_path == null && $avatar->remote_url == null) {
			$avatar->media_path = 'public/avatars/default.jpg';
			$avatar->is_remote = true;
			$avatar->save();
		}

		$person = Helpers::fetchFromUrl($profile->remote_url);

		if(!$person || !isset($person['@context'])) {
			return 1;
		}

		if( !isset($person['icon']) ||
			!isset($person['icon']['type']) ||
			!isset($person['icon']['url'])
		) {
			return 1;
		}

		if($person['icon']['type'] !== 'Image') {
			return 1;
		}

		if(!Helpers::validateUrl($person['icon']['url'])) {
			return 1;
		}

		$icon = $person['icon'];

		$avatar->remote_url = $icon['url'];
		$avatar->save();

		MediaStorageService::avatar($avatar, boolval(config_cache('pixelfed.cloud_storage')) == false, true);

		return 1;
	}
}
