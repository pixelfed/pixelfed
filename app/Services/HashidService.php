<?php

namespace App\Services;

use Cache;

class HashidService {

	public const MIN_LIMIT = 15;
	public const CMAP = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_';

	public static function encode($id)
	{
		if(!is_numeric($id) || $id > PHP_INT_MAX || strlen($id) < self::MIN_LIMIT) {
			return null;
		}
		$key = "hashids:{$id}";
		return Cache::remember($key, now()->hours(48), function() use($id) {
			$cmap = self::CMAP;
			$base = strlen($cmap);
			$shortcode = '';
			while($id) {
				$id = ($id - ($r = $id % $base)) / $base;
				$shortcode = $cmap[$r] . $shortcode;
			};
			return $shortcode;
		});
	}

	public static function decode($short)
	{
		$len = strlen($short);
		if($len < 3 || $len > 11) {
			return null;
		}
		$id = 0;
		foreach(str_split($short) as $needle) {
			$pos = strpos(self::CMAP, $needle);
			// if(!$pos) {
			// 	return null;
			// }
			$id = ($id*64) + $pos;
		}
		if(strlen($id) < self::MIN_LIMIT) {
			return null;
		}
		return $id;
	}

}
