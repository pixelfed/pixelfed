<?php

namespace App\Services;

use App\Jobs\InternalPipeline\NotificationEpochUpdatePipeline;
use App\Models\Notification;
use App\Models\Status;
use App\Transformer\Api\NotificationTransformer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class NotificationService
{
    const CACHE_KEY = 'pf:services:notifications:ids:';

    const WARM_KEY = 'pf:services:notifications:warmed:';

    const EPOCH_CACHE_KEY = 'pf:services:notifications:epoch-id:by-months:';

    const ITEM_KEY = 'service:notification:';

    const MISS_KEY = 'service:notification:miss:';

    const ITEM_CACHE_TTL = 86400;

    const MISS_CACHE_TTL = 600;

    const WARM_TTL = 604800;

    const MAX_ITEMS = 400;

    /** Max zset/db rounds a single page request may walk before giving up. */
    const PAGE_SCAN_ROUNDS = 5;

    /** Deeper walk when the caller filters by type (e.g. follows only). */
    const TYPED_SCAN_ROUNDS = 15;

    const MASTODON_TYPES = [
        'follow',
        'follow_request',
        'mention',
        'reblog',
        'favourite',
        'poll',
        'status',
    ];

    /** Mastodon types that are meaningless without an attached status. */
    const MASTODON_STATUS_TYPES = [
        'mention',
        'reblog',
        'favourite',
        'poll',
        'status',
    ];

    /**
     * Fetch notifications by rank (offset/limit), newest first.
     *
     * Previously passed $start/$stop to ZRANGEBYSCORE as scores, which never
     * matched snowflake ids and forced a DB hit on every call.
     */
    public static function get($id, $start = 0, $stop = 400)
    {
        $res = collect([]);
        $stop = min((int) $stop, self::MAX_ITEMS);
        $start = max((int) $start, 0);

        self::warmCache($id);

        $ids = Redis::zrevrange(self::CACHE_KEY.$id, $start, $start + $stop - 1);

        if (empty($ids)) {
            $ids = self::coldGet($id, $start, $stop);
        }

        foreach ($ids as $nid) {
            $n = self::getNotification($nid, $id);
            if ($n != null) {
                $res->push($n);
            }
        }

        return $res;
    }

    public static function coldGet($id, $start = 0, $stop = 400)
    {
        $stop = min((int) $stop, self::MAX_ITEMS);
        $ids = Notification::where('id', '>', self::getEpochId())
            ->where('profile_id', $id)
            ->orderByDesc('id')
            ->skip($start)
            ->take($stop)
            ->pluck('id');

        if ($ids->count()) {
            self::addMany($id, $ids->all());
        }

        return $ids;
    }

    public static function getEpochId($months = 6)
    {
        $epoch = Cache::get(self::EPOCH_CACHE_KEY.$months);
        if (! $epoch) {
            NotificationEpochUpdatePipeline::dispatch();

            return 1;
        }

        return $epoch;
    }

    public static function getMax($id = false, $start = 0, $limit = 10)
    {
        return self::getMaxPage($id, $start, $limit)['data'];
    }

    public static function getMin($id = false, $start = 0, $limit = 10)
    {
        return self::getMinPage($id, $start, $limit)['data'];
    }

    public static function getMaxMastodon($id = false, $start = 0, $limit = 10)
    {
        return self::getMaxMastodonPage($id, $start, $limit)['data'];
    }

    public static function getMinMastodon($id = false, $start = 0, $limit = 10)
    {
        return self::getMinMastodonPage($id, $start, $limit)['data'];
    }

    /**
     * Page variants return the ids actually scanned so the controller can build
     * Link headers that do not stall on a run of null/filtered notifications.
     *
     * @return array{data: array, next_max_id: int|null, prev_min_id: int|null}
     */
    public static function getMaxPage($id = false, $maxId = null, $limit = 10)
    {
        return self::fetchPage($id, $maxId, 'max', $limit);
    }

    public static function getMinPage($id = false, $minId = null, $limit = 10)
    {
        return self::fetchPage($id, $minId, 'min', $limit);
    }

    /**
     * @param  array|null  $types  Optional Mastodon type whitelist (mention, reblog, follow, favourite...).
     *                             Filtering happens inside the walk, so sparse types still fill a page.
     */
    public static function getMaxMastodonPage($id = false, $maxId = null, $limit = 10, ?array $types = null)
    {
        return self::fetchPage($id, $maxId, 'max', $limit, self::mastodonFilter($types), $types ? self::TYPED_SCAN_ROUNDS : self::PAGE_SCAN_ROUNDS);
    }

    public static function getMinMastodonPage($id = false, $minId = null, $limit = 10, ?array $types = null)
    {
        return self::fetchPage($id, $minId, 'min', $limit, self::mastodonFilter($types), $types ? self::TYPED_SCAN_ROUNDS : self::PAGE_SCAN_ROUNDS);
    }

    protected static function mastodonFilter(?array $types): callable
    {
        return function ($n) use ($types) {
            $n = self::toMastodon($n);

            if (! $n) {
                return null;
            }

            if ($types && ! in_array($n['type'], $types)) {
                return null;
            }

            return $n;
        };
    }

    /**
     * Walk the id zset (and the DB when the zset runs short) until we have
     * $limit renderable notifications or nothing is left.
     */
    protected static function fetchPage($id, $cursor, string $direction, int $limit, ?callable $filter = null, ?int $scanRounds = null): array
    {
        $maxRounds = $scanRounds ?? self::PAGE_SCAN_ROUNDS;
        $empty = ['data' => [], 'next_max_id' => null, 'prev_min_id' => null];

        if (! $id || ! $cursor) {
            return $empty;
        }

        $limit = max(1, min((int) $limit, 80));
        $cursor = (int) $cursor;
        $key = self::CACHE_KEY.$id;

        self::warmCache($id);

        $items = [];
        $firstScanned = null;
        $lastScanned = null;
        $exhausted = false;
        $rounds = 0;

        while (count($items) < $limit && ! $exhausted && $rounds < $maxRounds) {
            $rounds++;
            $want = (($limit - count($items)) * 2);

            // Exclusive upper bound: the cursor on the first round, then the last id we looked at.
            $upper = $lastScanned !== null ? '('.$lastScanned : ($direction === 'max' ? '('.$cursor : '+inf');
            $lower = $direction === 'max' ? '-inf' : '('.$cursor;

            $ids = Redis::zrevrangebyscore($key, $upper, $lower, ['limit' => [0, $want]]);
            $ids = array_map('intval', $ids ?: []);

            if (count($ids) < $want) {
                $bound = count($ids) ? end($ids) : $lastScanned;
                $dbIds = self::dbIds($id, $direction, $cursor, $bound, $want - count($ids));

                if (count($dbIds)) {
                    if (self::count($id) < self::MAX_ITEMS) {
                        self::addMany($id, $dbIds);
                    }
                    $ids = array_merge($ids, $dbIds);
                }

                if (count($ids) < $want) {
                    $exhausted = true;
                }
            }

            if (empty($ids)) {
                break;
            }

            foreach ($ids as $nid) {
                $firstScanned = $firstScanned ?? $nid;
                $lastScanned = $nid;

                $n = self::getNotification($nid, $id);
                if (! $n) {
                    continue;
                }

                if ($filter) {
                    $n = $filter($n);
                    if (! $n) {
                        continue;
                    }
                }

                $items[] = $n;

                if (count($items) >= $limit) {
                    break;
                }
            }
        }

        return [
            'data' => $items,
            'next_max_id' => $lastScanned,
            'prev_min_id' => $firstScanned,
        ];
    }

    /**
     * DB fallback for ids the zset does not hold (partial set after eviction,
     * or the user scrolled past the MAX_ITEMS window).
     */
    protected static function dbIds($id, string $direction, int $cursor, ?int $bound, int $limit): array
    {
        $q = Notification::where('profile_id', $id)
            ->where('id', '>', self::getEpochId())
            ->orderByDesc('id')
            ->limit($limit);

        if ($direction === 'max') {
            $q->where('id', '<', $bound !== null ? min($bound, $cursor) : $cursor);
        } else {
            $q->where('id', '>', $cursor);
            if ($bound !== null) {
                $q->where('id', '<', $bound);
            }
        }

        return $q->pluck('id')->map(fn ($v) => (int) $v)->all();
    }

    /**
     * Kept for callers outside this service. Now uses exclusive bounds instead
     * of a fixed offset, so a missing max_id no longer skips a real result.
     */
    public static function getRankedMaxId($id = false, $start = null, $limit = 10)
    {
        if (! $start || ! $id) {
            return [];
        }

        return array_map('intval', Redis::zrevrangebyscore(self::CACHE_KEY.$id, '('.$start, '-inf', [
            'limit' => [0, $limit],
        ]) ?: []);
    }

    public static function getRankedMinId($id = false, $end = null, $limit = 10)
    {
        if (! $end || ! $id) {
            return [];
        }

        return array_map('intval', Redis::zrevrangebyscore(self::CACHE_KEY.$id, '+inf', '('.$end, [
            'limit' => [0, $limit],
        ]) ?: []);
    }

    public static function rewriteMastodonTypes($notification)
    {
        if (! $notification || ! isset($notification['type'])) {
            return $notification;
        }

        if ($notification['type'] === 'comment') {
            $notification['type'] = 'mention';
        }

        if ($notification['type'] === 'share') {
            $notification['type'] = 'reblog';
        }

        if ($notification['type'] === 'tagged') {
            $notification['type'] = 'mention';
        }

        return $notification;
    }

    /**
     * Convert a Pixelfed notification into a Mastodon shaped one.
     * Returns null when the notification should be dropped from the page.
     */
    public static function toMastodon($n)
    {
        $n = self::rewriteMastodonTypes($n);

        if (! $n || ! in_array($n['type'], self::MASTODON_TYPES)) {
            return null;
        }

        if (isset($n['account'])) {
            $account = AccountService::getMastodon($n['account']['id']);
            if (! $account) {
                return null;
            }
            $n['account'] = $account;
        }

        unset($n['relationship']);

        if ($n['type'] === 'mention' && isset($n['tagged'], $n['tagged']['status_id'])) {
            $n['status'] = StatusService::getMastodon($n['tagged']['status_id'], false);
            unset($n['tagged']);
        } elseif (isset($n['status']['id'])) {
            $n['status'] = StatusService::getMastodon($n['status']['id'], false);
        }

        if (in_array($n['type'], self::MASTODON_STATUS_TYPES) && empty($n['status'])) {
            return null;
        }

        return $n;
    }

    public static function set($id, $val)
    {
        $key = self::CACHE_KEY.$id;

        // The set is gone (eviction, restart, failover). Rebuild from the DB
        // rather than creating a 1-item set that warmCache would then trust.
        if (! Redis::exists($key)) {
            self::warmCache($id, self::MAX_ITEMS, true);
        }

        self::addRaw($key, $val);
        self::trim($key);

        return 1;
    }

    public static function del($id, $val)
    {
        Cache::forget(self::ITEM_KEY.$val);
        Cache::forget(self::MISS_KEY.$val);

        return Redis::zrem(self::CACHE_KEY.$id, $val);
    }

    public static function add($id, $val)
    {
        return self::set($id, $val);
    }

    public static function rem($id, $val)
    {
        return self::del($id, $val);
    }

    public static function count($id)
    {
        return (int) Redis::zcard(self::CACHE_KEY.$id);
    }

    protected static function addRaw(string $key, $val): void
    {
        Redis::zadd($key, $val, $val);
    }

    protected static function addMany($id, array $ids): void
    {
        if (empty($ids)) {
            return;
        }

        $key = self::CACHE_KEY.$id;

        Redis::pipeline(function ($pipe) use ($key, $ids) {
            foreach ($ids as $nid) {
                $pipe->zadd($key, $nid, $nid);
            }
        });

        self::trim($key);
    }

    /** Keep only the newest MAX_ITEMS members. */
    protected static function trim(string $key): void
    {
        Redis::zremrangebyrank($key, 0, -(self::MAX_ITEMS + 1));
    }

    public static function isWarm($id): bool
    {
        return (bool) Redis::exists(self::WARM_KEY.$id);
    }

    /**
     * Rebuild the id zset from the DB.
     *
     * Runs when the set is empty, when it has never been marked warm (or the
     * marker expired), or when forced. The marker lives in Redis so it shares
     * the fate of the zset on a flush.
     */
    public static function warmCache($id, $stop = 400, $force = false)
    {
        if (! $force && self::count($id) > 0 && self::isWarm($id)) {
            return 0;
        }

        $stop = min((int) $stop, self::MAX_ITEMS);

        $ids = Notification::where('profile_id', $id)
            ->where('id', '>', self::getEpochId())
            ->orderByDesc('id')
            ->limit($stop)
            ->pluck('id')
            ->all();

        self::addMany($id, $ids);

        Redis::set(self::WARM_KEY.$id, 1, 'EX', self::WARM_TTL);

        return 1;
    }

    /**
     * Fetch a single transformed notification.
     *
     * Returns null for anything that cannot be rendered (missing row, deleted
     * actor, deleted status, transformer failure). Misses are negatively
     * cached briefly, and when $profileId is supplied the dead id is pruned
     * from that profile's zset so future pages stop tripping over it.
     */
    public static function getNotification($id, $profileId = null)
    {
        if (Cache::has(self::MISS_KEY.$id)) {
            return null;
        }

        $notification = Cache::get(self::ITEM_KEY.$id);

        if (! $notification) {
            $notification = self::buildNotification($id);

            if (! $notification) {
                self::markMiss($id, $profileId);

                return null;
            }

            Cache::put(self::ITEM_KEY.$id, $notification, self::ITEM_CACHE_TTL);
        }

        if (isset($notification['account']['id'])) {
            $account = AccountService::get($notification['account']['id'], true);

            if (! $account) {
                self::markMiss($id, $profileId);

                return null;
            }

            $notification['account'] = $account;
        }

        return $notification;
    }

    protected static function buildNotification($id)
    {
        try {
            $n = Notification::with('item')->find($id);

            if (! $n) {
                return null;
            }

            if ($n->item_id && $n->item_type === Status::class && ! $n->item) {
                return null;
            }

            if (! AccountService::get($n->actor_id, true)) {
                return null;
            }

            return FractalService::item($n, new NotificationTransformer);
        } catch (\Throwable $e) {
            Log::warning('NotificationService: failed to build notification', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    protected static function markMiss($id, $profileId = null): void
    {
        Cache::put(self::MISS_KEY.$id, 1, self::MISS_CACHE_TTL);

        if ($profileId) {
            Redis::zrem(self::CACHE_KEY.$profileId, $id);
        }
    }

    public static function setNotification(Notification $notification)
    {
        try {
            $item = FractalService::item($notification, new NotificationTransformer);
        } catch (\Throwable $e) {
            Log::warning('NotificationService: failed to transform notification', [
                'id' => $notification->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }

        Cache::forget(self::MISS_KEY.$notification->id);
        Cache::put(self::ITEM_KEY.$notification->id, $item, self::ITEM_CACHE_TTL);

        return $item;
    }

    /**
     * Create a notification, register it in the cache, and add it to the recipient's feed.
     *
     * @param  int  $profileId  The recipient profile ID
     * @param  int  $actorId  The actor profile ID who triggered the notification
     * @param  string|null  $action  The notification action (e.g. 'comment', 'like', 'follow')
     * @param  int  $itemId  The related item ID
     * @param  string  $itemType  The related item class (e.g. Status::class)
     */
    public static function createNotification(int $profileId, int $actorId, ?string $action, int $itemId, string $itemType): Notification
    {
        $notification = new Notification;
        $notification->profile_id = $profileId;
        $notification->actor_id = $actorId;
        $notification->action = $action;
        $notification->item_id = $itemId;
        $notification->item_type = $itemType;
        $notification->save();

        self::setNotification($notification);
        self::set($notification->profile_id, $notification->id);

        return $notification;
    }

    /**
     * Create a notification only if one doesn't already exist for this actor+action+item combination.
     *
     * Use this for actions that can be triggered multiple times but should only notify once,
     * such as shares/boosts (a user can only boost a post once) and mentions.
     *
     * @param  int  $profileId  The recipient profile ID
     * @param  int  $actorId  The actor profile ID who triggered the notification
     * @param  string  $action  The notification action (e.g. 'share', 'mention')
     * @param  int  $itemId  The related item ID
     * @param  string  $itemType  The related item class (e.g. Status::class)
     */
    public static function firstOrCreateNotification(int $profileId, int $actorId, string $action, int $itemId, string $itemType): Notification
    {
        $notification = Notification::firstOrCreate([
            'profile_id' => $profileId,
            'actor_id' => $actorId,
            'action' => $action,
            'item_id' => $itemId,
            'item_type' => $itemType,
        ]);

        if ($notification->wasRecentlyCreated) {
            self::setNotification($notification);
            self::set($notification->profile_id, $notification->id);
        }

        return $notification;
    }
}
