<?php

namespace App\Services;

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
     * matched the ids stored as scores and forced a DB hit on every call.
     */
    public static function get($id, $start = 0, $stop = 400)
    {
        $stop = min((int) $stop, self::MAX_ITEMS);
        $start = max((int) $start, 0);

        self::warmCache($id);

        $ids = Redis::zrevrange(self::CACHE_KEY.$id, $start, $start + $stop - 1);
        $ids = array_map('intval', $ids ?: []);

        if (empty($ids)) {
            $ids = self::coldGet($id, $start, $stop)->all();
        }

        return collect(array_values(self::getNotifications($ids, $id)));
    }

    /**
     * DB fallback for get(). Bounded by the (profile_id, deleted_at, id)
     * index and LIMIT, so no time-window lower bound is needed.
     */
    public static function coldGet($id, $start = 0, $stop = 400)
    {
        $stop = min((int) $stop, self::MAX_ITEMS);

        $ids = Notification::where('profile_id', $id)
            ->orderByDesc('id')
            ->skip($start)
            ->take($stop)
            ->pluck('id');

        if ($ids->count()) {
            self::addMany($id, $ids->all());
        }

        return $ids;
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
        return self::fetchPage($id, $maxId, 'max', $limit, self::renderableFilter());
    }

    public static function getMinPage($id = false, $minId = null, $limit = 10)
    {
        return self::fetchPage($id, $minId, 'min', $limit, self::renderableFilter());
    }

    protected static function renderableFilter(): callable
    {
        // Notification types that are meaningless without an attached status.
        $statusTypes = ['comment', 'mention', 'share', 'reblog', 'favourite'];

        // Notification types that don't have an attached status.
        $otherTypes = array_merge($statusTypes, [
            'follow',
            'follow_request',
            'direct',
            'tagged',
            'modlog',
            'group',
            'story:react',
            'story:comment',
        ]);

        return function ($n) use ($statusTypes, $otherTypes) {
            if (! isset($n['account']['id'])) {
                return null;
            }

            $type = $n['type'] ?? null;

            if ($type !== null && ! in_array($type, $otherTypes)) {
                Log::warning('NotificationService: unexpected notification type in renderableFilter', [
                    'type' => $type,
                    'notification_id' => $n['id'] ?? null,
                ]);
            }

            if (in_array($type, $statusTypes) && ! isset($n['status']['id'])) {
                return null;
            }

            return $n;
        };
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

            // One batched hydration per round instead of one per id.
            $hydrated = self::getNotifications($ids, $id);

            foreach ($ids as $nid) {
                $firstScanned = $firstScanned ?? $nid;
                $lastScanned = $nid;

                $n = $hydrated[$nid] ?? null;
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
     * or the user scrolled past the MAX_ITEMS window). Every variant is a
     * bounded range on the (profile_id, deleted_at, id) index.
     */
    protected static function dbIds($id, string $direction, int $cursor, ?int $bound, int $limit): array
    {
        $q = Notification::where('profile_id', $id)
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

        self::write($key, [$val]);

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

    protected static function addMany($id, array $ids): void
    {
        self::write(self::CACHE_KEY.$id, $ids);
    }

    /**
     * Add ids to a profile's zset, trim to the newest MAX_ITEMS, and refresh
     * the TTL, all in one pipeline. The TTL means inactive profiles stop
     * holding a 400-entry zset in Redis forever.
     */
    protected static function write(string $key, array $ids): void
    {
        if (empty($ids)) {
            return;
        }

        Redis::pipeline(function ($pipe) use ($key, $ids) {
            foreach ($ids as $nid) {
                $pipe->zadd($key, $nid, $nid);
            }
            $pipe->zremrangebyrank($key, 0, -(self::MAX_ITEMS + 1));
            $pipe->expire($key, self::WARM_TTL);
        });
    }

    public static function isWarm($id): bool
    {
        return (bool) Redis::exists(self::WARM_KEY.$id);
    }

    /**
     * Rebuild the id zset from the DB.
     *
     * Runs when the profile has never been marked warm (or the marker
     * expired), or when forced. A warm marker with an empty zset is a valid
     * state (profile has no notifications), so we no longer re-query the DB
     * on every request for those profiles. If the zset itself was evicted
     * while the marker survived, coldGet/dbIds rebuild it on the next read.
     */
    public static function warmCache($id, $stop = 400, $force = false)
    {
        if (! $force && self::isWarm($id)) {
            return 0;
        }

        $stop = min((int) $stop, self::MAX_ITEMS);

        $ids = Notification::where('profile_id', $id)
            ->orderByDesc('id')
            ->limit($stop)
            ->pluck('id')
            ->all();

        self::addMany($id, $ids);

        Redis::set(self::WARM_KEY.$id, 1, 'EX', self::WARM_TTL);

        return 1;
    }

    /**
     * Fetch a single transformed notification. Thin wrapper over the batch
     * path so callers outside this service keep working.
     */
    public static function getNotification($id, $profileId = null)
    {
        return self::getNotifications([$id], $profileId)[(int) $id] ?? null;
    }

    /**
     * Fetch many transformed notifications in as few round trips as possible.
     *
     * One MGET covers miss markers and cached items for every id, one DB
     * query builds whatever is cold, and accounts are resolved once per
     * distinct account rather than once per notification.
     *
     * Returns [id => notification] in input order. Anything that cannot be
     * rendered (missing row, deleted actor, deleted status, transformer
     * failure) is omitted, negatively cached briefly, and when $profileId is
     * supplied pruned from that profile's zset so future pages stop tripping
     * over it.
     */
    public static function getNotifications(array $ids, $profileId = null): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));

        if (empty($ids)) {
            return [];
        }

        $missKeys = [];
        $itemKeys = [];
        foreach ($ids as $nid) {
            $missKeys[$nid] = self::MISS_KEY.$nid;
            $itemKeys[$nid] = self::ITEM_KEY.$nid;
        }

        $cached = Cache::many(array_merge(array_values($missKeys), array_values($itemKeys)));

        $items = [];
        $cold = [];

        foreach ($ids as $nid) {
            if (! empty($cached[$missKeys[$nid]])) {
                continue;
            }

            $item = $cached[$itemKeys[$nid]] ?? null;

            if ($item) {
                $items[$nid] = $item;
            } else {
                $cold[] = $nid;
            }
        }

        if (! empty($cold)) {
            $built = self::buildNotifications($cold);
            $putMany = [];

            foreach ($cold as $nid) {
                if (isset($built[$nid])) {
                    $items[$nid] = $built[$nid];
                    $putMany[self::ITEM_KEY.$nid] = $built[$nid];
                } else {
                    self::markMiss($nid, $profileId);
                }
            }

            if (! empty($putMany)) {
                Cache::putMany($putMany, self::ITEM_CACHE_TTL);
            }
        }

        // Resolve each distinct account once. A page of notifications is
        // usually dominated by a handful of actors.
        $accounts = [];
        foreach ($items as $n) {
            $aid = $n['account']['id'] ?? null;
            if ($aid && ! array_key_exists($aid, $accounts)) {
                $accounts[$aid] = AccountService::get($aid, true);
            }
        }

        $out = [];

        foreach ($ids as $nid) {
            if (! isset($items[$nid])) {
                continue;
            }

            $n = $items[$nid];

            if (isset($n['account']['id'])) {
                $account = $accounts[$n['account']['id']] ?? null;

                if (! $account) {
                    self::markMiss($nid, $profileId);

                    continue;
                }

                $n['account'] = $account;
            }

            $out[$nid] = $n;
        }

        return $out;
    }

    /**
     * Build transformed notifications from the DB in one query.
     * Returns [id => notification] for everything that rendered.
     */
    protected static function buildNotifications(array $ids): array
    {
        $built = [];

        if (empty($ids)) {
            return $built;
        }

        try {
            $rows = Notification::with('item')->whereIn('id', $ids)->get();
        } catch (\Throwable $e) {
            Log::warning('NotificationService: failed to load notifications', [
                'ids' => $ids,
                'error' => $e->getMessage(),
            ]);

            return $built;
        }

        $actors = [];
        foreach ($rows->pluck('actor_id')->unique() as $aid) {
            $actors[$aid] = (bool) AccountService::get($aid, true);
        }

        foreach ($rows as $n) {
            if ($n->item_id && in_array($n->item_type, ['App\Status', Status::class]) && ! $n->item) {
                continue;
            }

            if (empty($actors[$n->actor_id])) {
                continue;
            }

            try {
                $built[(int) $n->id] = FractalService::item($n, new NotificationTransformer);
            } catch (\Throwable $e) {
                Log::warning('NotificationService: failed to build notification', [
                    'id' => $n->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $built;
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
