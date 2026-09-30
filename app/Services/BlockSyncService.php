<?php

namespace App\Services;

use App\Models\Profile;
use App\Util\ActivityPub\Helpers;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Implements FEP-070c: Block synchronization across servers. Our first FEP :)
 */
class BlockSyncService
{
    const HEADER = 'Block-Synchronization';

    const CONTEXT_TERM = 'https://w3id.org/fep/070c#blockSynchronization';

    const DIGESTS_CACHE_KEY = 'pf:services:block-sync:digests:v1';

    const DIGESTS_LOCK_KEY = 'pf:services:block-sync:digests:lock';

    const DIGESTS_TTL = 3600;

    const COOLDOWN_KEY = 'pf:services:block-sync:cooldown:';

    const URL_SEEN_KEY = 'pf:services:block-sync:url-seen:';

    const RECEIVED_DIGEST_KEY = 'pf:services:block-sync:received:v1:';

    const RECEIVED_DIGEST_TTL = 3600;

    const REMOVAL_GRACE_MINUTES = 10;

    const MAX_ITEMS = 50000;

    const MAX_APPLY_PER_RUN = 1000;

    const MAX_NEW_ACTORS_PER_RUN = 50;

    const COLLECTION_TYPES = [
        'Collection',
        'OrderedCollection',
        'CollectionPage',
        'OrderedCollectionPage',
    ];

    public static function federating(): bool
    {
        return (bool) config_cache('federation.activitypub.enabled');
    }

    public static function receiving(): bool
    {
        return self::federating()
            && (bool) config('federation.activitypub.block_sync.enabled', true);
    }

    public static function disclosing(): bool
    {
        return self::federating()
            && (bool) config('federation.activitypub.block_sync.disclose', true);
    }

    public static function endpointUrl(): string
    {
        return url('/f/block_sync');
    }

    public static function pairHash(string $actor, string $object): string
    {
        return hash('sha256', $actor.' '.$object, true);
    }

    public static function digest(iterable $pairs): string
    {
        $acc = str_repeat("\0", 32);

        foreach ($pairs as $pair) {
            if (
                ! is_array($pair)
                || ! isset($pair['actor'], $pair['object'])
                || ! is_string($pair['actor'])
                || ! is_string($pair['object'])
            ) {
                continue;
            }

            $acc ^= self::pairHash($pair['actor'], $pair['object']);
        }

        return bin2hex($acc);
    }

    public static function parseHeader(mixed $value): ?array
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        if ($value === '' || strlen($value) > 2048) {
            return null;
        }

        preg_match_all(
            '/(?:^|,)\s*([A-Za-z][A-Za-z0-9_-]*)\s*=\s*"([^"]*)"/',
            $value,
            $matches,
            PREG_SET_ORDER
        );

        $params = [];

        foreach ($matches as $match) {
            if (isset($params[$match[1]])) {
                return null;
            }

            $params[$match[1]] = $match[2];
        }

        if (empty($params['url']) || empty($params['digest'])) {
            return null;
        }

        $digest = strtolower($params['digest']);

        if (! preg_match('/^[0-9a-f]{64}$/', $digest)) {
            return null;
        }

        return [
            'url' => $params['url'],
            'digest' => $digest,
        ];
    }

    public static function itemToPair(mixed $item): ?array
    {
        if (! is_array($item)) {
            return null;
        }

        $types = array_filter((array) ($item['type'] ?? null), 'is_string');

        if (! in_array('Block', $types, true)) {
            return null;
        }

        $actor = self::idOf($item['actor'] ?? null);
        $object = self::idOf($item['object'] ?? null);

        if ($actor === null || $object === null) {
            return null;
        }

        return ['actor' => $actor, 'object' => $object];
    }

    public static function idOf(mixed $value): ?string
    {
        if (is_array($value)) {
            $value = $value['id'] ?? null;
        }

        return is_string($value) && $value !== '' && strlen($value) <= 2048 ? $value : null;
    }

    public static function isDisclosedPeer(string $authority): bool
    {
        if ($authority === FollowersSyncService::localAuthority()) {
            return false;
        }

        $host = parse_url($authority, PHP_URL_HOST);

        if (! is_string($host) || $host === '') {
            return false;
        }

        $host = strtolower($host);

        return ! in_array($host, (array) InstanceService::getBannedDomains(), true)
            && ! in_array($host, (array) InstanceService::getUnlistedDomains(), true);
    }

    protected static function disclosureQuery(): Builder
    {
        return DB::table('user_filters as uf')
            ->join('profiles as blocker', 'blocker.id', '=', 'uf.user_id')
            ->join('profiles as blocked', 'blocked.id', '=', 'uf.filterable_id')
            ->where('uf.filterable_type', Profile::class)
            ->where('uf.filter_type', 'block')
            ->whereNull('blocker.domain')
            ->whereNull('blocker.deleted_at')
            ->whereNotNull('blocked.domain')
            ->whereNotNull('blocked.remote_url')
            ->whereNull('blocked.deleted_at');
    }

    protected static function localActorIdFor(int $id, string $username): string
    {
        $profile = new Profile;
        $profile->forceFill([
            'id' => $id,
            'username' => $username,
            'domain' => null,
            'remote_url' => null,
        ]);

        return FollowersSyncService::localActorId($profile);
    }

    public static function disclosedPairs(string $authority): array
    {
        $host = parse_url($authority, PHP_URL_HOST);

        if (! is_string($host) || $host === '') {
            return [];
        }

        $pairs = [];

        self::disclosureQuery()
            ->where('blocked.domain', strtolower($host))
            ->select('uf.id', 'blocker.id as blocker_id', 'blocker.username as blocker_username', 'blocked.remote_url as blocked_url')
            ->chunkById(2000, function ($rows) use (&$pairs, $authority) {
                foreach ($rows as $row) {
                    if (FollowersSyncService::authority($row->blocked_url) !== $authority) {
                        continue;
                    }

                    $pairs[] = [
                        'actor' => self::localActorIdFor((int) $row->blocker_id, (string) $row->blocker_username),
                        'object' => (string) $row->blocked_url,
                    ];
                }

                return count($pairs) <= self::MAX_ITEMS;
            }, 'uf.id', 'id');

        usort($pairs, fn (array $a, array $b) => [$a['actor'], $a['object']] <=> [$b['actor'], $b['object']]);

        return $pairs;
    }

    public static function outboundDigests(): array
    {
        $cached = Cache::get(self::DIGESTS_CACHE_KEY);

        if (
            is_array($cached)
            && isset($cached['computed_at'], $cached['digests'])
            && is_array($cached['digests'])
            && (int) $cached['computed_at'] > now()->subSeconds(self::DIGESTS_TTL)->getTimestamp()
        ) {
            return $cached['digests'];
        }

        $acc = [];

        self::disclosureQuery()
            ->select('uf.id', 'blocker.id as blocker_id', 'blocker.username as blocker_username', 'blocked.remote_url as blocked_url')
            ->chunkById(5000, function ($rows) use (&$acc) {
                foreach ($rows as $row) {
                    $authority = FollowersSyncService::authority($row->blocked_url);

                    if (! $authority) {
                        continue;
                    }

                    $acc[$authority] = ($acc[$authority] ?? str_repeat("\0", 32))
                        ^ self::pairHash(
                            self::localActorIdFor((int) $row->blocker_id, (string) $row->blocker_username),
                            (string) $row->blocked_url
                        );
                }
            }, 'uf.id', 'id');

        $digests = array_map('bin2hex', $acc);

        Cache::put(self::DIGESTS_CACHE_KEY, [
            'computed_at' => now()->getTimestamp(),
            'digests' => $digests,
        ], self::DIGESTS_TTL * 2);

        return $digests;
    }

    public static function forgetOutboundDigests(): void
    {
        Cache::forget(self::DIGESTS_CACHE_KEY);
    }

    protected static function toggleOutboundDigest(string $authority, string $actor, string $object): void
    {
        try {
            Cache::lock(self::DIGESTS_LOCK_KEY, 10)->block(5, function () use ($authority, $actor, $object) {
                $cached = Cache::get(self::DIGESTS_CACHE_KEY);

                if (! is_array($cached) || ! isset($cached['digests']) || ! is_array($cached['digests'])) {
                    return;
                }

                $current = isset($cached['digests'][$authority]) ? hex2bin($cached['digests'][$authority]) : false;
                $next = bin2hex(($current !== false ? $current : str_repeat("\0", 32)) ^ self::pairHash($actor, $object));

                if ($next === FollowersSyncService::EMPTY_DIGEST) {
                    unset($cached['digests'][$authority]);
                } else {
                    $cached['digests'][$authority] = $next;
                }

                Cache::put(self::DIGESTS_CACHE_KEY, $cached, self::DIGESTS_TTL * 2);
            });
        } catch (Throwable) {
            self::forgetOutboundDigests();
        }
    }

    public static function header(string $inboxUrl, ?array $digests = null): ?string
    {
        if (! self::disclosing()) {
            return null;
        }

        $authority = FollowersSyncService::authority($inboxUrl);

        if (! $authority || ! self::isDisclosedPeer($authority)) {
            return null;
        }

        $digests ??= self::outboundDigests();

        return sprintf(
            'url="%s", digest="%s"',
            self::endpointUrl(),
            $digests[$authority] ?? FollowersSyncService::EMPTY_DIGEST
        );
    }

    public static function decorateInstanceActor(array $actor): array
    {
        $context = $actor['@context'] ?? [];
        $context = is_array($context) ? $context : [$context];

        $context[] = [
            'blockSynchronization' => [
                '@id' => self::CONTEXT_TERM,
                '@type' => '@id',
            ],
        ];

        $actor['@context'] = $context;
        $actor['blockSynchronization'] = self::endpointUrl();

        return $actor;
    }

    protected static function receivedQuery(string $host): Builder
    {
        return DB::table('user_filters as uf')
            ->join('profiles as blocker', 'blocker.id', '=', 'uf.user_id')
            ->join('profiles as blocked', 'blocked.id', '=', 'uf.filterable_id')
            ->where('uf.filterable_type', Profile::class)
            ->where('uf.filter_type', 'block')
            ->where('blocker.domain', strtolower($host))
            ->whereNotNull('blocker.remote_url')
            ->whereNull('blocker.deleted_at')
            ->whereNull('blocked.domain')
            ->whereNull('blocked.deleted_at');
    }

    public static function receivedBlocks(string $authority): Collection
    {
        $host = parse_url($authority, PHP_URL_HOST);

        if (! is_string($host) || $host === '') {
            return collect();
        }

        return self::receivedQuery($host)
            ->select('uf.id', 'uf.user_id', 'uf.filterable_id', 'uf.created_at', 'blocker.remote_url as blocker_url', 'blocked.username as blocked_username')
            ->get()
            ->filter(fn ($row) => FollowersSyncService::authority($row->blocker_url) === $authority)
            ->mapWithKeys(fn ($row) => [
                $row->user_id.':'.$row->filterable_id => [
                    'id' => (int) $row->id,
                    'blocker_id' => (int) $row->user_id,
                    'blocked_id' => (int) $row->filterable_id,
                    'created_at' => $row->created_at,
                    'actor' => (string) $row->blocker_url,
                    'object' => self::localActorIdFor((int) $row->filterable_id, (string) $row->blocked_username),
                ],
            ]);
    }

    public static function receivedDigest(string $authority, bool $fresh = false): string
    {
        $key = self::RECEIVED_DIGEST_KEY.hash('sha256', $authority);

        if ($fresh) {
            $digest = self::digest(self::receivedBlocks($authority)->values());
            Cache::put($key, $digest, self::RECEIVED_DIGEST_TTL);

            return $digest;
        }

        return Cache::remember(
            $key,
            self::RECEIVED_DIGEST_TTL,
            fn () => self::digest(self::receivedBlocks($authority)->values())
        );
    }

    public static function forgetReceivedDigest(?string $authority): void
    {
        if ($authority) {
            Cache::forget(self::RECEIVED_DIGEST_KEY.hash('sha256', $authority));
        }
    }

    public static function rememberSyncUrl(string $authority, string $url): void
    {
        if (strlen($url) > 255 || FollowersSyncService::authority($url) !== $authority) {
            return;
        }

        $host = parse_url($authority, PHP_URL_HOST);

        if (! is_string($host) || $host === '') {
            return;
        }

        if (! Cache::add(self::URL_SEEN_KEY.hash('sha256', $authority.'|'.$url), 1, 86400)) {
            return;
        }

        DB::table('instances')
            ->where('domain', strtolower($host))
            ->where(function ($query) use ($url) {
                $query->whereNull('block_sync_url')
                    ->orWhere('block_sync_url', '!=', $url);
            })
            ->update(['block_sync_url' => $url]);
    }

    protected static function resolveRemoteActors(array $actorUrls): array
    {
        $resolved = [];

        foreach (array_chunk($actorUrls, 500) as $chunk) {
            Profile::whereNotNull('domain')
                ->whereIn('remote_url', $chunk)
                ->get(['id', 'username', 'domain', 'remote_url', 'status'])
                ->each(function (Profile $profile) use (&$resolved) {
                    $resolved[(string) $profile->remote_url] = $profile;
                });
        }

        $fetched = 0;

        foreach ($actorUrls as $url) {
            if (isset($resolved[$url]) || $fetched >= self::MAX_NEW_ACTORS_PER_RUN) {
                continue;
            }

            $fetched++;

            try {
                $profile = Helpers::profileFirstOrNew($url);
            } catch (Throwable) {
                $profile = null;
            }

            if ($profile && $profile->domain !== null && $profile->remote_url === $url) {
                $resolved[$url] = $profile;
            }
        }

        return $resolved;
    }

    public static function fetchCollection(string $url, string $authority): ?array
    {
        $maxPages = max(1, (int) config('federation.activitypub.block_sync.max_pages', 10));

        $document = self::fetchDocument($url, $authority);

        if (! self::isCollection($document)) {
            return null;
        }

        $total = isset($document['totalItems']) && is_int($document['totalItems']) ? $document['totalItems'] : null;

        $items = [];
        $malformed = false;
        $pages = 0;
        $seen = [$url => true];

        $page = self::hasItems($document)
            ? $document
            : ($document['first'] ?? null);

        while ($page !== null) {
            if (is_string($page)) {
                if (isset($seen[$page])) {
                    return null;
                }

                if (++$pages > $maxPages) {
                    return ['items' => $items, 'complete' => false, 'malformed' => $malformed, 'total' => $total];
                }

                $seen[$page] = true;
                $page = self::fetchDocument($page, $authority);
            }

            if (! self::isCollection($page)) {
                return null;
            }

            $pageItems = $page['orderedItems'] ?? $page['items'] ?? [];

            if (! is_array($pageItems)) {
                return null;
            }

            if (! array_is_list($pageItems)) {
                $pageItems = [$pageItems];
            }

            foreach ($pageItems as $item) {
                $pair = self::itemToPair($item);

                if ($pair === null) {
                    $malformed = true;

                    continue;
                }

                $items[] = $pair;
            }

            if (count($items) > self::MAX_ITEMS) {
                return null;
            }

            $page = $page['next'] ?? null;

            if ($page !== null && ! is_string($page) && ! is_array($page)) {
                return null;
            }
        }

        return ['items' => $items, 'complete' => true, 'malformed' => $malformed, 'total' => $total];
    }

    private static function fetchDocument(string $url, string $authority): ?array
    {
        if (FollowersSyncService::authority($url) !== $authority) {
            return null;
        }

        $document = ActivityPubFetchService::fetchRequest($url, true);

        return is_array($document) ? $document : null;
    }

    private static function isCollection(mixed $document): bool
    {
        if (! is_array($document)) {
            return false;
        }

        foreach ((array) ($document['type'] ?? null) as $type) {
            if (is_string($type) && in_array($type, self::COLLECTION_TYPES, true)) {
                return true;
            }
        }

        return false;
    }

    private static function hasItems(array $document): bool
    {
        return array_key_exists('orderedItems', $document)
            || array_key_exists('items', $document);
    }
}
