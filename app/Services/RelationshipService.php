<?php

namespace App\Services;

use App\Follower;
use App\FollowRequest;
use App\UserFilter;
use Illuminate\Support\Facades\Cache;

class RelationshipService
{
    const CACHE_KEY = 'pf:services:urel:';

    public static function get($aid, $tid)
    {
        $actor = AccountService::get($aid, true);
        $target = AccountService::get($tid, true);
        if (! $actor || ! $target) {
            return self::defaultRelation($tid);
        }

        if ($actor['id'] === $target['id']) {
            return self::defaultRelation($tid);
        }

        return Cache::remember(self::key("a_{$aid}:t_{$tid}"), 1209600, function () use ($aid, $tid) {
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
                'muting_notifications' => false,
                'requested' => FollowRequest::whereFollowerId($aid)
                    ->whereFollowingId($tid)
                    ->exists(),
                'domain_blocking' => false,
                'showing_reblogs' => false,
                'endorsed' => false,
            ];
        });
    }

    public static function delete($aid, $tid)
    {
        Cache::forget(self::key("wd:a_{$aid}:t_{$tid}"));

        return Cache::forget(self::key("a_{$aid}:t_{$tid}"));
    }

    public static function refresh($aid, $tid)
    {
        Cache::forget('pf:services:follower:audience:'.$aid);
        Cache::forget('pf:services:follower:audience:'.$tid);
        self::delete($tid, $aid);
        self::delete($aid, $tid);
        self::get($tid, $aid);

        return self::get($aid, $tid);
    }

    public static function forget($aid, $tid)
    {
        Cache::forget('pf:services:follower:audience:'.$aid);
        Cache::forget('pf:services:follower:audience:'.$tid);
        self::delete($tid, $aid);
        self::delete($aid, $tid);
    }

    public static function defaultRelation($tid)
    {
        return [
            'id' => (string) $tid,
            'following' => false,
            'followed_by' => false,
            'blocking' => false,
            'muting' => false,
            'muting_notifications' => false,
            'requested' => false,
            'domain_blocking' => false,
            'showing_reblogs' => false,
            'endorsed' => false,
        ];
    }

    protected static function key($suffix)
    {
        return self::CACHE_KEY.$suffix;
    }

    public static function getWithDate($aid, $tid)
    {
        $res = self::get($aid, $tid);

        if (! $res || ! $res['following']) {
            $res['following_since'] = null;

            return $res;
        }

        return Cache::remember(self::key("wd:a_{$aid}:t_{$tid}"), 1209600, function () use ($aid, $tid, $res) {
            $tmp = Follower::whereProfileId($aid)->whereFollowingId($tid)->first();
            if (! $tmp) {
                $res['following_since'] = null;

                return $res;
            }
            $res['following_since'] = str_replace('+00:00', 'Z', $tmp->created_at->format(DATE_RFC3339_EXTENDED));

            return $res;
        });
    }
}
