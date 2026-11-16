<?php

namespace App\Services;

use App\Exceptions\CollectionException;
use App\Models\Collection;
use App\Models\CollectionItem;
use App\Models\CollectionItemRemoval;
use App\Models\Profile;
use App\Models\Status;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class CollectionService
{
    const CACHE_KEY = 'pf:services:collections-v1:';

    public const MAX_ITEMS_CEILING = 1000;

    public const MAX_COLLECTIONS_PER_POST = 10;

    public const POLICY_EVERYONE = 'everyone';

    public const POLICY_FOLLOWERS = 'followers';

    public const POLICY_NOBODY = 'nobody';

    public const POLICIES = [
        self::POLICY_EVERYONE,
        self::POLICY_FOLLOWERS,
        self::POLICY_NOBODY,
    ];

    public const COLLECTIBLE_TYPES = [
        'photo',
        'photo:album',
        'video',
        'video:album',
        'photo:video:album',
    ];

    private const string POLICY_CACHE_KEY = 'pf:services:collections:collect-policy:';

    private const int POLICY_CACHE_TTL = 86400;

    public static function getItems($id, $start = 0, $stop = 10)
    {
        if (self::count($id)) {
            return Redis::zrangebyscore(self::CACHE_KEY.'items:'.$id, $start, $stop);
        }

        return self::coldBootItems($id);
    }

    public static function addItem($id, $sid, $score)
    {
        return Redis::zadd(self::CACHE_KEY.'items:'.$id, $score, $sid);
    }

    public static function removeItem($id, $sid)
    {
        return Redis::zrem(self::CACHE_KEY.'items:'.$id, $sid);
    }

    public static function clearItems($id)
    {
        return Redis::del(self::CACHE_KEY.'items:'.$id);
    }

    public static function coldBootItems($id)
    {
        return CollectionItem::whereCollectionId($id)
            ->orderBy('order')
            ->get()
            ->each(function ($item) use ($id) {
                return self::addItem($id, $item->object_id, $item->order);
            })
            ->map(function ($item) {
                return (string) $item->object_id;
            })
            ->values()
            ->toArray();
    }

    public static function count($id)
    {
        $count = Redis::zcard(self::CACHE_KEY.'items:'.$id);
        if (! $count) {
            self::coldBootItems($id);
            $count = Redis::zcard(self::CACHE_KEY.'items:'.$id);
        }

        return $count;
    }

    public static function getCollection($id)
    {
        $collection = Cache::remember(self::CACHE_KEY.'get:'.$id, 86400, function () use ($id) {
            $collection = Collection::find($id);
            if (! $collection) {
                return false;
            }
            $account = AccountService::get($collection->profile_id);
            if (! $account) {
                return false;
            }

            return [
                'id' => (string) $collection->id,
                'pid' => (string) $collection->profile_id,
                'visibility' => $collection->visibility,
                'title' => $collection->title,
                'description' => $collection->description,
                'thumb' => url('/storage/no-preview.png'),
                'url' => $collection->url(),
                'updated_at' => $collection->updated_at,
                'published_at' => $collection->published_at,
            ];
        });

        if ($collection) {
            $account = AccountService::get($collection['pid']);
            if (! $account) {
                return false;
            }
            $collection['avatar'] = $account['avatar'];
            $collection['username'] = $account['username'];
            $collection['thumb'] = self::getThumb($id);
            $collection['post_count'] = self::count($id);
        }

        return $collection;
    }

    public static function setCollection($id, $collection)
    {
        $account = AccountService::get($collection->profile_id);
        if (! $account) {
            return false;
        }
        $res = [
            'id' => (string) $collection->id,
            'pid' => (string) $collection->profile_id,
            'visibility' => $collection->visibility,
            'title' => $collection->title,
            'description' => $collection->description,
            'thumb' => self::getThumb($id),
            'url' => $collection->url(),
            'updated_at' => $collection->updated_at,
            'published_at' => $collection->published_at,
        ];
        Cache::put(self::CACHE_KEY.'get:'.$id, $res, 86400);
        $res['avatar'] = $account['avatar'];
        $res['username'] = $account['username'];
        $res['post_count'] = self::count($id);

        return $res;
    }

    public static function deleteCollection($id)
    {
        Redis::del(self::CACHE_KEY.'items:'.$id);
        Cache::forget(self::CACHE_KEY.'get:'.$id);
    }

    public static function getThumb($id)
    {
        $item = self::getItems($id, 0, 1);
        if (! $item) {
            return url('/storage/no-preview.png');
        }
        $status = StatusService::get($item[0]);
        if (! $status) {
            return url('/storage/no-preview.png');
        }

        if (! isset($status['media_attachments']) || empty($status['media_attachments'])) {
            return url('/storage/no-preview.png');
        }

        return $status['media_attachments'][0]['url'];
    }

    public static function maxItems(): int
    {
        $max = (int) config_cache('pixelfed.max_collection_length');

        if ($max < 1) {
            return self::MAX_ITEMS_CEILING;
        }

        return min($max, self::MAX_ITEMS_CEILING);
    }

    public static function normalizeSort(?string $sort): string
    {
        return in_array($sort, Collection::SORTS, true) ? $sort : Collection::SORT_OLDEST;
    }

    public static function collectPolicy(int $profileId): string
    {
        return Cache::remember(
            self::POLICY_CACHE_KEY.$profileId,
            self::POLICY_CACHE_TTL,
            function () use ($profileId) {
                $profile = Profile::find($profileId);

                if (! $profile || $profile->domain !== null) {
                    return self::POLICY_EVERYONE;
                }

                $policy = $profile->user?->settings?->can_collect;

                return in_array($policy, self::POLICIES, true)
                    ? $policy
                    : self::POLICY_EVERYONE;
            }
        );
    }

    public static function forgetCollectPolicy(int $profileId): void
    {
        Cache::forget(self::POLICY_CACHE_KEY.$profileId);
    }

    public static function policyAllows(int $authorId, int $collectorId): bool
    {
        if ($authorId === $collectorId) {
            return true;
        }

        return match (self::collectPolicy($authorId)) {
            self::POLICY_EVERYONE => true,
            self::POLICY_FOLLOWERS => (bool) FollowerService::follows((string) $collectorId, (string) $authorId),
            default => false,
        };
    }

    public static function collectError(Status $status, Profile $collector): ?string
    {
        if ($status->reblog_of_id || ! in_array($status->type, self::COLLECTIBLE_TYPES, true)) {
            return 'Only photo and video posts can be added to collections.';
        }

        if ((int) $status->profile_id === (int) $collector->id) {
            if (! in_array($status->scope, ['public', 'unlisted'], true)) {
                return 'Only public and unlisted posts can be added to collections.';
            }

            return null;
        }

        if ($status->scope !== 'public') {
            return 'Only public posts from other accounts can be added to collections.';
        }

        $author = $status->profile;

        if (! $author || $author->status !== null) {
            return 'This post is not available.';
        }

        if (self::blocksEither((int) $author->id, (int) $collector->id)) {
            return 'You cannot add this post to a collection.';
        }

        if ($author->domain !== null && in_array(strtolower($author->domain), self::domainBlocks((int) $collector->id), true)) {
            return 'You cannot add this post to a collection.';
        }

        return match (self::collectPolicy((int) $author->id)) {
            self::POLICY_EVERYONE => null,
            self::POLICY_FOLLOWERS => FollowerService::follows((string) $collector->id, (string) $author->id)
                ? null
                : 'Only followers of this account can add their posts to collections.',
            default => 'This account does not allow their posts to be added to collections.',
        };
    }

    public static function addStatus(Collection $collection, Status $status, Profile $collector): bool
    {
        if ((int) $collection->profile_id !== (int) $collector->id) {
            throw new CollectionException('Collection not found.', 404);
        }

        if ($error = self::collectError($status, $collector)) {
            throw new CollectionException($error, 422);
        }

        if (CollectionItemRemoval::whereCollectionId($collection->id)->whereStatusId($status->id)->exists()) {
            throw new CollectionException('The author removed this post from this collection.', 403);
        }

        try {
            $added = DB::transaction(function () use ($collection, $status) {
                Collection::whereKey($collection->id)->lockForUpdate()->first();

                if (CollectionItem::whereCollectionId($collection->id)->whereObjectId($status->id)->exists()) {
                    return false;
                }

                $max = self::maxItems();

                if (CollectionItem::whereCollectionId($collection->id)->count() >= $max) {
                    throw new CollectionException('This collection is full. Collections can have up to '.$max.' posts.', 422);
                }

                $last = CollectionItem::whereCollectionId($collection->id)->max('order');

                CollectionItem::create([
                    'collection_id' => $collection->id,
                    'object_type' => Status::class,
                    'object_id' => $status->id,
                    'order' => $last === null ? 0 : ((int) $last + 1),
                ]);

                return true;
            });
        } catch (UniqueConstraintViolationException $e) {
            return false;
        }

        if ($added) {
            self::markUpdated($collection);
        }

        return $added;
    }

    public static function addStatusToCollections(Status $status, Profile $collector, array $collectionIds): array
    {
        $ids = collect($collectionIds)
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->take(self::MAX_COLLECTIONS_PER_POST)
            ->values();

        if ($ids->isEmpty()) {
            return [];
        }

        return Collection::whereProfileId($collector->id)
            ->whereIn('id', $ids->all())
            ->get()
            ->filter(function (Collection $collection) use ($status, $collector) {
                try {
                    self::addStatus($collection, $status, $collector);

                    return true;
                } catch (CollectionException $e) {
                    return false;
                }
            })
            ->map(fn (Collection $collection) => (string) $collection->id)
            ->values()
            ->all();
    }

    public static function removeStatus(Collection $collection, int $statusId): bool
    {
        $removed = DB::transaction(function () use ($collection, $statusId) {
            Collection::whereKey($collection->id)->lockForUpdate()->first();

            $item = CollectionItem::whereCollectionId($collection->id)
                ->whereObjectId($statusId)
                ->first();

            if (! $item) {
                return false;
            }

            $order = $item->order;
            $item->delete();

            if ($order !== null) {
                CollectionItem::whereCollectionId($collection->id)
                    ->where('order', '>', $order)
                    ->decrement('order');
            }

            return true;
        });

        if ($removed) {
            self::markUpdated($collection);
        }

        return $removed;
    }

    public static function removeByAuthor(Collection $collection, Status $status): bool
    {
        if ((int) $collection->profile_id === (int) $status->profile_id) {
            return self::removeStatus($collection, (int) $status->id);
        }

        CollectionItemRemoval::firstOrCreate([
            'collection_id' => $collection->id,
            'status_id' => $status->id,
        ], [
            'profile_id' => $status->profile_id,
        ]);

        return self::removeStatus($collection, (int) $status->id);
    }

    public static function destroy(Collection $collection): void
    {
        $id = $collection->id;

        DB::transaction(function () use ($collection) {
            $collection->items()->delete();
            $collection->removals()->delete();
            $collection->delete();
        });

        self::deleteCollection($id);
    }

    protected static function markUpdated(Collection $collection): void
    {
        $collection->updated_at = now();
        $collection->save();

        self::deleteCollection($collection->id);
    }

    public static function canView(Collection $collection, ?User $viewer): bool
    {
        $ownerId = (int) $collection->profile_id;

        if ($viewer && (int) $viewer->profile_id === $ownerId) {
            return true;
        }

        if ($viewer && $viewer->is_admin) {
            return true;
        }

        if ($collection->visibility === 'draft' || $collection->published_at === null) {
            return false;
        }

        $owner = AccountService::get($ownerId, true);

        if (! $owner) {
            return false;
        }

        if ($viewer && self::blocks($ownerId, (int) $viewer->profile_id)) {
            return false;
        }

        $follows = $viewer && FollowerService::follows((string) $viewer->profile_id, (string) $ownerId);

        return match ($collection->visibility) {
            'private' => $follows,
            'public' => empty($owner['locked']) || $follows,
            default => false,
        };
    }

    public static function filterVisible(iterable $statuses, int $ownerId, ?int $viewerId = null): array
    {
        $authorAllowed = [];
        $viewerFilters = $viewerId ? array_map('intval', UserFilterService::filters($viewerId)) : [];
        $viewerDomains = $viewerId ? self::domainBlocks($viewerId) : [];

        $res = [];

        foreach ($statuses as $status) {
            if (! $status || ! isset($status['account']['id']) || empty($status['media_attachments'])) {
                continue;
            }

            $visibility = $status['visibility'] ?? null;
            $authorId = (int) $status['account']['id'];

            if ($authorId === $ownerId) {
                if (! in_array($visibility, ['public', 'unlisted'], true)) {
                    continue;
                }
            } else {
                if ($visibility !== 'public') {
                    continue;
                }

                if (! isset($authorAllowed[$authorId])) {
                    $authorAllowed[$authorId] = ! self::blocksEither($authorId, $ownerId)
                        && self::policyAllows($authorId, $ownerId);
                }

                if (! $authorAllowed[$authorId]) {
                    continue;
                }
            }

            if ($viewerId && $viewerId !== $authorId) {
                if (in_array($authorId, $viewerFilters, true) || self::blocks($authorId, $viewerId)) {
                    continue;
                }

                $domain = self::accountDomain($status['account']);

                if ($domain && in_array($domain, $viewerDomains, true)) {
                    continue;
                }
            }

            $res[] = $status;
        }

        return $res;
    }

    public static function toApi(Collection $collection, ?int $viewerId = null, ?int $postCount = null): ?array
    {
        $account = AccountService::get($collection->profile_id, true);

        if (! $account) {
            return null;
        }

        $isOwner = $viewerId !== null && $viewerId === (int) $collection->profile_id;

        return [
            'id' => (string) $collection->id,
            'title' => $collection->title,
            'description' => $collection->description,
            'visibility' => $collection->visibility,
            'sort' => self::normalizeSort($collection->sort),
            'sensitive' => (bool) $collection->is_nsfw,
            'post_count' => $postCount ?? CollectionItem::whereCollectionId($collection->id)->count(),
            'max_posts' => self::maxItems(),
            'thumbnail' => self::thumbnail($collection),
            'url' => $collection->url(),
            'is_owner' => $isOwner,
            'account' => $account,
            'published_at' => self::timestamp($collection->published_at),
            'created_at' => self::timestamp($collection->created_at),
            'updated_at' => self::timestamp($collection->updated_at),
        ];
    }

    public static function toApiMany(iterable $collections, ?int $viewerId = null): array
    {
        $collections = collect($collections);

        if ($collections->isEmpty()) {
            return [];
        }

        $counts = CollectionItem::whereIn('collection_id', $collections->pluck('id')->all())
            ->groupBy('collection_id')
            ->selectRaw('collection_id, count(*) as aggregate')
            ->pluck('aggregate', 'collection_id');

        return $collections
            ->map(fn (Collection $collection) => self::toApi($collection, $viewerId, (int) ($counts[$collection->id] ?? 0)))
            ->filter()
            ->values()
            ->all();
    }

    public static function thumbnail(Collection $collection): ?array
    {
        $direction = self::normalizeSort($collection->sort) === Collection::SORT_RECENT ? 'desc' : 'asc';

        $statuses = CollectionItem::whereCollectionId($collection->id)
            ->orderBy('order', $direction)
            ->orderBy('id', $direction)
            ->limit(10)
            ->pluck('object_id')
            ->map(fn ($id) => StatusService::get($id, false));

        $first = self::filterVisible($statuses, (int) $collection->profile_id)[0] ?? null;

        if (! $first) {
            return null;
        }

        $media = $first['media_attachments'][0];

        return [
            'status_id' => (string) $first['id'],
            'type' => $media['type'] ?? null,
            'url' => $media['url'] ?? null,
            'preview_url' => $media['preview_url'] ?? null,
            'blurhash' => $media['blurhash'] ?? null,
        ];
    }

    public static function blocks(int $profileId, int $targetId): bool
    {
        $blocks = UserFilterService::blocks($profileId);

        return $blocks && in_array($targetId, array_map('intval', $blocks), true);
    }

    public static function blocksEither(int $a, int $b): bool
    {
        return self::blocks($a, $b) || self::blocks($b, $a);
    }

    protected static function domainBlocks(int $profileId): array
    {
        return array_map('strtolower', UserFilterService::domainBlocks($profileId) ?: []);
    }

    protected static function accountDomain(array $account): ?string
    {
        if (! empty($account['local'])) {
            return null;
        }

        $acct = (string) ($account['acct'] ?? '');
        $at = strrpos($acct, '@');

        return $at === false ? null : strtolower(substr($acct, $at + 1));
    }

    public static function timestamp($value): ?string
    {
        if (! $value) {
            return null;
        }

        $date = $value instanceof \DateTimeInterface ? Carbon::instance($value) : Carbon::parse($value);

        return $date->utc()->format('Y-m-d\TH:i:s.v\Z');
    }
}
