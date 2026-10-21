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

    protected static function setRankedMaxIdOrRecent(string $key, $start, int $limit): array
    {
        $ids = self::setRankedMaxId($key, $start, $limit);

        if ($ids || ! $start) {
            return $ids;
        }

        $oldest = Redis::zrange($key, 0, 0);

        if (! $oldest) {
            return $ids;
        }

        $oldestScore = Redis::zscore($key, $oldest[0]);
        $cursorScore = self::cursorScoreForKey($key, $start);

        if ($cursorScore === null || $oldestScore === null || $oldestScore === false) {
            return $ids;
        }

        if ((int) $cursorScore >= (int) $oldestScore) {
            // Nothing older than the cursor, which is the end of the feed.
            return $ids;
        }

        // The cursor sits below everything the feed still holds, so the window
        // it pointed into has been trimmed away. Mastodon-API clients and the
        // mobile app persist their cursor, so answering with an empty page
        // means they send the same dead value forever and never see the feed
        // again. The newest window is the only answer that recovers.
        return self::setGet($key, 0, $limit);
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

        // Anything written to a live feed contradicts "a warm found nothing",
        // so the guard goes whatever path the write came from. Removal is not
        // the counterpart: a feed can only get emptier that way, and re-arming
        // the warm on the read path's prune would warm on every read.
        self::setClearEmptyWarmed($key);

        return $res;
    }

    protected static function setRem(string $key, $val)
    {
        return Redis::zrem($key, $val);
    }

    protected static function setCount(string $key)
    {
        return Redis::zcard($key);
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

    /**
     * A feed that a completed warm found empty has no Redis key, which is
     * indistinguishable from a feed that was never built. These three record
     * the difference so repeated reads of a genuinely empty feed stop
     * re-running the candidate query.
     *
     * The guard holds the timestamp at which it lapses and is stored with a
     * matching cache TTL, so an absent, expired or unreadable guard always
     * means "warm it", never "stay empty".
     */
    protected static function setShouldWarmEmpty(string $key): bool
    {
        $lapsesAt = Cache::get(self::emptyWarmGuardKey($key));

        return ! $lapsesAt || (int) $lapsesAt <= now()->timestamp;
    }

    protected static function setMarkEmptyWarmed(string $key, int $interval): void
    {
        Cache::put(
            self::emptyWarmGuardKey($key),
            now()->addSeconds($interval)->timestamp,
            $interval + 60
        );
    }

    protected static function setClearEmptyWarmed(string $key): void
    {
        Cache::forget(self::emptyWarmGuardKey($key));
    }

    protected static function emptyWarmGuardKey(string $key): string
    {
        return 'pf:timeline:empty-warm-guard:'.$key;
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
