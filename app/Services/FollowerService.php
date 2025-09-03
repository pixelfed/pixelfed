<?php

namespace App\Services;

use App\Follower;
use App\Jobs\FollowPipeline\FollowServiceWarmCache;
use App\Profile;
use Cache;
use DB;
use Illuminate\Support\Facades\Redis;

class FollowerService
{
    const CACHE_KEY = 'pf:services:followers:';

    const FOLLOWERS_SYNC_KEY = 'pf:services:followers:sync-followers:';

    const FOLLOWING_SYNC_KEY = 'pf:services:followers:sync-following:';

    const FOLLOWING_KEY = 'pf:services:follow:following:id:';

    const FOLLOWERS_KEY = 'pf:services:follow:followers:id:';

    const FOLLOWERS_LOCAL_KEY = 'pf:services:follow:local-follower-ids:v1:';

    const FOLLOWERS_INTER_KEY = 'pf:services:follow:followers:inter:id:';

    const FOLLOWERS_MUTUALS_KEY = 'pf:services:follow:mutuals:';

    public static function add($actor, $target, $refresh = true)
    {
        $ts = (int) microtime(true);
        if ($refresh) {
            RelationshipService::refresh($actor, $target);
        } else {
            RelationshipService::forget($actor, $target);
        }
        Redis::zadd(self::FOLLOWING_KEY.$actor, $ts, $target);
        Redis::zadd(self::FOLLOWERS_KEY.$target, $ts, $actor);
        Cache::forget('profile:following:'.$actor);
        Cache::forget(self::FOLLOWERS_LOCAL_KEY.$actor);
        Cache::forget(self::FOLLOWERS_LOCAL_KEY.$target);

        Redis::del(self::FOLLOWERS_MUTUALS_KEY.$actor);
        Redis::del(self::FOLLOWERS_MUTUALS_KEY.$target);
    }

    public static function remove($actor, $target, $silent = false)
    {
        Redis::zrem(self::FOLLOWING_KEY.$actor, $target);
        Redis::zrem(self::FOLLOWERS_KEY.$target, $actor);
        Cache::forget(self::FOLLOWERS_LOCAL_KEY.$actor);
        Cache::forget(self::FOLLOWERS_LOCAL_KEY.$target);

        Redis::del(self::FOLLOWERS_MUTUALS_KEY.$actor);
        Redis::del(self::FOLLOWERS_MUTUALS_KEY.$target);

        if ($silent !== true) {
            AccountService::del($actor);
            AccountService::del($target);
            RelationshipService::refresh($actor, $target);
            Cache::forget('profile:following:'.$actor);
        } else {
            RelationshipService::forget($actor, $target);
        }
    }

    public static function followers($id, $start = 0, $stop = 10)
    {
        self::cacheSyncCheck($id, 'followers');

        return Redis::zrevrange(self::FOLLOWERS_KEY.$id, $start, $stop);
    }

    public static function following($id, $start = 0, $stop = 10)
    {
        self::cacheSyncCheck($id, 'following');

        return Redis::zrevrange(self::FOLLOWING_KEY.$id, $start, $stop);
    }

    public static function followersPaginate($id, $page = 1, $limit = 10)
    {
        $start = $page == 1 ? 0 : $page * $limit - $limit;
        $end = $start + ($limit - 1);

        return self::followers($id, $start, $end);
    }

    public static function followingPaginate($id, $page = 1, $limit = 10)
    {
        $start = $page == 1 ? 0 : $page * $limit - $limit;
        $end = $start + ($limit - 1);

        return self::following($id, $start, $end);
    }

    public static function followerCount($id, $warmCache = true)
    {
        if ($warmCache) {
            self::cacheSyncCheck($id, 'followers');
        }

        return Redis::zCard(self::FOLLOWERS_KEY.$id);
    }

    public static function followingCount($id, $warmCache = true)
    {
        if ($warmCache) {
            self::cacheSyncCheck($id, 'following');
        }

        return Redis::zCard(self::FOLLOWING_KEY.$id);
    }

    public static function follows(string $actor, string $target, $quickCheck = false)
    {
        if ($actor == $target) {
            return false;
        }

        if ($quickCheck) {
            return (bool) Redis::zScore(self::FOLLOWERS_KEY.$target, $actor);
        }

        if (self::followerCount($target, false) && self::followingCount($actor, false)) {
            self::cacheSyncCheck($target, 'followers');

            return (bool) Redis::zScore(self::FOLLOWERS_KEY.$target, $actor);
        } else {
            self::cacheSyncCheck($target, 'followers');
            self::cacheSyncCheck($actor, 'following');

            return Follower::whereProfileId($actor)->whereFollowingId($target)->exists();
        }
    }

    public static function cacheSyncCheck($id, $scope = 'followers')
    {
        if ($scope === 'followers') {
            if (Cache::get(self::FOLLOWERS_SYNC_KEY.$id) != null) {
                return;
            }
            FollowServiceWarmCache::dispatch($id)->onQueue('low');
        }
        if ($scope === 'following') {
            if (Cache::get(self::FOLLOWING_SYNC_KEY.$id) != null) {
                return;
            }
            FollowServiceWarmCache::dispatch($id)->onQueue('low');
        }

    }

    public static function audience($profile, $scope = null)
    {
        return (new self)->getAudienceInboxes($profile, $scope);
    }

    public static function softwareAudience($profile, $software = 'pixelfed')
    {
        return collect(self::audience($profile))
            ->filter(function ($inbox) use ($software) {
                $domain = parse_url($inbox, PHP_URL_HOST);
                if (! $domain) {
                    return false;
                }

                return InstanceService::software($domain) === strtolower($software);
            })
            ->unique()
            ->values()
            ->toArray();
    }

    protected function getAudienceInboxes($pid, $scope = null)
    {
        $key = 'pf:services:follower:audience:'.$pid;
        $bannedDomains = InstanceService::getBannedDomains();
        $domains = Cache::remember($key, 432000, function () use ($pid, $bannedDomains) {
            $profile = Profile::whereNull(['status', 'domain'])->find($pid);
            if (! $profile) {
                return [];
            }

            return DB::table('followers')
                ->join('profiles', 'followers.profile_id', '=', 'profiles.id')
                ->where('followers.following_id', $pid)
                ->whereNotNull('profiles.inbox_url')
                ->whereNull('profiles.deleted_at')
                ->select('followers.profile_id', 'followers.following_id', 'profiles.id', 'profiles.user_id', 'profiles.deleted_at', 'profiles.sharedInbox', 'profiles.inbox_url')
                ->get()
                ->map(function ($r) {
                    return $r->sharedInbox ?? $r->inbox_url;
                })
                ->filter(function ($r) use ($bannedDomains) {
                    $domain = parse_url($r, PHP_URL_HOST);

                    return $r && ! in_array($domain, $bannedDomains);
                })
                ->unique()
                ->values();
        });

        if (! $domains || ! $domains->count()) {
            return [];
        }

        $banned = InstanceService::getBannedDomains();

        if (! $banned || count($banned) === 0) {
            return $domains->toArray();
        }

        $res = $domains->filter(function ($domain) use ($banned) {
            $parsed = parse_url($domain, PHP_URL_HOST);

            return ! in_array($parsed, $banned);
        })
            ->values()
            ->toArray();

        return $res;
    }

    public static function mutualCount($pid, $mid)
    {
        return Cache::remember(self::CACHE_KEY.':mutualcount:'.$pid.':'.$mid, 3600, function () use ($pid, $mid) {
            return DB::table('followers as u')
                ->join('followers as s', 'u.following_id', '=', 's.following_id')
                ->where('s.profile_id', $mid)
                ->where('u.profile_id', $pid)
                ->count();
        });
    }

    public static function mutualIds($pid, $mid, $limit = 3)
    {
        $key = self::CACHE_KEY.':mutualids:'.$pid.':'.$mid.':limit_'.$limit;

        return Cache::remember($key, 3600, function () use ($pid, $mid, $limit) {
            return DB::table('followers as u')
                ->join('followers as s', 'u.following_id', '=', 's.following_id')
                ->where('s.profile_id', $mid)
                ->where('u.profile_id', $pid)
                ->limit($limit)
                ->pluck('s.following_id')
                ->toArray();
        });
    }

    public static function mutualAccounts($actorId, $profileId)
    {
        if ($actorId == $profileId) {
            return [];
        }
        $actorKey = self::FOLLOWING_KEY.$actorId;
        $profileKey = self::FOLLOWERS_KEY.$profileId;
        $key = self::FOLLOWERS_INTER_KEY.$actorId.':'.$profileId;
        $res = Redis::zinterstore($key, [$actorKey, $profileKey]);
        if ($res) {
            return Redis::zrange($key, 0, -1);
        } else {
            return [];
        }
    }

    /**
     * Get mutual followers for DM suggestions using Redis set intersection
     * This is extremely fast as it operates entirely in Redis memory
     *
     * @param  int  $profileId
     * @param  int  $limit
     * @param  int|null  $cursor
     * @return array
     */
    public static function getMutualsForDM($profileId, $limit = 20, $cursor = null)
    {
        $acct = AccountService::get($profileId, true);
        if (! $acct || ! isset($acct['id'])) {
            return [
                'data' => [],
                'has_more' => false,
                'next_cursor' => null,
                'total_count' => 0,
            ];
        }

        self::cacheSyncCheck($profileId, 'followers');
        self::cacheSyncCheck($profileId, 'following');

        $followingKey = self::FOLLOWING_KEY.$profileId;
        $followersKey = self::FOLLOWERS_KEY.$profileId;
        $mutualsKey = self::FOLLOWERS_MUTUALS_KEY.$profileId;

        $ttl = Redis::ttl($mutualsKey);
        if ($ttl === -2 || $ttl < 300) {
            Redis::zinterstore($mutualsKey, [$followingKey, $followersKey]);
            Redis::expire($mutualsKey, 7200);
        }

        $start = 0;

        if ($cursor) {
            $cursorRank = Redis::zrank($mutualsKey, $cursor);
            if ($cursorRank !== false) {
                $start = $cursorRank + 1;
            }
        }

        $mutuals = Redis::zrange($mutualsKey, $start, $start + $limit);

        $hasMore = count($mutuals) > $limit;
        if ($hasMore) {
            $mutuals = array_slice($mutuals, 0, $limit);
        }

        $nextCursor = $hasMore && ! empty($mutuals) ? end($mutuals) : null;

        return [
            'data' => array_map('intval', $mutuals),
            'has_more' => $hasMore,
            'next_cursor' => $nextCursor ? (int) $nextCursor : null,
            'total_count' => Redis::zcard($mutualsKey),
        ];
    }

    /**
     * Get mutual followers with profile data for DM suggestions
     * Combines Redis efficiency with profile information
     *
     * @param  int  $profileId
     * @param  int  $limit
     * @param  int|null  $cursor
     * @return array
     */
    public static function getMutualsWithProfiles($profileId, $limit = 20, $cursor = null)
    {
        $mutuals = self::getMutualsForDM($profileId, $limit, $cursor);

        if (empty($mutuals['data'])) {
            return $mutuals;
        }

        $orderedProfiles = [];
        foreach ($mutuals['data'] as $mutualId) {
            $acct = AccountService::get($mutualId, true);
            if ($acct && isset($acct['id'])) {
                $orderedProfiles[] = $acct;
            }
        }

        return [
            'data' => $orderedProfiles,
            'has_more' => $mutuals['has_more'],
            'next_cursor' => $mutuals['next_cursor'],
            'total_count' => $mutuals['total_count'],
        ];
    }

    /**
     * Get mutual count efficiently using Redis intersection
     *
     * @param  int  $profileId
     * @return int
     */
    public static function getMutualCount($profileId)
    {
        self::cacheSyncCheck($profileId, 'followers');
        self::cacheSyncCheck($profileId, 'following');

        $followingKey = self::FOLLOWING_KEY.$profileId;
        $followersKey = self::FOLLOWERS_KEY.$profileId;
        $mutualsKey = self::FOLLOWERS_MUTUALS_KEY.$profileId;

        $ttl = Redis::ttl($mutualsKey);
        if ($ttl === -2 || $ttl < 300) {
            Redis::zinterstore($mutualsKey, [$followingKey, $followersKey]);
            Redis::expire($mutualsKey, 7200);
        }

        return Redis::zcard($mutualsKey);
    }

    public static function delCache($id)
    {
        Redis::del(self::CACHE_KEY.$id);
        Redis::del(self::FOLLOWING_KEY.$id);
        Redis::del(self::FOLLOWERS_KEY.$id);
        Redis::del(self::FOLLOWERS_MUTUALS_KEY.$id);
        Cache::forget(self::FOLLOWERS_SYNC_KEY.$id);
        Cache::forget(self::FOLLOWING_SYNC_KEY.$id);
    }

    public static function localFollowerIds($pid, $limit = 0)
    {
        $key = self::FOLLOWERS_LOCAL_KEY.$pid;
        $res = Cache::remember($key, 7200, function () use ($pid) {
            return DB::table('followers')
                ->join('profiles', 'followers.profile_id', '=', 'profiles.id')
                ->where('followers.following_id', $pid)
                ->whereNotNull('profiles.user_id')
                ->whereNull('profiles.deleted_at')
                ->select('followers.profile_id', 'followers.following_id', 'profiles.id', 'profiles.user_id', 'profiles.deleted_at')
                ->pluck('followers.profile_id');
        });

        return $limit ?
            $res->take($limit)->values()->toArray() :
            $res->values()->toArray();
    }
}
