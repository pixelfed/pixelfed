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

	public static function scan($text)
	{
		if(config('federation.custom_emoji.enabled') == false) {
			return [];
		}

		return Str::of($text)
		->matchAll(self::SCAN_RE)
		->map(function($match) {
			$tag = Cache::remember(self::CACHE_KEY . $match, 14400, function() use($match) {
				return self::whereShortcode(':' . $match . ':')->first();
			});

			if($tag) {
				$url = url('/storage/' . $tag->media_path);
				return [
					'shortcode' => $match,
					'url' => $url,
					'static_path' => $url,
					'visible_in_picker' => $tag->disabled == false
				];
			}
		})
		->filter(function($tag) {
			return $tag && isset($tag['static_path']);
		})
		->values();
	}
}
