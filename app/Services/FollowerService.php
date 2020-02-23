<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;

use App\{
	Follower,
	Profile
};

class FollowerService {

	protected $profile;
	public static $follower_prefix = 'px:profile:followers-v1.3:';
	public static $following_prefix = 'px:profile:following-v1.3:';

	public static function build()
	{
		return new self();
	}

	public function profile(Profile $profile)
	{
		$this->profile = $profile;
		self::$follower_prefix .= $profile->id;
		self::$following_prefix .= $profile->id;
		return $this;
	}

	public function followers($limit = 100, $offset = 1)
	{
		if(Redis::zcard(self::$follower_prefix) == 0) {
			$followers = $this->profile->followers()->pluck('profile_id');
			$followers->map(function($i) {
				Redis::zadd(self::$follower_prefix, $i, $i);
			});
			return Redis::zrevrange(self::$follower_prefix, $offset, $limit);
		} else {
			return Redis::zrevrange(self::$follower_prefix, $offset, $limit);
		}
	}


	public function following($limit = 100, $offset = 1)
	{
		if(Redis::zcard(self::$following_prefix) == 0) {
			$following = $this->profile->following()->pluck('following_id');
			$following->map(function($i) {
				Redis::zadd(self::$following_prefix, $i, $i);
			});
			return Redis::zrevrange(self::$following_prefix, $offset, $limit);
		} else {
			return Redis::zrevrange(self::$following_prefix, $offset, $limit);
		}
	}

	public static function follows(string $actor, string $target): bool
	{
		$key = self::$follower_prefix . $target;
		if(Redis::zcard($key) == 0) {
			$p = Profile::findOrFail($target);
			self::build()->profile($p)->followers(1);
			self::build()->profile($p)->following(1);
			return (bool) Redis::zrank($key, $actor);
		} else {
			return (bool) Redis::zrank($key, $actor);
		}
	}

}
