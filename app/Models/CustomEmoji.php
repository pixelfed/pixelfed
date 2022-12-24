<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class CustomEmoji extends Model
{
	use HasFactory;

	const SCAN_RE = "/(?<=[^[:alnum:]:]|\n|^):([a-zA-Z0-9_]{2,}):(?=[^[:alnum:]:]|$)/x";
	const CACHE_KEY = "pf:custom_emoji:";

	protected $guarded = [];

	public static function scan($text, $activitypub = false)
	{
		if(config('federation.custom_emoji.enabled') == false) {
			return [];
		}

		return Str::of($text)
		->matchAll(self::SCAN_RE)
		->map(function($match) use($activitypub) {
			$tag = Cache::remember(self::CACHE_KEY . $match, 14400, function() use($match) {
				return self::orderBy('id')->whereDisabled(false)->whereShortcode(':' . $match . ':')->first();
			});

			if($tag) {
				$url = url('/storage/' . $tag->media_path);

				if($activitypub == true) {
					$mediaType = Str::endsWith($url, '.png') ? 'image/png' : 'image/jpg';
					return [
						'id' => url('emojis/' . $tag->id),
						'type' => 'Emoji',
						'name' => $tag->shortcode,
						'updated' => $tag->updated_at->toAtomString(),
						'icon' => [
							'type' => 'Image',
							'mediaType' => $mediaType,
							'url' => $url
						]
					];
				} else {
					return [
						'shortcode' => $match,
						'url' => $url,
						'static_url' => $url,
						'visible_in_picker' => $tag->disabled == false
					];
				}
			}
		})
		->filter(function($tag) use($activitypub) {
			if($activitypub == true) {
				return $tag && isset($tag['icon']);
			} else {
				return $tag && isset($tag['static_url']);
			}
		})
		->values()
		->toArray();
	}
}
