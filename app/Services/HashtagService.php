<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use App\Hashtag;
use App\StatusHashtag;
use App\HashtagFollow;

class HashtagService
{
    const FOLLOW_KEY = 'pf:services:hashtag:following:v1:';
    const FOLLOW_PIDS_KEY = 'pf:services:hashtag-follows:v1:';

    public static function get($id)
    {
        return Cache::remember('services:hashtag:by_id:' . $id, 3600, function() use($id) {
            $tag = Hashtag::find($id);
            if(!$tag) {
                return [];
            }
            return [
                'name' => $tag->name,
                'slug' => $tag->slug,
            ];
        });
    }

    public static function count($id)
    {
        return Cache::remember('services:hashtag:total-count:by_id:' . $id, 300, function() use($id) {
            $tag = Hashtag::find($id);
            return $tag ? $tag->cached_count ?? 0 : 0;
        });
    }

    public static function isFollowing($pid, $hid)
    {
        $res = Redis::zscore(self::FOLLOW_KEY . $hid, $pid);
        if($res) {
            return true;
        }

        $synced = Cache::get(self::FOLLOW_KEY . 'acct:' . $pid . ':synced');
        if(!$synced) {
            $tags = HashtagFollow::whereProfileId($pid)
                ->get()
                ->each(function($tag) use($pid) {
                    self::follow($pid, $tag->hashtag_id);
                });
            Cache::set(self::FOLLOW_KEY . 'acct:' . $pid . ':synced', true, 1209600);

            return (bool) Redis::zscore(self::FOLLOW_KEY . $hid, $pid) >= 1;
        }

        return false;
    }

    public static function follow($pid, $hid)
    {
    	Cache::forget(self::FOLLOW_PIDS_KEY . $hid);
        return Redis::zadd(self::FOLLOW_KEY . $hid, $pid, $pid);
    }

    public static function unfollow($pid, $hid)
    {
    	Cache::forget(self::FOLLOW_PIDS_KEY . $hid);
        return Redis::zrem(self::FOLLOW_KEY . $hid, $pid);
    }

    public static function following($hid, $start = 0, $limit = 10)
    {
        $synced = Cache::get(self::FOLLOW_KEY . 'acct-following:' . $hid . ':synced');
        if(!$synced) {
            $tags = HashtagFollow::whereHashtagId($hid)
                ->get()
                ->each(function($tag) use($hid) {
                    self::follow($tag->profile_id, $hid);
                });
            Cache::set(self::FOLLOW_KEY . 'acct-following:' . $hid . ':synced', true, 1209600);

            return Redis::zrevrange(self::FOLLOW_KEY . $hid, $start, $limit);
        }
        return Redis::zrevrange(self::FOLLOW_KEY . $hid, $start, $limit);
    }
}
