<?php

namespace App\Services;

use App\MediaTag;
use App\Notification;
use Cache;
use Illuminate\Support\Facades\Redis;

class MediaTagService
{
    const CACHE_KEY = 'pf:services:media_tags:id:';

    public static function get($mediaId, $usernames = true)
    {
        return self::coldGet($mediaId, $usernames);
    }

    public static function coldGet($mediaId, $usernames = true)
    {
        $k = 'pf:services:media_tags:get:sid:'.$mediaId;

        return Cache::remember($k, now()->addMinutes(60), function () use ($mediaId, $usernames) {
            $key = self::CACHE_KEY.$mediaId;
            if (Redis::zCount($key, '-inf', '+inf') == 0) {
                $tags = MediaTag::whereStatusId($mediaId)->get();
                if ($tags->count() == 0) {
                    return [];
                }

                foreach ($tags as $t) {
                    self::set($mediaId, $t->profile_id);
                }
            }
            $res = Redis::zRange($key, 0, -1);
            if (! $usernames) {
                return $res;
            }
            $usernames = [];
            foreach ($res as $k) {
                $username = (new self)->idToUsername($k);
                array_push($usernames, $username);
            }

            return $usernames;
        });
    }

    public static function set($mediaId, $profileId)
    {
        $key = self::CACHE_KEY.$mediaId;
        Redis::zAdd($key, $profileId, $profileId);

        return true;
    }

    protected function idToUsername($id)
    {
        $profile = ProfileService::get($id, true);

        if (! $profile) {
            return 'unavailable';
        }

        return [
            'id' => (string) $id,
            'username' => $profile['username'],
            'avatar' => $profile['avatar'],
        ];
    }

    public static function sendNotification(MediaTag $tag)
    {
        $p = $tag->status->profile;
        $actor = $p->username;

        $n = new Notification;
        $n->profile_id = $tag->profile_id;
        $n->actor_id = $p->id;
        $n->item_id = $tag->id;
        $n->item_type = 'App\MediaTag';
        $n->action = 'tagged';
        $n->save();

    }

    public static function untag($statusId, $profileId)
    {
        MediaTag::whereStatusId($statusId)
            ->whereProfileId($profileId)
            ->delete();
        $key = 'pf:services:media_tags:get:sid:'.$statusId;
        Redis::zRem(self::CACHE_KEY.$statusId, $profileId);
        Cache::forget($key);

        return true;
    }
}
