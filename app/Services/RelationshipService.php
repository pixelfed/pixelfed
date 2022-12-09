<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use App\Follower;
use App\FollowRequest;
use App\Profile;
use App\UserFilter;

class RelationshipService
{
	const CACHE_KEY = 'pf:services:urel:';

	public static function get($aid, $tid)
	{
		$actor = AccountService::get($aid, true);
		$target = AccountService::get($tid, true);
		if(!$actor || !$target) {
			return self::defaultRelation($tid);
		}

		if($actor['id'] === $target['id']) {
			return self::defaultRelation($tid);
		}

		return Cache::remember(self::key("a_{$aid}:t_{$tid}"), 1209600, function() use($aid, $tid) {
			return [
				'id' => (string) $tid,
				'following' => Follower::whereProfileId($aid)->whereFollowingId($tid)->exists(),
				'followed_by' => Follower::whereProfileId($tid)->whereFollowingId($aid)->exists(),
				'blocking' => UserFilter::whereUserId($aid)
					->whereFilterableType('App\Profile')
					->whereFilterableId($tid)
					->whereFilterType('block')
					->exists(),
				'muting' => UserFilter::whereUserId($aid)
					->whereFilterableType('App\Profile')
					->whereFilterableId($tid)
					->whereFilterType('mute')
					->exists(),
				'muting_notifications' => null,
				'requested' => FollowRequest::whereFollowerId($aid)
					->whereFollowingId($tid)
					->exists(),
				'domain_blocking' => null,
				'showing_reblogs' => null,
				'endorsed' => false
			];
		});
	}

	public static function delete($aid, $tid)
	{
		return Cache::forget(self::key("a_{$aid}:t_{$tid}"));
	}

	public static function refresh($aid, $tid)
	{
		Cache::forget('pf:services:follow:audience:' . $aid);
		Cache::forget('pf:services:follow:audience:' . $tid);
		self::delete($tid, $aid);
		self::delete($aid, $tid);
		self::get($tid, $aid);
		return self::get($aid, $tid);
	}

	public static function defaultRelation($tid)
	{
		return [
            'id' => (string) $tid,
            'following' => false,
            'followed_by' => false,
            'blocking' => false,
            'muting' => false,
            'muting_notifications' => null,
            'requested' => false,
            'domain_blocking' => null,
            'showing_reblogs' => null,
            'endorsed' => false
        ];
	}

	protected static function key($suffix)
	{
		return self::CACHE_KEY . $suffix;
	}
}
