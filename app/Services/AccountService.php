<?php

namespace App\Services;

use Cache;
use App\Profile;
use App\Status;
use App\UserSetting;
use App\Transformer\Api\AccountTransformer;
use League\Fractal;
use League\Fractal\Serializer\ArraySerializer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AccountService
{
	const CACHE_KEY = 'pf:services:account:';

	public static function get($id, $softFail = false)
	{
		return Cache::remember(self::CACHE_KEY . $id, 43200, function() use($id, $softFail) {
			$fractal = new Fractal\Manager();
			$fractal->setSerializer(new ArraySerializer());
			$profile = Profile::find($id);
			if(!$profile) {
				if($softFail) {
					return null;
				}
				abort(404);
			}
			$resource = new Fractal\Resource\Item($profile, new AccountTransformer());
			return $fractal->createData($resource)->toArray();
		});	
	}

	public static function del($id)
	{
		return Cache::forget(self::CACHE_KEY . $id);
	}

	public static function settings($id)
	{
		$settings = UserSetting::whereUserId($id)->first();
		if(!$settings) {
			return self::defaultSettings();
		}
		return collect($settings)
		->filter(function($item, $key) {
			return in_array($key, array_keys(self::defaultSettings())) == true;
		})
		->map(function($item, $key) {
			if($key == 'compose_settings') {
				$cs = self::defaultSettings()['compose_settings'];
				return array_merge($cs, $item ?? []);
			}

			if($key == 'other') {
				$other =  self::defaultSettings()['other'];
				return array_merge($other, $item ?? []);
			}
			return $item;
		});
	}

	public static function canEmbed($id)
	{
		return self::settings($id)['other']['disable_embeds'] == false;
	}

	public static function defaultSettings()
	{
		return [
			'crawlable' => true,
			'public_dm' => false,
			'reduce_motion' => false,
			'high_contrast_mode' => false,
			'video_autoplay' => false,
			'show_profile_follower_count' => true,
			'show_profile_following_count' => true,
			'compose_settings' => [
				'default_scope' => 'public',
				'default_license' => 1,
				'media_descriptions' => false
			],
			'other' => [
				'advanced_atom' => false,
				'disable_embeds' => false,
				'mutual_mention_notifications' => false,
				'hide_collections' => false,
				'hide_like_counts' => false,
				'hide_groups' => false,
				'hide_stories' => false,
				'disable_cw' => false,
			]
		];
	}

	public static function syncPostCount($id)
	{
		$profile = Profile::find($id);

		if(!$profile) {
			return false;
		}

		$key = self::CACHE_KEY . 'pcs:' . $id;

		if(Cache::has($key)) {
			return;
		}

		$count = Status::whereProfileId($id)
			->whereNull('in_reply_to_id')
			->whereNull('reblog_of_id')
			->whereIn('scope', ['public', 'unlisted', 'private'])
			->count();

		$profile->status_count = $count;
		$profile->save();

		Cache::put($key, 1, 900);
		return true;
	}

	public static function usernameToId($username)
	{
		$key = self::CACHE_KEY . 'u2id:' . hash('sha256', $username);
		return Cache::remember($key, 900, function() use($username) {
			$s = Str::of($username);
			if($s->contains('@') && !$s->startsWith('@')) {
				$username = "@{$username}";
			}
			$profile = DB::table('profiles')
				->whereUsername($username)
				->first();
			if(!$profile) {
				return null;
			}
			return (string) $profile->id;
		});
	}
}
