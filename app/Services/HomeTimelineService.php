<?php

namespace App\Services;

use App\Models\Follower;
use App\Models\Status;
use App\Models\UserDomainBlock;
use App\Services\Concerns\SortedSetTimeline;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class HomeTimelineService
{
    use SortedSetTimeline;

    const CACHE_KEY = 'pf:services:timeline:v2:home:';

    const CACHE_TTL = 1209600;

    const FEED_LIMIT = 400;

    const FOLLOWER_FEED_POST_LIMIT = 10;

    /**
     * How long a home feed stays recorded as genuinely empty. Shorter than
     * the instance-wide feeds because a home feed turns non-empty through
     * other people's actions, which may not reach a gated key.
     */
    const EMPTY_WARM_INTERVAL = 900;

    public static function get($id, $start = 0, $stop = 10)
    {
        self::cacheKeyTouch($id);

        return self::setGet(self::CACHE_KEY.$id, $start, $stop);
    }

    public static function getRankedMaxId($id, $start = null, $limit = 10)
    {
        self::cacheKeyTouch($id);

        return self::setRankedMaxId(self::CACHE_KEY.$id, $start, $limit);
    }

    public static function getRankedMaxIdOrRecent($id, $start = null, $limit = 10)
    {
        self::cacheKeyTouch($id);

        return self::setRankedMaxIdOrRecent(self::CACHE_KEY.$id, $start, $limit);
    }

    public static function getRankedMinId($id, $end = null, $limit = 10)
    {
        self::cacheKeyTouch($id);

        return self::setRankedMinId(self::CACHE_KEY.$id, $end, $limit);
    }

    public static function add($id, $val)
    {
        $res = self::setAdd(self::CACHE_KEY.$id, $val, self::FEED_LIMIT);

        self::cacheKeyTouch($id);

        return $res;
    }

    public static function rem($id, $val)
    {
        return self::setRem(self::CACHE_KEY.$id, $val);
    }

    public static function count($id)
    {
        return self::setCount(self::CACHE_KEY.$id);
    }

    public static function statusScore($statusId)
    {
        return self::scoreForStatus($statusId);
    }

    public static function cacheKeyExists($id)
    {
        return (bool) Redis::exists(self::CACHE_KEY.$id);
    }

    public static function cacheKeyTouch($id)
    {
        if (Redis::exists(self::CACHE_KEY.$id)) {
            Redis::expire(self::CACHE_KEY.$id, self::CACHE_TTL);
        }
    }

    public static function shouldWarmEmpty($id): bool
    {
        return self::setShouldWarmEmpty(self::CACHE_KEY.$id);
    }

    public static function markEmptyWarmed($id): void
    {
        self::setMarkEmptyWarmed(self::CACHE_KEY.$id, self::EMPTY_WARM_INTERVAL);
    }

    public static function clearEmptyWarmed($id): void
    {
        self::setClearEmptyWarmed(self::CACHE_KEY.$id);
    }

    /**
     * Rebuild the cached feed, returning the number of members published.
     *
     * Returns 0 both when nothing was published and when the guard declined
     * to run; the read path only ever forces, so the two cannot collide
     * there, and every other caller treats them the same way.
     */
    public static function warmCache($id, bool $force = false, int $limit = 100): int
    {
        if (self::count($id) != 0 && $force != true) {
            return 0;
        }

        $limit = $limit > 0 ? $limit : 100;

        $following = self::followingForWarm($id);
        $domainBlocks = UserDomainBlock::whereProfileId($id)->pluck('domain')->toArray();

        $pass = self::publishWarm($id, self::warmCandidates($following, $limit, true, false), $domainBlocks);
        $unresolved = $pass['unresolved'];

        if ($pass['added'] > 0) {
            self::clearEmptyWarmed($id);

            return $pass['added'];
        }

        // Nothing publishable inside the fast path. Before concluding there is
        // nothing to show at all, drop the two assumptions that path makes for
        // speed — that anything worth showing was posted in the last six
        // months, and that a boost is not content — and re-read a following
        // list that resolved to the viewer alone, which is what a list cached
        // before a follow landed looks like.
        if (self::followingIsSelfOnly($following, $id)) {
            Cache::forget('profile:following:'.$id);
            $following = self::followingForWarm($id);
        }

        $pass = self::publishWarm($id, self::warmCandidates($following, $limit, false, true), $domainBlocks);
        $unresolved += $pass['unresolved'];

        if ($pass['added'] > 0) {
            self::clearEmptyWarmed($id);

            return $pass['added'];
        }

        if ($unresolved > 0) {
            // Candidates were there and could not be resolved, so this warm
            // never established that the feed is empty. Recording it as empty
            // here is how a transient failure becomes a blank timeline the
            // user cannot get out of.
            Log::warning('HomeTimelineService: home warm could not resolve any candidate', [
                'pid' => $id,
                'unresolved' => $unresolved,
            ]);

            return 0;
        }

        self::markEmptyWarmed($id);

        return 0;
    }

    protected static function followingForWarm($id): array
    {
        $following = Cache::remember('profile:following:'.$id, 1209600, function () use ($id) {
            $following = Follower::whereProfileId($id)->pluck('following_id');

            return $following->push($id)->toArray();
        });

        $filters = UserFilterService::filters($id);

        if ($filters && count($filters)) {
            $following = array_diff($following, $filters);
        }

        return array_values($following);
    }

    protected static function followingIsSelfOnly(array $following, $id): bool
    {
        return count($following) === 1 && (int) reset($following) === (int) $id;
    }

    /**
     * The candidate ids for one warm pass.
     *
     * The per-account latest is merged with the newest overall so a prolific
     * account cannot crowd everyone else out of a capped feed.
     */
    protected static function warmCandidates(array $following, int $limit, bool $windowed, bool $includeShares)
    {
        $types = ['photo', 'photo:album', 'video', 'video:album', 'photo:video:album'];
        $nullable = ['in_reply_to_id'];

        if ($includeShares) {
            $types[] = 'share';
        } else {
            $nullable[] = 'reblog_of_id';
        }

        $minId = SnowflakeService::byDate(now()->subMonths(6));

        $baseQuery = function () use ($following, $windowed, $minId, $types, $nullable) {
            return Status::when($windowed, function ($q) use ($minId) {
                return $q->where('id', '>', $minId);
            })
                ->whereIn('profile_id', $following)
                ->whereNull($nullable)
                ->whereIn('type', $types)
                ->whereIn('visibility', ['public', 'unlisted', 'private']);
        };

        $latestPerAccount = $baseQuery()
            ->select(DB::raw('max(id) as id'))
            ->groupBy('profile_id')
            ->pluck('id')
            ->sortDesc()
            ->take($limit)
            ->values();

        $remaining = $limit - $latestPerAccount->count();

        $recentIds = $remaining > 0
            ? $baseQuery()
                ->whereNotIn('id', $latestPerAccount->all())
                ->orderByDesc('id')
                ->limit($remaining)
                ->pluck('id')
            : collect();

        return $latestPerAccount
            ->merge($recentIds)
            ->unique()
            ->values();
    }

    /**
     * Hydrate the candidates into a scratch set and publish it atomically.
     *
     * Returns the number of members published and the number of candidates
     * that could not be resolved at all. The caller needs the two apart: a
     * candidate skipped by a domain block is a decision, while a candidate
     * that would not resolve means the warm did not finish its work.
     */
    protected static function publishWarm($id, $candidates, array $domainBlocks): array
    {
        $tmpKey = self::CACHE_KEY.$id.':rebuild:'.Str::random(8);
        $added = 0;
        $unresolved = 0;

        foreach ($candidates as $sid) {
            try {
                $status = StatusService::get($sid, false);
            } catch (\Throwable $e) {
                $unresolved++;

                continue;
            }

            if (! $status || ! isset($status['account'], $status['url'])) {
                $unresolved++;

                continue;
            }
            if ($domainBlocks && count($domainBlocks)) {
                $domain = parse_url($status['url'], PHP_URL_HOST);
                if (! is_string($domain) || $domain === '') {
                    $unresolved++;

                    continue;
                }
                if (in_array(strtolower($domain), $domainBlocks)) {
                    continue;
                }
            }
            $score = ! empty($status['created_at']) ? strtotime($status['created_at']) : false;
            Redis::zadd($tmpKey, $score !== false ? $score : self::snowflakeScore($sid), $sid);
            $added++;
        }

        if ($added === 0) {
            // The instance-wide feeds clear themselves when a warm finds
            // nothing, because their content is the instance's content. A
            // per-user feed is different: a warm can come up empty because
            // this user's relationships or the statuses behind their feed
            // could not be resolved, and throwing away a feed that was
            // serving content is how a user ends up with a blank timeline.
            Redis::del($tmpKey);

            return ['added' => 0, 'unresolved' => $unresolved];
        }

        if ($added > self::FEED_LIMIT) {
            Redis::zremrangebyrank($tmpKey, 0, -(self::FEED_LIMIT + 1));
        }

        Redis::rename($tmpKey, self::CACHE_KEY.$id);
        Redis::expire(self::CACHE_KEY.$id, self::CACHE_TTL);

        return ['added' => $added, 'unresolved' => $unresolved];
    }
}
