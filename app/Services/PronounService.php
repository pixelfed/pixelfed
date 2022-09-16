<?php

namespace App\Services;

use Cache;
use App\Models\UserPronoun;
use App\Profile;

class PronounService {

	public static function get($id)
	{
		$key = 'user:pronouns:' . $id;
		$ttl = now()->addHours(12);

		return Cache::remember($key, $ttl, function() use($id) {
			$res = UserPronoun::whereProfileId($id)->first();
			return $res && $res->pronouns ? json_decode($res->pronouns, true) : [];
		});
	}

	public static function put($id, $pronouns)
	{
		$res = UserPronoun::whereProfileId($id)->first();
		$key = 'user:pronouns:' . $id;

		if($res) {
			$res->pronouns = json_encode($pronouns);
			$res->save();
			Cache::forget($key);
			AccountService::del($id);
			return $res->pronouns;
		}

		$res = new UserPronoun;
		$res->profile_id = $id;
		$res->pronouns = json_encode($pronouns);
		$res->save();
		Cache::forget($key);
		AccountService::del($id);
		return $res->pronouns;
	}

	public static function clear($id)
	{
		$res = UserPronoun::whereProfileId($id)->first();
		if($res) {
			$res->pronouns = null;
			$res->save();
		}
		$key = 'user:pronouns:' . $id;
		Cache::forget($key);
		AccountService::del($id);
	}

	public static function pronouns()
	{
		return [
			'co',
			'cos',
			'e',
			'ey',
			'em',
			'eir',
			'fae',
			'faer',
			'he',
			'him',
			'his',
			'her',
			'hers',
			'hir',
			'mer',
			'mers',
			'ne',
			'nir',
			'nirs',
			'nee',
			'ner',
			'ners',
			'per',
			'pers',
			'she',
			'they',
			'them',
			'theirs',
			'thon',
			'thons',
			've',
			'ver',
			'vis',
			'vi',
			'vir',
			'xe',
			'xem',
			'xyr',
			'ze',
			'zir',
			'zie'
		];
	}
}
