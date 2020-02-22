<?php

namespace App\Services;

use Cache;
use Illuminate\Support\Facades\Redis;

use App\Follower;
use App\Profile;

class ProfileService
{
    protected $profile;
    protected $profile_prefix;

    public static function build()
    {
        return new self();
    }

    public function profile(Profile $profile)
    {
        $this->profile = $profile;
        $this->profile_prefix = 'profile:model:'.$profile->id;
        return $this;
    }

    public function profileId($id)
    {
        return Cache::rememberForever('profile:model:'.$id, function () use ($id) {
            return Profile::findOrFail($id);
        });
    }

    public function get()
    {
        return Cache::rememberForever($this->profile_prefix, function () {
            return $this->profile;
        });
    }
}
