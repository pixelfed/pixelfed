<?php

namespace App\Services\Concerns;

use App\Services\StatusService;
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

        return (int) $statusId;
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
