<?php

namespace App\Services;

use App\Models\CustomEmoji;
use App\Util\ActivityPub\Helpers;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class CustomEmojiService
{
	public static function get($shortcode)
	{
		if(config('federation.custom_emoji.enabled') == false) {
			return;
		}

		return CustomEmoji::whereShortcode($shortcode)->first();
	}

	public static function import($url, $id = false)
	{
		if(config('federation.custom_emoji.enabled') == false) {
			return;
		}

		if(Helpers::validateUrl($url) == false) {
			return;
		}

		$emoji = CustomEmoji::whereUri($url)->first();
		if($emoji) {
			return;
		}

		$res = Http::acceptJson()->get($url);

		if($res->successful()) {
			$json = $res->json();

			if(
				!$json ||
				!isset($json['id']) ||
				!isset($json['type']) ||
				$json['type'] !== 'Emoji' ||
				!isset($json['icon']) ||
				!isset($json['icon']['mediaType']) ||
				!isset($json['icon']['url']) ||
				!isset($json['icon']['type']) ||
				$json['icon']['type'] !== 'Image' ||
				!in_array($json['icon']['mediaType'], ['image/jpeg', 'image/png', 'image/jpg'])
			) {
				return;
			}

			if(!self::headCheck($json['icon']['url'])) {
				return;
			}

			$emoji = new CustomEmoji;
			$emoji->shortcode = $json['name'];
			$emoji->uri = $json['id'];
			$emoji->domain = parse_url($json['id'], PHP_URL_HOST);
			$emoji->image_remote_url = $json['icon']['url'];
			$emoji->save();

			$ext = '.' . last(explode('/', $json['icon']['mediaType']));
			$dest = storage_path('app/public/emoji/') . $emoji->id . $ext;
			copy($emoji->image_remote_url, $dest);
			$emoji->media_path = 'emoji/' . $emoji->id . $ext;
			$emoji->save();

			$name = str_replace(':', '', $json['name']);
			Cache::forget('pf:custom_emoji');
			Cache::forget('pf:custom_emoji:' . $name);
			if($id) {
				StatusService::del($id);
			}
			return;
		} else {
			return;
		}
	}

	public static function headCheck($url)
	{
		$res = Http::head($url);

		if(!$res->successful()) {
			return false;
		}

		$type = $res->header('content-type');
		$length = $res->header('content-length');

		if(
			!$type ||
			!$length ||
			!in_array($type, ['image/jpeg', 'image/png', 'image/jpg']) ||
			$length > config('federation.custom_emoji.max_size')
		) {
			return false;
		}

		return true;
	}

	public static function all()
	{
		return Cache::rememberForever('pf:custom_emoji', function() {
			$pgsql = config('database.default') === 'pgsql';
			return CustomEmoji::when(!$pgsql, function($q, $pgsql) {
					return $q->groupBy('shortcode');
				})
				->get()
				->map(function($emojo) {
					$url = url('storage/' . $emojo->media_path);
					return [
						'shortcode' => str_replace(':', '', $emojo->shortcode),
						'url' => $url,
						'static_path' => $url,
						'visible_in_picker' => $emojo->disabled == false
					];
				})
				->when($pgsql, function($collection) {
					return $collection->unique('shortcode');
				})
				->toJson(JSON_UNESCAPED_SLASHES);
		});
	}
}
