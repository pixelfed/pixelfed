<?php

namespace App\Services;

use Redis;

use App\{
	Follower,
	Profile
};

class FollowerService {

	protected $profile;
	protected $follower_prefix;
	protected $following_prefix;

	public static function build()
	{
		return new self();
	}

	public function profile(Profile $profile)
	{
		$this->profile = $profile;
		$this->follower_prefix = config('cache.prefix').':profile:followers:'.$profile->id;
		$this->following_prefix = config('cache.prefix').':profile:following:'.$profile->id;
		return $this;
	}

	public function followers($limit = 100, $offset = 0)
	{
		if(Redis::llen($this->follower_prefix) == 0) {
			$followers = $this->profile->followers;
			$followers->map(function($i) {
				Redis::lpush($this->follower_prefix, $i->id);
			});
			return $followers;
		} else {
			return Redis::lrange($this->follower_prefix, $offset, $limit);
		}
	}


	public function following($limit = 100, $offset = 0)
	{
		if(Redis::llen($this->following_prefix) == 0) {
			$following = $this->profile->following;
			$following->map(function($i) {
				Redis::lpush($this->following_prefix, $i->id);
			});
			return $following;
		} else {
			return Redis::lrange($this->following_prefix, $offset, $limit);
		}
	}

}