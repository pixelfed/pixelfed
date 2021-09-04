<?php

namespace App\Util\Lexer;

use Illuminate\Support\Str;
use App\Util\ActivityPub\Helpers;

class Bearcap
{
	public static function encode($url, $token)
	{
		return "bear:?t={$token}&u={$url}";
	}

	public static function decode($str)
	{
		if(!Str::startsWith($str, 'bear:')) {
			return false;
		}

		$query = parse_url($str, PHP_URL_QUERY);

		if(!$query) {
			return false;
		}

		$res = [];

		$parts = Str::of($str)->substr(6)->explode('&')->toArray();

		foreach($parts as $part) {
			if(Str::startsWith($part, 't=')) {
				$res['token'] = substr($part, 2);
			}

			if(Str::startsWith($part, 'u=')) {
				$res['url'] = substr($part, 2);
			}
		}

		if( !isset($res['token']) ||
			!isset($res['url'])
		) {
			return false;
		}

		$url = $res['url'];
		if(mb_substr($url, 0, 8) !== 'https://') {
			return false;
		}
		$valid = filter_var($url, FILTER_VALIDATE_URL);
		if(!$valid) {
			return false;
		}
		return $res;
	}
}
