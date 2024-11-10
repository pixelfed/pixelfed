<?php

namespace App\Services;

use App\User;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Log;

class PushNotificationService
{
    public const ACTIVE_LIST_KEY = 'pf:services:push-notify:active_deliver:';

    public const NOTIFY_TYPES = ['follow', 'like', 'mention', 'comment'];

    public const DEEP_CHECK_KEY = 'pf:services:push-notify:deep-check:';

    public const PUSH_GATEWAY_VERSION = '1.0';

    public const LOTTERY_ODDS = 20;

    public const CACHE_LOCK_SECONDS = 10;

    public static function get($list)
    {
        return Redis::smembers(self::ACTIVE_LIST_KEY.$list);
    }

    public static function set($listId, $memberId)
    {
        if (! in_array($listId, self::NOTIFY_TYPES)) {
            return false;
        }
        $user = User::whereProfileId($memberId)->first();
        if (! $user || $user->status || $user->deleted_at) {
            return false;
        }

        return Redis::sadd(self::ACTIVE_LIST_KEY.$listId, $memberId);
    }

    public static function check($listId, $memberId)
    {
        return random_int(1, self::LOTTERY_ODDS) === 1
            ? self::isMemberDeepCheck($listId, $memberId)
            : self::isMember($listId, $memberId);
    }

    public static function isMember($listId, $memberId)
    {
        try {
            return Redis::sismember(self::ACTIVE_LIST_KEY.$listId, $memberId);
        } catch (Exception $e) {
            return false;
        }
    }

    public static function isMemberDeepCheck($listId, $memberId)
    {
        $lock = Cache::lock(self::DEEP_CHECK_KEY.$listId, self::CACHE_LOCK_SECONDS);

        try {
            $lock->block(5);
            $actualCount = User::whereNull('status')->where('notify_enabled', true)->where('notify_'.$listId, true)->count();
            $cachedCount = self::count($listId);
            if ($actualCount != $cachedCount) {
                self::warmList($listId);
                $user = User::where('notify_enabled', true)->where('profile_id', $memberId)->first();

                return $user ? (bool) $user->{"notify_{$listId}"} : false;
            } else {
                return self::isMember($listId, $memberId);
            }
        } catch (Exception $e) {
            Log::error('Failed during deep membership check: '.$e->getMessage());

            return false;
        } finally {
            optional($lock)->release();
        }
    }

    public static function removeMember($listId, $memberId)
    {
        return Redis::srem(self::ACTIVE_LIST_KEY.$listId, $memberId);
    }

    public static function removeMemberFromAll($memberId)
    {
        foreach (self::NOTIFY_TYPES as $type) {
            self::removeMember($type, $memberId);
        }

        return 1;
    }

    public static function count($listId)
    {
        if (! in_array($listId, self::NOTIFY_TYPES)) {
            return false;
        }

        return Redis::scard(self::ACTIVE_LIST_KEY.$listId);
    }

    public static function warmList($listId)
    {
        if (! in_array($listId, self::NOTIFY_TYPES)) {
            return false;
        }
        $key = self::ACTIVE_LIST_KEY.$listId;
        Redis::del($key);
        foreach (User::where('notify_'.$listId, true)->cursor() as $acct) {
            if ($acct->status || $acct->deleted_at || ! $acct->profile_id || ! $acct->notify_enabled) {
                continue;
            }
            Redis::sadd($key, $acct->profile_id);
        }

        return self::count($listId);
    }
}
