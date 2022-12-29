<?php

namespace App\Services;

use App\Models\CustomEmoji;
use App\Util\ActivityPub\Helpers;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Client\RequestException;

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

		try {
			$res = Http::acceptJson()->get($url);
		} catch (RequestException $e) {
			return;
		} catch (\Exception $e) {
			return;
		}

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

			$emoji = CustomEmoji::firstOrCreate([
				'shortcode' => $json['name'],
				'domain' => parse_url($json['id'], PHP_URL_HOST)
			], [
				'uri' => $json['id'],
				'image_remote_url' => $json['icon']['url']
			]);

			if($emoji->wasRecentlyCreated == false) {
				if(Storage::exists('public/' . $emoji->media_path)) {
					Storage::delete('public/' . $emoji->media_path);
				}
			}

			$ext = '.' . last(explode('/', $json['icon']['mediaType']));
			$dest = storage_path('app/public/emoji/') . $emoji->id . $ext;
			copy($json['icon']['url'], $dest);
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
		try {
			$res = Http::head($url);
		} catch (RequestException $e) {
			return false;
		} catch (\Exception $e) {
			return false;
		}

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
						'static_url' => $url,
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
