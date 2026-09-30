<?php

namespace App\Services;

use App\Jobs\Federation\BlockSyncPipeline;
use App\Jobs\Federation\DeliverBlockActivity;
use App\Jobs\FollowPipeline\UnfollowPipeline;
use App\Models\Follower;
use App\Models\FollowRequest;
use App\Models\Profile;
use App\Models\UserFilter;
use App\Util\ActivityPub\Helpers;
use App\Util\ActivityPub\HttpSignature;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class BlockSyncService
{
    const HEADER = 'Block-Synchronization';

    const CONTEXT_TERM = 'https://w3id.org/fep/070c#blockSynchronization';

    const DIGEST_CONTEXT_TERM = 'https://w3id.org/fep/070c#blockSynchronizationDigest';

    const DIGESTS_CACHE_KEY = 'pf:services:block-sync:digests:v1';

    const DIGESTS_LOCK_KEY = 'pf:services:block-sync:digests:lock';

    const DIGESTS_TTL = 3600;

    const COOLDOWN_KEY = 'pf:services:block-sync:cooldown:';

    const URL_SEEN_KEY = 'pf:services:block-sync:url-seen:';

    const RECEIVED_DIGEST_KEY = 'pf:services:block-sync:received:v1:';

    const RECEIVED_DIGEST_TTL = 3600;

    const SIGNED_DIGEST_KEY = 'pf:services:block-sync:signed-digest:';

    const SIGNED_DIGEST_TTL = 3600;

    const FAILURE_COOLDOWN = 21600;

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
            && (bool) config('federation.activitypub.block_sync.disclose', false);
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

    public static function localBlockChanged(UserFilter $filter, bool $created): void
    {
        try {
            if ($filter->filterable_type !== Profile::class || $filter->filter_type !== 'block') {
                return;
            }

            $blocker = Profile::withTrashed()->find($filter->user_id, ['id', 'username', 'domain', 'remote_url']);

            if (! $blocker || $blocker->domain !== null) {
                return;
            }

            $blocked = Profile::withTrashed()->find($filter->filterable_id, ['id', 'domain', 'remote_url', 'inbox_url', 'deleted_at']);

            if (! $blocked || $blocked->domain === null || ! $blocked->remote_url) {
                return;
            }

            $authority = FollowersSyncService::authority($blocked->remote_url);

            if (! $authority) {
                return;
            }

            self::toggleOutboundDigest($authority, FollowersSyncService::localActorId($blocker), $blocked->remote_url);

            if (
                self::disclosing()
                && ! $blocked->trashed()
                && $blocked->inbox_url
                && self::isDisclosedPeer($authority)
            ) {
                DeliverBlockActivity::dispatch(
                    (int) $blocker->id,
                    (int) $blocked->id,
                    (int) $filter->id,
                    ! $created
                )->onQueue('high');
            }
        } catch (Throwable $e) {
            Log::warning('BlockSync: unable to process local block change', [
                'filter_id' => $filter->id,
                'error' => $e->getMessage(),
            ]);
        }
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

    public static function handleInboundHeaders(mixed $headers): void
    {
        try {
            self::processInboundHeaders($headers);
        } catch (Throwable $e) {
            Log::debug('BlockSync: unable to process Block-Synchronization header', [
                'exception' => $e::class,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private static function processInboundHeaders(mixed $headers): void
    {
        if (! self::receiving() || ! is_array($headers)) {
            return;
        }

        $headers = array_change_key_case($headers, CASE_LOWER);

        $raw = self::singleHeaderValue($headers[strtolower(self::HEADER)] ?? null);
        $signature = self::singleHeaderValue($headers['signature'] ?? null);

        if ($raw === null || $signature === null) {
            return;
        }

        $signatureData = HttpSignature::parseSignatureHeader($signature);

        if (isset($signatureData['error']) || ! isset($signatureData['keyId'], $signatureData['headers'])) {
            return;
        }

        $signed = preg_split('/\s+/', strtolower(trim($signatureData['headers']))) ?: [];

        if (! in_array(strtolower(self::HEADER), $signed, true)) {
            return;
        }

        $params = self::parseHeader($raw);

        if (! $params) {
            return;
        }

        $keyId = Helpers::validateUrl($signatureData['keyId']);

        if (! $keyId) {
            return;
        }

        $sender = Profile::whereKeyId($keyId)
            ->whereNotNull('domain')
            ->first();

        if (! $sender || $sender->status !== null) {
            return;
        }

        $authority = FollowersSyncService::authority($sender->remote_url);

        if (
            ! $authority
            || $authority === FollowersSyncService::localAuthority()
            || FollowersSyncService::authority($params['url']) !== $authority
        ) {
            return;
        }

        self::rememberSyncUrl($authority, $params['url']);

        if (hash_equals($params['digest'], self::receivedDigest($authority))) {
            return;
        }

        self::rememberSignedDigest($authority, $params['url'], $params['digest']);

        $cooldown = max(60, (int) config('federation.activitypub.block_sync.cooldown', 900));

        if (! Cache::add(self::COOLDOWN_KEY.hash('sha256', $authority), 1, $cooldown)) {
            return;
        }

        BlockSyncPipeline::dispatch($authority, $params['url'])->onQueue('follow');
    }

    public static function rememberSignedDigest(string $authority, string $url, string $digest): void
    {
        Cache::put(self::SIGNED_DIGEST_KEY.hash('sha256', $authority), [
            'url' => $url,
            'digest' => $digest,
        ], self::SIGNED_DIGEST_TTL);
    }

    public static function pullSignedDigest(string $authority): ?array
    {
        $value = Cache::pull(self::SIGNED_DIGEST_KEY.hash('sha256', $authority));

        if (
            ! is_array($value)
            || ! is_string($value['url'] ?? null)
            || ! is_string($value['digest'] ?? null)
            || FollowersSyncService::authority($value['url']) !== $authority
            || ! preg_match('/^[0-9a-f]{64}$/', $value['digest'])
        ) {
            return null;
        }

        return ['url' => $value['url'], 'digest' => $value['digest']];
    }

    public static function backOff(string $authority): void
    {
        Cache::put(self::COOLDOWN_KEY.hash('sha256', $authority), 1, self::FAILURE_COOLDOWN);
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

    public static function synchronize(string $authority, string $url, ?string $expectedDigest = null, ?float $deadline = null): array
    {
        $result = [
            'status' => 'skipped',
            'added' => 0,
            'removed' => 0,
            'unresolved' => 0,
        ];

        if ($expectedDigest !== null) {
            $expectedDigest = strtolower($expectedDigest);
        }

        if (
            ! self::receiving()
            || FollowersSyncService::authority($authority) !== $authority
            || $authority === FollowersSyncService::localAuthority()
            || FollowersSyncService::authority($url) !== $authority
            || ($expectedDigest !== null && ! preg_match('/^[0-9a-f]{64}$/', $expectedDigest))
        ) {
            return $result;
        }

        $host = strtolower((string) parse_url($authority, PHP_URL_HOST));

        if (in_array($host, (array) InstanceService::getBannedDomains(), true)) {
            return $result;
        }

        if ($expectedDigest !== null && hash_equals($expectedDigest, self::receivedDigest($authority, true))) {
            $result['status'] = 'in_sync';

            return $result;
        }

        $collection = self::fetchCollection($url, $authority, $deadline);

        if ($collection === null) {
            $result['status'] = 'fetch_failed';

            return $result;
        }

        $items = $collection['items'];

        $canRemove = $collection['complete']
            && ! $collection['malformed']
            && $collection['digest'] !== null
            && hash_equals($collection['digest'], self::digest($items));

        $segments = [];
        $pairs = [];

        foreach ($items as $pair) {
            $segment = FollowersSyncService::localActorSegment($pair['object']);

            if (FollowersSyncService::authority($pair['actor']) !== $authority || $segment === null) {
                $canRemove = false;

                continue;
            }

            $segments[] = $segment;
            $pairs[] = $pair + ['segment' => $segment];
        }

        $localBySegment = [];

        foreach (FollowersSyncService::resolveLocalActorSegments($segments) as $profile) {
            $localBySegment[strtolower((string) $profile->username)] = $profile;
            $localBySegment[(string) $profile->id] = $profile;
        }

        $remoteByUrl = self::resolveRemoteActors(
            array_values(array_unique(array_column($pairs, 'actor'))),
            $deadline
        );

        $desired = [];

        foreach ($pairs as $pair) {
            $local = $localBySegment[strtolower($pair['segment'])] ?? $localBySegment[$pair['segment']] ?? null;

            if (! $local) {
                $canRemove = false;

                continue;
            }

            $remote = $remoteByUrl[$pair['actor']] ?? null;

            if (! $remote) {
                $result['unresolved']++;

                continue;
            }

            $desired[$remote->id.':'.$local->id] = [$remote, $local];
        }

        $known = self::receivedBlocks($authority);

        foreach ($desired as $key => [$remote, $local]) {
            if ($known->has($key)) {
                continue;
            }

            if ($result['added'] >= self::MAX_APPLY_PER_RUN) {
                break;
            }

            if (self::applyRemoteBlock($remote, $local)) {
                $result['added']++;
            }
        }

        if ($canRemove) {
            $graceCutoff = now()->subMinutes(self::REMOVAL_GRACE_MINUTES);

            foreach ($known as $key => $row) {
                if (isset($desired[$key])) {
                    continue;
                }

                if ($row['created_at'] !== null && Carbon::parse($row['created_at'])->gt($graceCutoff)) {
                    continue;
                }

                if (self::removeRemoteBlockById($row['id'], $row['blocker_id'], $row['blocked_id'])) {
                    $result['removed']++;
                }
            }
        }

        if ($result['added'] || $result['removed']) {
            self::forgetReceivedDigest($authority);
        }

        $result['status'] = $canRemove ? 'synchronized' : 'synchronized_without_removals';

        if ($result['added'] || $result['removed']) {
            Log::info('BlockSync: reconciled blocks from remote instance', [
                'authority' => $authority,
            ] + $result);
        }

        return $result;
    }

    protected static function resolveRemoteActors(array $actorUrls, ?float $deadline = null): array
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
            if (isset($resolved[$url])) {
                continue;
            }

            if ($fetched >= self::MAX_NEW_ACTORS_PER_RUN || self::pastDeadline($deadline)) {
                break;
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

    public static function fetchCollection(string $url, string $authority, ?float $deadline = null): ?array
    {
        $maxPages = max(1, (int) config('federation.activitypub.block_sync.max_pages', 10));

        $document = self::fetchDocument($url, $authority);

        if (! is_array($document) || ! self::isCollection($document)) {
            return null;
        }

        $digest = self::collectionDigest($document);

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

                if (++$pages > $maxPages || self::pastDeadline($deadline)) {
                    return ['items' => $items, 'complete' => false, 'malformed' => $malformed, 'digest' => $digest];
                }

                $seen[$page] = true;
                $page = self::fetchDocument($page, $authority);
            }

            if (! is_array($page) || ! self::isCollection($page)) {
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

        return ['items' => $items, 'complete' => true, 'malformed' => $malformed, 'digest' => $digest];
    }

    public static function collectionDigest(array $document): ?string
    {
        $value = $document['blockSynchronizationDigest'] ?? $document[self::DIGEST_CONTEXT_TERM] ?? null;

        if (! is_string($value)) {
            return null;
        }

        $value = strtolower($value);

        return preg_match('/^[0-9a-f]{64}$/', $value) ? $value : null;
    }

    private static function pastDeadline(?float $deadline): bool
    {
        return $deadline !== null && microtime(true) >= $deadline;
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

    public static function applyRemoteBlock(Profile $blocker, Profile $blocked): bool
    {
        if ($blocker->domain === null || $blocked->domain !== null || $blocker->id === $blocked->id) {
            return false;
        }

        $exists = UserFilter::whereUserId($blocker->id)
            ->whereFilterableId($blocked->id)
            ->whereFilterableType(Profile::class)
            ->whereFilterType('block')
            ->exists();

        if ($exists) {
            return false;
        }

        $follows = Follower::where(function ($query) use ($blocker, $blocked) {
            $query->where('profile_id', $blocked->id)->where('following_id', $blocker->id);
        })->orWhere(function ($query) use ($blocker, $blocked) {
            $query->where('profile_id', $blocker->id)->where('following_id', $blocked->id);
        })->get();

        foreach ($follows as $follow) {
            $profileId = $follow->profile_id;
            $followingId = $follow->following_id;

            $follow->delete();

            UnfollowPipeline::dispatch($profileId, $followingId)->onQueue('high');
        }

        FollowRequest::where(function ($query) use ($blocker, $blocked) {
            $query->where('follower_id', $blocked->id)->where('following_id', $blocker->id);
        })->orWhere(function ($query) use ($blocker, $blocked) {
            $query->where('follower_id', $blocker->id)->where('following_id', $blocked->id);
        })->delete();

        UserFilter::firstOrCreate([
            'user_id' => $blocker->id,
            'filterable_id' => $blocked->id,
            'filterable_type' => Profile::class,
            'filter_type' => 'block',
        ]);

        RelationshipService::refresh($blocked->id, $blocker->id);

        self::forgetReceivedDigest(FollowersSyncService::authority($blocker->remote_url));

        return true;
    }

    public static function removeRemoteBlock(Profile $blocker, Profile $blocked): bool
    {
        if ($blocker->domain === null || $blocked->domain !== null) {
            return false;
        }

        $filter = UserFilter::whereUserId($blocker->id)
            ->whereFilterableId($blocked->id)
            ->whereFilterableType(Profile::class)
            ->whereFilterType('block')
            ->first();

        if (! $filter) {
            return false;
        }

        $filter->delete();

        RelationshipService::refresh($blocked->id, $blocker->id);

        self::forgetReceivedDigest(FollowersSyncService::authority($blocker->remote_url));

        return true;
    }

    protected static function removeRemoteBlockById(int $filterId, int $blockerId, int $blockedId): bool
    {
        $filter = UserFilter::whereKey($filterId)
            ->whereUserId($blockerId)
            ->whereFilterableId($blockedId)
            ->whereFilterableType(Profile::class)
            ->whereFilterType('block')
            ->first();

        if (! $filter) {
            return false;
        }

        $filter->delete();

        RelationshipService::refresh($blockedId, $blockerId);

        return true;
    }

    private static function singleHeaderValue(mixed $value): ?string
    {
        if (is_array($value)) {
            if (count($value) !== 1) {
                return null;
            }

            $value = reset($value);
        }

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }
}
