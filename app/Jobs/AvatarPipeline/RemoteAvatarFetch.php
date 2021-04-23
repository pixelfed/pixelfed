<?php

/*
 * SPDX-FileCopyrightText: 2018 Daniel Supernault 
 * SPDX-License-Identifier: AGPL-3.0-only
 */

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

		if(config('pixelfed.cloud_storage') !== true) {
			return 1;
		}

		if($profile->domain == null || $profile->private_key) {
			return 1;
		}

		$avatar = Avatar::firstOrCreate([
			'profile_id' => $profile->id
		]);

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

		MediaStorageService::avatar($avatar);

		return 1;
	}
}
