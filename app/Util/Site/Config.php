<?php

namespace App\Util\Site;

use Cache;

class Config {

	public static function get() {
		return Cache::remember('api:site:configuration', now()->addMinutes(30), function() {
			return [
				'uploader' => [
					'max_photo_size' => config('pixelfed.max_photo_size'),
					'max_caption_length' => config('pixelfed.max_caption_length'),
					'album_limit' => config('pixelfed.max_album_length'),
					'image_quality' => config('pixelfed.image_quality'),

					'optimize_image' => config('pixelfed.optimize_image'),
					'optimize_video' => config('pixelfed.optimize_video'),

					'media_types' => config('pixelfed.media_types'),
					'enforce_account_limit' => config('pixelfed.enforce_account_limit')
				],

				'activitypub' => [
					'enabled' => config('federation.activitypub.enabled'),
					'remote_follow' => config('federation.activitypub.remoteFollow')
				],

				'ab' => [
					'lc' => config('exp.lc'),
					'rec' => config('exp.rec'),
					'loops' => config('exp.loops')
				],

				'site' => [
					'domain' => config('pixelfed.domain.app'),
					'url'    => config('app.url')
				],

				'username' => [
					'remote' => [
						'formats' => config('instance.username.remote.formats'),
						'format' => config('instance.username.remote.format'),
						'custom' => config('instance.username.remote.custom')
					]
				]
			];
		});
	}

	public static function json() {
		return json_encode(self::get(), JSON_FORCE_OBJECT);
	}
}
