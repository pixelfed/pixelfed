<?php

namespace App\Services;

use App\Http\Controllers\FollowerController;
use App\Jobs\FollowPipeline\FollowersSyncPipeline;
use App\Jobs\FollowPipeline\FollowPipeline;
use App\Jobs\FollowPipeline\UnfollowPipeline;
use App\Models\Follower;
use App\Models\FollowRequest;
use App\Models\Profile;
use App\Util\ActivityPub\Helpers;
use App\Util\ActivityPub\HttpSignature;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * FEP-8fcf: Followers collection synchronization across servers.
 *
 * https://codeberg.org/fediverse/fep/src/branch/main/fep/8fcf/fep-8fcf.md
 *
 * Sender side:   outboundDigests() / header() / partialFollowers()
 * Receiver side: handleInboundHeaders() / synchronize()
 *
 * Everything that compares actor ids works on "authorities" (scheme + host
 * + non default port), as the FEP defines the partial collection by URI
 * scheme and authority, not by hostname alone.
 */
class FollowersSyncService
{
    const HEADER = 'Collection-Synchronization';

    const EMPTY_DIGEST = '0000000000000000000000000000000000000000000000000000000000000000';

    const DIGEST_CACHE_KEY = 'pf:services:followers-sync:digests:v1:';

    const DIGEST_CACHE_TTL = 21600;

    const COOLDOWN_KEY = 'pf:services:followers-sync:cooldown:';

    const FOLLOWERS_URL_MISS_KEY = 'pf:services:followers-sync:no-followers-url:';

    /**
     * Follows younger than this are never removed by a synchronization, so
     * an Accept that is still in flight cannot be undone by a list that was
     * generated moments earlier.
     */
    const REMOVAL_GRACE_MINUTES = 10;

    const MAX_ITEMS = 100000;

    const MAX_UNDO_PER_RUN = 100;

    const COLLECTION_TYPES = [
        'Collection',
        'OrderedCollection',
        'CollectionPage',
        'OrderedCollectionPage',
    ];

    public static function enabled(): bool
    {
        return (bool) config('federation.activitypub.followers_sync.enabled', true);
    }

    /**
     * URI scheme and authority of a URL, normalized: lowercase scheme and
     * host, default ports dropped. Null for anything that is not http(s).
     */
    public static function authority(mixed $url): ?string
    {
        if (! is_string($url) || trim($url) === '') {
            return null;
        }

        $parts = parse_url(trim($url));

        if (! is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
            return null;
        }

        if (isset($parts['user']) || isset($parts['pass'])) {
            return null;
        }

        $scheme = strtolower($parts['scheme']);

        if (! in_array($scheme, ['http', 'https'], true)) {
            return null;
        }

        $authority = $scheme.'://'.strtolower($parts['host']);

        if (isset($parts['port'])) {
            $port = (int) $parts['port'];
            $default = $scheme === 'https' ? 443 : 80;

            if ($port !== $default) {
                $authority .= ':'.$port;
            }
        }

        return $authority;
    }

    /**
     * Authority of this instance, taken from the URL generator so it always
     * agrees with the actor ids produced by Profile::permalink().
     */
    public static function localAuthority(): ?string
    {
        return self::authority(url('/'));
    }

    /**
     * Partial follower collection digest: the SHA256 digests of every
     * follower id XORed together, hex encoded.
     *
     * @param  iterable<int, mixed>  $ids
     */
    public static function digest(iterable $ids): string
    {
        $acc = str_repeat("\0", 32);

        foreach ($ids as $id) {
            if (! is_string($id) || $id === '') {
                continue;
            }

            $acc ^= hash('sha256', $id, true);
        }

        return bin2hex($acc);
    }

    /**
     * Parse a Collection-Synchronization header value. It reuses the
     * `signature` parameter syntax of draft-cavage HTTP signatures.
     *
     * @return array{collectionId: string, url: string, digest: string}|null
     */
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

        foreach (['collectionId', 'url', 'digest'] as $required) {
            if (! isset($params[$required]) || $params[$required] === '') {
                return null;
            }
        }

        $digest = strtolower($params['digest']);

        if (! preg_match('/^[0-9a-f]{64}$/', $digest)) {
            return null;
        }

        return [
            'collectionId' => $params['collectionId'],
            'url' => $params['url'],
            'digest' => $digest,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Local actor ids
    |--------------------------------------------------------------------------
    |
    | Digests are computed over actor ids exactly as the remote server knows
    | them. These two methods are the only place that turns a local profile
    | into an actor id and back, so a change of the actor URL scheme only has
    | to be reflected here. Reconciliation always compares profile ids, never
    | URL strings, so a remote that still lists an older URL form of a local
    | actor can at worst cause a redundant fetch, never a removal.
    |
    */

    public static function localActorId(Profile $profile): string
    {
        return $profile->permalink();
    }

    /**
     * The `/users/{segment}` segment of a local actor id, or null when the
     * URL is not a recognized local actor id.
     */
    public static function localActorSegment(mixed $uri): ?string
    {
        $local = self::localAuthority();

        if (! $local || self::authority($uri) !== $local) {
            return null;
        }

        $parts = parse_url(trim($uri));

        if (isset($parts['query']) || isset($parts['fragment'])) {
            return null;
        }

        if (! preg_match('#^/users/([A-Za-z0-9_.\-]{1,64})/?$#', $parts['path'] ?? '', $match)) {
            return null;
        }

        return $match[1];
    }

    public static function resolveLocalActor(mixed $uri): ?Profile
    {
        $segment = self::localActorSegment($uri);

        if ($segment === null) {
            return null;
        }

        return self::resolveLocalActorSegments([$segment])->first();
    }

    /**
     * Resolve many `/users/{segment}` segments at once.
     *
     * @param  array<int, string>  $segments
     * @return Collection<int, Profile> Keyed by profile id
     */
    public static function resolveLocalActorSegments(array $segments): Collection
    {
        $columns = ['id', 'user_id', 'username', 'domain', 'remote_url', 'status'];
        $segments = array_values(array_unique(array_map('strval', $segments)));
        $resolved = collect();

        foreach (array_chunk($segments, 500) as $chunk) {
            $profiles = Profile::whereNull('domain')
                ->whereIn('username', $chunk)
                ->get($columns);

            $matched = $profiles
                ->map(fn (Profile $profile) => strtolower($profile->username))
                ->all();

            // Id based actor URLs (/users/{id}), accepted alongside usernames.
            $ids = array_values(array_filter(
                $chunk,
                fn (string $segment) => ctype_digit($segment) && ! in_array(strtolower($segment), $matched, true)
            ));

            if (! empty($ids)) {
                $profiles = $profiles->concat(
                    Profile::whereNull('domain')->whereIn('id', $ids)->get($columns)
                );
            }

            foreach ($profiles as $profile) {
                $resolved->put($profile->id, $profile);
            }
        }

        return $resolved;
    }

    /**
     * Digest of every partial followers collection of a local profile,
     * keyed by authority. Computed with a single pass over the remote
     * followers and cached, so a delivery to many inboxes costs one lookup.
     *
     * @return array<string, string>
     */
    public static function outboundDigests(Profile $profile): array
    {
        if ($profile->domain !== null) {
            return [];
        }

        return Cache::remember(
            self::DIGEST_CACHE_KEY.$profile->id,
            self::DIGEST_CACHE_TTL,
            function () use ($profile) {
                $acc = [];

                self::remoteFollowersQuery($profile->id)
                    ->select('id', 'remote_url')
                    ->chunkById(5000, function ($rows) use (&$acc) {
                        foreach ($rows as $row) {
                            $authority = self::authority($row->remote_url);

                            if (! $authority) {
                                continue;
                            }

                            $acc[$authority] = ($acc[$authority] ?? str_repeat("\0", 32))
                                ^ hash('sha256', $row->remote_url, true);
                        }
                    }, 'id');

                return array_map('bin2hex', $acc);
            }
        );
    }

    public static function forgetOutboundDigests(mixed $profileId): void
    {
        Cache::forget(self::DIGEST_CACHE_KEY.$profileId);
    }

    /**
     * Collection-Synchronization header value for a delivery of $profile to
     * $inboxUrl, or null when no header should be sent.
     *
     * @param  array<string, string>|null  $digests  Result of outboundDigests(), to avoid a cache lookup per inbox
     */
    public static function header(Profile $profile, string $inboxUrl, ?array $digests = null): ?string
    {
        if (! self::enabled() || $profile->domain !== null) {
            return null;
        }

        $authority = self::authority($inboxUrl);

        if (! $authority || $authority === self::localAuthority()) {
            return null;
        }

        $digests ??= self::outboundDigests($profile);

        return sprintf(
            'collectionId="%s", url="%s", digest="%s"',
            $profile->permalink('/followers'),
            $profile->permalink('/followers_synchronization'),
            $digests[$authority] ?? self::EMPTY_DIGEST
        );
    }

    /**
     * Partial followers collection of a local profile for one remote
     * instance: the ids of its followers that share $authority.
     *
     * @return array<int, string>
     */
    public static function partialFollowers(Profile $profile, string $authority): array
    {
        $host = parse_url($authority, PHP_URL_HOST);

        if (! $host || $profile->domain !== null) {
            return [];
        }

        return self::remoteFollowersQuery($profile->id)
            ->where('domain', strtolower($host))
            ->pluck('remote_url')
            ->filter(fn ($url) => self::authority($url) === $authority)
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /**
     * Remote followers of a local profile. Built on the profiles table so
     * duplicate follower rows cannot cancel each other out of the XOR.
     */
    private static function remoteFollowersQuery(mixed $profileId)
    {
        return DB::table('profiles')
            ->whereNotNull('domain')
            ->whereNotNull('remote_url')
            ->whereNull('deleted_at')
            ->whereIn('id', function ($query) use ($profileId) {
                $query->select('profile_id')
                    ->from('followers')
                    ->where('following_id', $profileId);
            });
    }

    /**
     * Inspect the headers of a delivery whose HTTP signature has already
     * been verified, and queue a synchronization when the sender's digest
     * disagrees with our local copy of its followers collection.
     *
     * Never throws: a synchronization problem must not block the inbox.
     *
     * @param  array<string, mixed>  $headers  Request headers as returned by $request->headers->all()
     */
    public static function handleInboundHeaders(mixed $headers): void
    {
        try {
            self::processInboundHeaders($headers);
        } catch (Throwable $e) {
            Log::debug('FollowersSync: unable to process Collection-Synchronization header', [
                'exception' => $e::class,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private static function processInboundHeaders(mixed $headers): void
    {
        if (! self::enabled() || ! is_array($headers)) {
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

        // The FEP only considers a *signed* Collection-Synchronization header.
        $signed = preg_split('/\s+/', strtolower(trim($signatureData['headers']))) ?: [];

        if (! in_array(strtolower(self::HEADER), $signed, true)) {
            return;
        }

        $params = self::parseHeader($raw);

        if (! $params) {
            return;
        }

        /*
         * The sender is whoever signed the request, not payload.actor: the
         * user inbox only requires both to share a host.
         */
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

        if (! self::senderMatches($sender, $params['collectionId'], $params['url'])) {
            return;
        }

        /*
         * Reject a wrong collectionId right away when we know the sender's
         * followers collection. When we don't know it yet, the queued job
         * resolves and checks it, keeping remote fetches out of this path.
         */
        if ($sender->followers_url && $sender->followers_url !== $params['collectionId']) {
            return;
        }

        if (hash_equals($params['digest'], self::localFollowerDigest($sender))) {
            return;
        }

        $cooldown = max(60, (int) config('federation.activitypub.followers_sync.cooldown', 900));

        if (! Cache::add(self::COOLDOWN_KEY.$sender->id, 1, $cooldown)) {
            return;
        }

        FollowersSyncPipeline::dispatch(
            $sender->id,
            $params['collectionId'],
            $params['url'],
            $params['digest']
        )->onQueue('follow');
    }

    /**
     * Both the collection and the synchronization URL must live on the
     * sender's own authority, so an instance cannot be tricked into
     * requesting the followers of a third party.
     */
    public static function senderMatches(Profile $sender, string $collectionId, string $url): bool
    {
        $authority = self::authority($sender->remote_url);

        return $authority !== null
            && $authority !== self::localAuthority()
            && self::authority($collectionId) === $authority
            && self::authority($url) === $authority;
    }

    /**
     * Local profiles that follow a remote profile, according to our database.
     *
     * @return Collection<int, Profile>
     */
    public static function localFollowerProfiles(Profile $remote): Collection
    {
        return Profile::whereNull('domain')
            ->whereIn('id', function ($query) use ($remote) {
                $query->select('profile_id')
                    ->from('followers')
                    ->where('following_id', $remote->id);
            })
            ->get(['id', 'user_id', 'username', 'domain', 'remote_url', 'status']);
    }

    public static function localFollowerDigest(Profile $remote): string
    {
        return self::digest(
            self::localFollowerProfiles($remote)
                ->map(fn (Profile $profile) => self::localActorId($profile))
        );
    }

    /**
     * Followers collection id advertised by a remote actor document. Only
     * accepted when it lives on the actor's own authority.
     *
     * @param  array<string, mixed>  $actor
     */
    public static function followersUrlFromActor(array $actor): ?string
    {
        $followers = $actor['followers'] ?? null;

        if (is_array($followers)) {
            $followers = $followers['id'] ?? null;
        }

        if (! is_string($followers) || $followers === '' || strlen($followers) > 255) {
            return null;
        }

        $authority = self::authority($followers);

        if (! $authority || $authority !== self::authority($actor['id'] ?? null)) {
            return null;
        }

        return $followers;
    }

    /**
     * Followers collection id of a remote profile, fetching the actor once
     * to backfill profiles that were ingested before the column existed.
     *
     * May perform a remote request: only call this from a queued job.
     */
    public static function followersUrlFor(Profile $remote): ?string
    {
        if ($remote->followers_url) {
            return $remote->followers_url;
        }

        if (! $remote->remote_url || Cache::has(self::FOLLOWERS_URL_MISS_KEY.$remote->id)) {
            return null;
        }

        $actor = Helpers::fetchProfileFromUrl($remote->remote_url);

        $url = is_array($actor) && ($actor['id'] ?? null) === $remote->remote_url
            ? self::followersUrlFromActor($actor)
            : null;

        if (! $url) {
            Cache::put(self::FOLLOWERS_URL_MISS_KEY.$remote->id, 1, 86400);

            return null;
        }

        DB::table('profiles')
            ->where('id', $remote->id)
            ->update(['followers_url' => $url]);

        $remote->followers_url = $url;
        $remote->syncOriginalAttribute('followers_url');

        return $url;
    }

    /**
     * Fetch the partial followers collection from the authoritative server
     * and reconcile our local copy with it.
     *
     * @return array{status: string, removed: int, accepted: int, undone: int}
     */
    public static function synchronize(Profile $sender, string $collectionId, string $url, string $expectedDigest): array
    {
        $result = [
            'status' => 'skipped',
            'removed' => 0,
            'accepted' => 0,
            'undone' => 0,
        ];

        $expectedDigest = strtolower($expectedDigest);

        if (
            ! self::enabled()
            || $sender->domain === null
            || $sender->status !== null
            || ! preg_match('/^[0-9a-f]{64}$/', $expectedDigest)
            || ! self::senderMatches($sender, $collectionId, $url)
        ) {
            return $result;
        }

        if (self::followersUrlFor($sender) !== $collectionId) {
            $result['status'] = 'collection_mismatch';

            return $result;
        }

        if (hash_equals($expectedDigest, self::localFollowerDigest($sender))) {
            $result['status'] = 'in_sync';

            return $result;
        }

        $collection = self::fetchCollection($url, (string) self::authority($sender->remote_url));

        /*
         * A failed or malformed response is never treated as an empty list.
         */
        if ($collection === null) {
            $result['status'] = 'fetch_failed';

            return $result;
        }

        /*
         * Removing followers is destructive, so it only happens when the
         * fetched list is complete, hashes to the digest the sender signed,
         * and contains no local URL we are unable to interpret.
         */
        $canRemove = $collection['complete']
            && hash_equals($expectedDigest, self::digest($collection['items']));

        $segments = [];

        foreach (array_unique($collection['items']) as $uri) {
            $segment = self::localActorSegment($uri);

            /*
             * A partial collection only holds actors of this instance. An
             * entry we cannot map to a local actor id means the response is
             * not what we think it is, so nothing gets removed based on it.
             */
            if ($segment === null) {
                $canRemove = false;

                continue;
            }

            $segments[] = $segment;
        }

        $listed = self::resolveLocalActorSegments($segments);

        $known = self::localFollowerProfiles($sender)->keyBy('id');

        if ($canRemove) {
            $stale = $known->keys()->diff($listed->keys())->values();

            if ($stale->isNotEmpty()) {
                $removable = Follower::whereFollowingId($sender->id)
                    ->whereIn('profile_id', $stale->all())
                    ->where(function ($query) {
                        $query->whereNull('created_at')
                            ->orWhere('created_at', '<', now()->subMinutes(self::REMOVAL_GRACE_MINUTES));
                    })
                    ->pluck('profile_id')
                    ->unique();

                foreach ($removable as $profileId) {
                    self::removeLocalFollower($profileId, $sender);
                    $result['removed']++;
                }
            }
        }

        foreach ($listed as $profileId => $profile) {
            if ($known->has($profileId)) {
                continue;
            }

            $pending = FollowRequest::whereFollowerId($profileId)
                ->whereFollowingId($sender->id)
                ->whereIsRejected(false)
                ->first();

            if ($pending) {
                self::acceptPendingFollow($pending, $profile, $sender);
                $result['accepted']++;

                continue;
            }

            // Bounded, so a remote cannot turn its own list into a delivery flood.
            if ($result['undone'] < self::MAX_UNDO_PER_RUN && self::sendUndoFollow($profileId, $sender)) {
                $result['undone']++;
            }
        }

        $result['status'] = $canRemove ? 'synchronized' : 'synchronized_without_removals';

        if ($result['removed'] || $result['accepted'] || $result['undone']) {
            Log::info('FollowersSync: reconciled followers of remote actor', [
                'profile_id' => $sender->id,
                'actor' => $sender->remote_url,
            ] + $result);
        }

        return $result;
    }

    /**
     * Fetch a (possibly paged) collection with a request signed by the
     * instance actor. Every page has to stay on the sender's authority.
     *
     * @return array{items: array<int, string>, complete: bool}|null
     */
    public static function fetchCollection(string $url, string $authority): ?array
    {
        $maxPages = max(1, (int) config('federation.activitypub.followers_sync.max_pages', 10));

        $document = self::fetchDocument($url, $authority);

        if (! self::isCollection($document)) {
            return null;
        }

        $items = [];
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
                    return ['items' => $items, 'complete' => false];
                }

                $seen[$page] = true;
                $page = self::fetchDocument($page, $authority);
            }

            if (! self::isCollection($page)) {
                return null;
            }

            $pageItems = $page['orderedItems'] ?? $page['items'] ?? [];

            if (is_string($pageItems)) {
                $pageItems = [$pageItems];
            }

            if (! is_array($pageItems)) {
                return null;
            }

            if (! array_is_list($pageItems)) {
                $pageItems = [$pageItems];
            }

            foreach ($pageItems as $item) {
                if (is_array($item)) {
                    $item = $item['id'] ?? null;
                }

                if (! is_string($item) || $item === '') {
                    return null;
                }

                $items[] = $item;
            }

            if (count($items) > self::MAX_ITEMS) {
                return null;
            }

            $page = $page['next'] ?? null;

            if ($page !== null && ! is_string($page) && ! is_array($page)) {
                return null;
            }
        }

        return ['items' => $items, 'complete' => true];
    }

    private static function fetchDocument(string $url, string $authority): ?array
    {
        if (self::authority($url) !== $authority) {
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

        $types = $document['type'] ?? null;

        foreach ((array) $types as $type) {
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

    /**
     * The remote server does not list this local follower. No Undo is sent:
     * the remote already agrees the relationship does not exist.
     */
    private static function removeLocalFollower(mixed $profileId, Profile $remote): void
    {
        Follower::whereProfileId($profileId)
            ->whereFollowingId($remote->id)
            ->delete();

        app(StoryIndexService::class)->removeFollowing((int) $profileId, (int) $remote->id);

        UnfollowPipeline::dispatch($profileId, $remote->id)->onQueue('high');

        RelationshipService::refresh($profileId, $remote->id);

        self::forgetFollowCaches($profileId, $remote->id);
    }

    /**
     * The remote server lists a local profile whose follow request is still
     * pending here: treat it as accepted, as the Accept handler would.
     */
    private static function acceptPendingFollow(FollowRequest $request, Profile $local, Profile $remote): void
    {
        $follower = Follower::firstOrCreate([
            'profile_id' => $local->id,
            'following_id' => $remote->id,
        ]);

        FollowPipeline::dispatch($follower)->onQueue('high');

        RelationshipService::refresh($local->id, $remote->id);

        self::forgetFollowCaches($local->id, $remote->id);

        $request->delete();
    }

    /**
     * The remote server lists a local profile that does not follow it here.
     */
    private static function sendUndoFollow(mixed $profileId, Profile $remote): bool
    {
        if (! config('federation.activitypub.remoteFollow')) {
            return false;
        }

        $local = Profile::whereNull('domain')
            ->whereNull('status')
            ->find($profileId);

        if (! $local || empty($local->private_key)) {
            return false;
        }

        try {
            (new FollowerController)->sendUndoFollow($local, $remote);

            return true;
        } catch (Throwable $e) {
            Log::debug('FollowersSync: unable to deliver Undo Follow', [
                'profile_id' => $local->id,
                'target_id' => $remote->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private static function forgetFollowCaches(mixed ...$profileIds): void
    {
        foreach ($profileIds as $id) {
            Cache::forget('profile:follower_count:'.$id);
            Cache::forget('profile:following_count:'.$id);
            Cache::forget('profile:following:'.$id);
            Cache::forget('profile:followers:'.$id);
            AccountService::del($id);
        }
    }

    /**
     * A header that was sent more than once is ambiguous: ignore it.
     */
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
