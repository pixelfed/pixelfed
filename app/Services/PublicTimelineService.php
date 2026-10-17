<?php

namespace App\Services;

use App\Models\Status;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class PublicTimelineService
{
    const CACHE_KEY = 'pf:services:timeline:public';

    public static function get($start = 0, $stop = 10)
    {
        if ($stop > 100) {
            $stop = 100;
        }

        return Redis::zrevrange(self::CACHE_KEY, $start, $stop);
    }

    public static function getRankedMaxId($start = null, $limit = 10)
    {
        if (! $start) {
            return [];
        }

        $score = self::cursorScore($start);

        if ($score === null) {
            return [];
        }

        return array_keys(Redis::zrevrangebyscore(self::CACHE_KEY, '('.$score, '-inf', [
            'withscores' => true,
            'limit' => [0, $limit],
        ]));
    }

    public static function getRankedMinId($end = null, $limit = 10)
    {
        if (! $end) {
            return [];
        }

        $score = self::cursorScore($end);

        if ($score === null) {
            return [];
        }

        return array_keys(Redis::zrevrangebyscore(self::CACHE_KEY, '+inf', '('.$score, [
            'withscores' => true,
            'limit' => [0, $limit],
        ]));
    }

    public static function add($val)
    {
        $res = Redis::zadd(self::CACHE_KEY, self::statusScore($val), $val);

        if (self::count() > 400) {
            Redis::zremrangebyrank(self::CACHE_KEY, 0, -401);
        }

        return $res;
    }

    public static function statusScore($statusId)
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

    protected static function cursorScore($statusId)
    {
        $score = Redis::zscore(self::CACHE_KEY, $statusId);

        if ($score !== null && $score !== false) {
            return (int) $score;
        }

        // Web clients parse the 19-digit snowflake cursor through a JS Number,
        // which rounds past Number.MAX_SAFE_INTEGER, so the id they send back
        // never matches a member exactly. Resolve it to the member it rounded
        // from before scoring, otherwise a fabricated score sorts the whole
        // feed back to the top and scroll stalls.
        $member = self::resolveCursorMember($statusId);

        if ($member !== null) {
            $memberScore = Redis::zscore(self::CACHE_KEY, $member);

            if ($memberScore !== null && $memberScore !== false) {
                return (int) $memberScore;
            }
        }

        return self::statusScore($statusId);
    }

    protected static function resolveCursorMember($statusId)
    {
        // The cursor missed an exact member lookup, which happens when a client
        // rounded the id. Match it to the member that collapses to the same
        // float value the client would have produced.
        $target = (float) $statusId;
        $members = Redis::zrange(self::CACHE_KEY, 0, -1);

        foreach ($members as $member) {
            if ((float) $member === $target) {
                return $member;
            }
        }

        return null;
    }

    public static function rem($val)
    {
        return Redis::zrem(self::CACHE_KEY, $val);
    }

    public static function del($val)
    {
        return self::rem($val);
    }

    public static function count()
    {
        return Redis::zcard(self::CACHE_KEY);
    }

    public static function deleteByProfileId($profileId)
    {
        $res = Redis::zrange(self::CACHE_KEY, 0, '-1');
        if (! $res) {
            return;
        }
        foreach ($res as $postId) {
            $s = StatusService::get($postId);
            if (! $s) {
                self::rem($postId);

                continue;
            }

            if (! data_get($s, 'account.id')) {
                self::rem($postId);

                continue;
            }

            if ($s['account']['id'] == $profileId) {
                self::rem($postId);
            }
        }
    }

    public static function warmCache($force = false, $limit = 100)
    {
        if (self::count() == 0 || $force == true) {
            $hideNsfw = config('instance.hide_nsfw_on_public_feeds');
            $minId = SnowflakeService::byDate(now()->subDays(90));
            $rows = Status::where('id', '>', $minId)
                ->whereNull(['uri', 'in_reply_to_id', 'reblog_of_id'])
                ->when($hideNsfw, function ($q, $hideNsfw) {
                    return $q->where('is_nsfw', false);
                })
                ->whereIn('type', ['photo', 'photo:album', 'video', 'video:album', 'photo:video:album'])
                ->whereScope('public')
                ->orderByDesc('id')
                ->limit($limit)
                ->get(['id', 'profile_id', 'created_at']);

            $tmpKey = self::CACHE_KEY.':rebuild:'.Str::random(8);
            $added = 0;
            foreach ($rows as $row) {
                if (AdminShadowFilterService::canAddToPublicFeedByProfileId($row->profile_id)) {
                    $score = $row->created_at ? $row->created_at->timestamp : $row->id;
                    Redis::zadd($tmpKey, $score, $row->id);
                    $added++;
                }
            }

            if ($added === 0) {
                Redis::del(self::CACHE_KEY);

                return 1;
            }

            if ($added > 400) {
                Redis::zremrangebyrank($tmpKey, 0, -401);
            }

            Redis::rename($tmpKey, self::CACHE_KEY);

            return 1;
        }

        return 0;
    }
}
