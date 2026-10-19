<?php

namespace App\Services\Concerns;

use App\Services\StatusService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

/**
 * Shared sorted-set timeline operations.
 *
 * Each cached timeline is a Redis sorted set scored by created_at. The home
 * feed is keyed per profile; the local and network feeds are instance-wide.
 * The operations are identical once the caller supplies the resolved key, so
 * they live here and each service passes its own key and trim cap.
 */
trait SortedSetTimeline
{
    protected static function setGet(string $key, int $start, int $stop): array
    {
        if ($stop > 100) {
            $stop = 100;
        }

        return Redis::zrevrange($key, $start, $stop) ?: [];
    }

    protected static function setRankedMaxId(string $key, $start, int $limit): array
    {
        if (! $start) {
            return [];
        }

        $score = self::cursorScoreForKey($key, $start);

        if ($score === null) {
            return [];
        }

        return array_keys(Redis::zrevrangebyscore($key, '('.$score, '-inf', [
            'withscores' => true,
            'limit' => [0, $limit],
        ]));
    }

    protected static function setRankedMinId(string $key, $end, int $limit): array
    {
        if (! $end) {
            return [];
        }

        $score = self::cursorScoreForKey($key, $end);

        if ($score === null) {
            return [];
        }

        return array_keys(Redis::zrevrangebyscore($key, '+inf', '('.$score, [
            'withscores' => true,
            'limit' => [0, $limit],
        ]));
    }

    protected static function setAdd(string $key, $val, int $trim)
    {
        $res = Redis::zadd($key, self::scoreForStatus($val), $val);

        if (self::setCount($key) > $trim) {
            Redis::zremrangebyrank($key, 0, -($trim + 1));
        }

        // A write means the feed is no longer settled-empty; let the next read
        // warm again if it still comes back empty.
        self::clearEmptyWarmGuard($key);

        return $res;
    }

    protected static function setRem(string $key, $val)
    {
        self::clearEmptyWarmGuard($key);

        return Redis::zrem($key, $val);
    }

    /**
     * Seconds a feed that warmed to nothing stays settled before another
     * synchronous empty warm is allowed.
     */
    protected static function emptyWarmInterval(): int
    {
        return 3600;
    }

    protected static function emptyWarmGuardKey(string $key): string
    {
        return 'pf:timeline:empty-warm-guard:'.$key;
    }

    /**
     * True when a synchronous empty warm is allowed right now: the guard is
     * unset or its interval has lapsed. Expiry is evaluated against the Carbon
     * clock so it advances deterministically under test.
     */
    protected static function shouldEmptyWarm(string $key): bool
    {
        $until = Cache::get(self::emptyWarmGuardKey($key));

        return $until === null || now()->timestamp >= (int) $until;
    }

    /**
     * Record that the feed warmed to nothing so repeated reads inside the
     * interval do not each trigger another synchronous rebuild.
     */
    protected static function markEmptyWarmed(string $key): void
    {
        $interval = self::emptyWarmInterval();
        Cache::put(
            self::emptyWarmGuardKey($key),
            now()->addSeconds($interval)->timestamp,
            $interval + 60
        );
    }

    protected static function clearEmptyWarmGuard(string $key): void
    {
        Cache::forget(self::emptyWarmGuardKey($key));
    }

    protected static function setCount(string $key)
    {
        return Redis::zcard($key);
    }

    /**
     * Seconds a temporary rebuild key is allowed to linger. A rebuild that
     * crashes before the atomic publish leaves its scratch key self-expiring
     * instead of persisting forever.
     */
    protected static function rebuildKeyTtl(): int
    {
        return 300;
    }

    /**
     * Drop any scratch keys left behind by an earlier interrupted rebuild so a
     * fresh rebuild never races or inherits a stale partial set.
     */
    protected static function purgeRebuildKeys(string $liveKey): void
    {
        $prefix = config('database.redis.options.prefix', '');
        $keys = Redis::keys($liveKey.':rebuild:*');
        foreach ($keys as $key) {
            if ($prefix !== '' && str_starts_with($key, $prefix)) {
                $key = substr($key, strlen($prefix));
            }
            Redis::del($key);
        }
    }

    /**
     * Members present on the live key at the moment a rebuild starts reading
     * its snapshot. Comparing this against the live key at publication reveals
     * writes that landed during the rebuild window.
     */
    protected static function rebuildBaseline(string $liveKey): array
    {
        return Redis::zrange($liveKey, 0, -1) ?: [];
    }

    /**
     * Publish a freshly built rebuild key onto the live key without discarding
     * writes that landed during the rebuild. Additions that appeared after the
     * snapshot are folded in; members removed during the window are not
     * resurrected. The swap itself is a single rename so readers never see a
     * partial set.
     */
    protected static function publishRebuild(string $liveKey, string $tmpKey, array $baseline): void
    {
        $current = Redis::zrange($liveKey, 0, -1, ['withscores' => true]) ?: [];
        if ($current) {
            $baselineLookup = array_flip(array_map('strval', $baseline));
            foreach ($current as $member => $score) {
                // A member on the live key that was not in the baseline is a
                // concurrent addition; carry it into the set being published.
                if (! isset($baselineLookup[(string) $member])) {
                    Redis::zadd($tmpKey, (float) $score, $member);
                }
            }
        }
        $currentLookup = array_flip(array_map('strval', array_keys($current)));
        foreach ($baseline as $member) {
            // A baseline member no longer on the live key was removed during the
            // window; drop it from the snapshot so the publish cannot replay it.
            if (! isset($currentLookup[(string) $member])) {
                Redis::zrem($tmpKey, $member);
            }
        }
        Redis::rename($tmpKey, $liveKey);
    }

    protected static function setDeleteByProfileId(string $key, $profileId): void
    {
        $members = Redis::zrange($key, 0, '-1');

        if (! $members) {
            return;
        }

        foreach ($members as $postId) {
            $status = StatusService::get($postId);

            if (! $status || ! data_get($status, 'account.id')) {
                self::setRem($key, $postId);

                continue;
            }

            if ($status['account']['id'] == $profileId) {
                self::setRem($key, $postId);
            }
        }
    }

    protected static function scoreForStatus($statusId)
    {
        try {
            $status = StatusService::get($statusId, false);

            if ($status && ! empty($status['created_at'])) {
                $ts = strtotime($status['created_at']);
                if ($ts !== false) {
                    return $ts;
                }
            }
        } catch (\Throwable $e) {
        }

        return self::snowflakeScore($statusId);
    }

    protected static function snowflakeScore($statusId): int
    {
        $id = (int) $statusId;

        if ($id <= 0) {
            return 0;
        }

        return (int) floor((($id >> 22) + 1549756800000) / 1000);
    }

    protected static function cursorScoreForKey(string $key, $statusId)
    {
        $score = Redis::zscore($key, $statusId);

        if ($score !== null && $score !== false) {
            return (int) $score;
        }

        // Web clients parse the 19-digit snowflake cursor through a JS Number,
        // which rounds past Number.MAX_SAFE_INTEGER, so the id they send back
        // never matches a member exactly. Resolve it to the member it rounded
        // from before scoring, otherwise a fabricated score sorts the whole
        // feed back to the top and scroll stalls.
        $member = self::resolveCursorMemberForKey($key, $statusId);

        if ($member !== null) {
            $memberScore = Redis::zscore($key, $member);

            if ($memberScore !== null && $memberScore !== false) {
                return (int) $memberScore;
            }
        }

        return self::scoreForStatus($statusId);
    }

    protected static function resolveCursorMemberForKey(string $key, $statusId)
    {
        // The cursor missed an exact member lookup, which happens when a client
        // rounded the id. Match it to the member that collapses to the same
        // float value the client would have produced.
        $target = (float) $statusId;

        foreach (Redis::zrange($key, 0, -1) as $member) {
            if ((float) $member === $target) {
                return $member;
            }
        }

        return null;
    }
}
