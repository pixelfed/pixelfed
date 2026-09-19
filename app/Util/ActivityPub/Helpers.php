<?php

namespace App\Util\ActivityPub;

use App\Jobs\AvatarPipeline\RemoteAvatarFetch;
use App\Jobs\HomeFeedPipeline\FeedInsertRemotePipeline;
use App\Jobs\InstancePipeline\FetchNodeinfoPipeline;
use App\Jobs\MediaPipeline\MediaStoragePipeline;
use App\Jobs\StatusPipeline\StatusReplyPipeline;
use App\Jobs\StatusPipeline\StatusTagsPipeline;
use App\Models\Instance;
use App\Models\Media;
use App\Models\ModeratedProfile;
use App\Models\Poll;
use App\Models\Profile;
use App\Models\Status;
use App\Services\Account\AccountStatService;
use App\Services\AccountService;
use App\Services\ActivityPubDeliveryService;
use App\Services\ActivityPubFetchService;
use App\Services\DomainService;
use App\Services\FollowersSyncService;
use App\Services\InstanceService;
use App\Services\MediaPathService;
use App\Services\NetworkTimelineService;
use App\Services\SanitizeService;
use App\Services\UserFilterService;
use App\Util\Media\License;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use League\Uri\Uri;
use Purify;

class Helpers
{
    private const string PUBLIC_TIMELINE = 'https://www.w3.org/ns/activitystreams#Public';

    private const int CACHE_TTL = 14440;

    private const string URL_CACHE_PREFIX = 'helpers:url:';

    private const int FETCH_CACHE_TTL = 15;

    /**
     * Seconds a failed fetch is remembered. Kept short on purpose: a failed
     * parent fetch used to be cached for the same 15 minutes as a success,
     * which pinned every reply to that parent as unresolvable. Two minutes
     * still absorbs a burst of activities pointing at the same dead URL.
     * RemoteReplyResolvePipeline::BACKOFF must start above this value.
     */
    public const int FETCH_NEGATIVE_TTL = 120;

    /**
     * Outcomes of resolveReplyParent().
     *
     * NONE        the object is not a reply
     * RESOLVED    the parent exists locally (it may have just been fetched)
     * UNRESOLVED  the object is a reply but the parent could not be found or
     *             fetched right now. Never store the object in this state,
     *             it would become a top-level status. Retry later.
     * REJECTED    the object is a reply we must not accept (blocked author,
     *             blocked domain, comments disabled, malformed inReplyTo).
     *             Never store, never retry.
     */
    public const string REPLY_PARENT_NONE = 'none';

    public const string REPLY_PARENT_RESOLVED = 'resolved';

    public const string REPLY_PARENT_UNRESOLVED = 'unresolved';

    public const string REPLY_PARENT_REJECTED = 'rejected';

    /**
     * Why validateUrlWithReason() accepted or rejected a url.
     *
     * These exist so a rejection is loggable. Before this, every failure
     * mode in validateUrl() collapsed into a bare false, and an admin
     * reporting "federation is broken" gave no way to tell a banned domain
     * from a dead host from a resolver that cannot answer at all.
     *
     * OK          usable, the normalized url is returned alongside
     * MALFORMED   not a parseable string url, or failed the input filters
     * URI         parsed, but not an https url we will ever talk to
     * HOST        the hostname is unusable (ip literal, single label,
     *             localhost, invalid idn)
     * BANNED      the domain is on the instance ban list
     * UNRESOLVED  no A/AAAA records came back from any resolution path.
     *             Ambiguous, see lookupHostIps().
     * PRIVATE_IP  the host positively resolves into non-global address
     *             space. This is the SSRF signal and is never allowed.
     */
    public const string URL_OK = 'ok';

    public const string URL_MALFORMED = 'malformed';

    public const string URL_URI = 'uri';

    public const string URL_HOST = 'host';

    public const string URL_BANNED = 'banned';

    public const string URL_UNRESOLVED = 'unresolved';

    public const string URL_PRIVATE_IP = 'private_ip';

    private const int MAX_URL_LENGTH = 4096;

    private const int DNS_TTL_POSITIVE = 86400;

    private const int DNS_TTL_NEGATIVE = 300;

    /**
     * An unresolved host expires quickly. Caching it for the same five
     * minutes as a confirmed private-range answer turns one resolver hiccup
     * into five minutes of dropped deliveries to a host that is actually up,
     * with DeliveryHostService recording a failure against it each time.
     */
    private const int DNS_TTL_UNRESOLVED = 60;

    /**
     * Maximum number of ancestors a single status fetch may walk up an
     * inReplyTo chain. Without a bound, a remote server that always answers
     * with another inReplyTo can hold a worker indefinitely, one outbound
     * fetch and one statuses row per hop. Anything deeper than this is not
     * rendered in the UI anyway.
     */
    private const int MAX_REPLY_DEPTH = 5;

    private const array LOCALHOST_DOMAINS = [
        'localhost',
        '127.0.0.1',
        '::1',
        'broadcasthost',
        'ip6-localhost',
        'ip6-loopback',
    ];

    /**
     * Validate an ActivityPub object
     */
    public static function validateObject(array $data): bool
    {
        $verbs = ['Create', 'Announce', 'Like', 'Follow', 'Delete', 'Accept', 'Reject', 'Undo', 'Tombstone'];

        return Validator::make($data, [
            'type' => ['required', 'string', Rule::in($verbs)],
            'id' => 'required|string',
            'actor' => 'required|string|url',
            'object' => 'required',
            'object.type' => 'required_if:type,Create',
            'object.attributedTo' => 'required_if:type,Create|url',
            'published' => 'required_if:type,Create|date',
        ])->passes();
    }

    /**
     * Validate media attachments
     */
    public static function verifyAttachments(array $data): bool
    {
        if (! isset($data['object']) || empty($data['object'])) {
            $data = ['object' => $data];
        }

        $mimeTypes = explode(',', config_cache('pixelfed.media_types'));
        $mediaTypes = in_array('video/mp4', $mimeTypes) ?
            ['Document', 'Image', 'Video'] :
            ['Document', 'Image'];

        $attachments = self::getAttachments($data);

        if (empty($attachments)) {
            return false;
        }

        return Validator::make($attachments, [
            '*.type' => ['required', 'string', Rule::in($mediaTypes)],
            '*.url' => 'required|url',
            '*.mediaType' => ['required', 'string', Rule::in($mimeTypes)],
            '*.name' => 'sometimes|nullable|string',
            '*.blurhash' => 'sometimes|nullable|string|min:6|max:164',
            '*.width' => 'sometimes|nullable|integer|min:1|max:5000',
            '*.height' => 'sometimes|nullable|integer|min:1|max:5000',
        ])->passes();
    }

    /**
     * Normalize ActivityPub audience
     */
    public static function normalizeAudience(array $data, bool $localOnly = true): ?array
    {
        if (! isset($data['to'])) {
            return null;
        }

        $audience = [
            'to' => [],
            'cc' => [],
            'scope' => 'private',
        ];

        if (is_array($data['to']) && ! empty($data['to'])) {
            foreach ($data['to'] as $to) {
                if ($to == self::PUBLIC_TIMELINE) {
                    $audience['scope'] = 'public';

                    continue;
                }
                $url = $localOnly ? self::validateLocalUrl($to) : self::validateUrl($to);
                if ($url) {
                    $audience['to'][] = $url;
                }
            }
        }

        if (is_array($data['cc']) && ! empty($data['cc'])) {
            foreach ($data['cc'] as $cc) {
                if ($cc == self::PUBLIC_TIMELINE) {
                    $audience['scope'] = 'unlisted';

                    continue;
                }
                $url = $localOnly ? self::validateLocalUrl($cc) : self::validateUrl($cc);
                if ($url) {
                    $audience['cc'][] = $url;
                }
            }
        }

        return $audience;
    }

    /**
     * Check if user is in audience
     */
    public static function userInAudience(Profile $profile, array $data): bool
    {
        $audience = self::normalizeAudience($data);
        $url = $profile->permalink();

        return in_array($url, $audience['to']) || in_array($url, $audience['cc']);
    }

    /**
     * Validate a URL that may be used for federation, reporting why when it
     * is rejected.
     *
     * Order matters. Cheap syntactic checks first, then the local-domain
     * short circuit, then the instance ban list, and only then anything
     * that depends on a working resolver.
     *
     * @return array{url: ?string, reason: string}
     */
    public static function validateUrlWithReason(
        mixed $url,
        bool $disableDNSCheck = false,
        bool $forceBanCheck = false
    ): array {
        $url = self::normalizeUrl($url);

        if (! $url) {
            return self::urlResult(null, self::URL_MALFORMED);
        }

        try {
            $uri = Uri::new($url);
        } catch (\Throwable) {
            return self::urlResult(null, self::URL_MALFORMED);
        }

        if (! self::isValidUri($uri)) {
            return self::urlResult(null, self::URL_URI);
        }

        // Urls on our own domain are trusted by definition and skip the ban
        // list and resolution below. The app domain frequently does not
        // resolve to a globally routable address from inside the app
        // container (docker networks, split-horizon dns, CGNAT), and failing
        // it here breaks local audience normalization and local actor
        // resolution on otherwise healthy instances.
        if (self::shouldSkipLocalChecks() && self::isAppDomain($uri->getHost())) {
            $localHost = self::normalizeHostLoose($uri->getHost());

            if (! $localHost) {
                return self::urlResult(null, self::URL_HOST);
            }

            try {
                return self::urlResult(
                    $uri->withHost($localHost)->toString(),
                    self::URL_OK
                );
            } catch (\Throwable) {
                return self::urlResult(null, self::URL_HOST);
            }
        }

        $host = self::normalizeHost($uri->getHost());

        if (! $host) {
            return self::urlResult(null, self::URL_HOST);
        }

        try {
            $uri = $uri->withHost($host);
        } catch (\Throwable) {
            return self::urlResult(null, self::URL_HOST);
        }

        if ($forceBanCheck || self::shouldCheckBans()) {
            if (self::isHostBanned($host)) {
                return self::urlResult(null, self::URL_BANNED);
            }
        }

        if ($disableDNSCheck === true) {
            return self::urlResult($uri->toString(), self::URL_OK);
        }

        $resolved = self::resolveHostIps($host);

        // A host that positively resolves into non-global address space is
        // refused no matter what. This is the check that actually does work.
        if ($resolved['state'] === self::URL_PRIVATE_IP) {
            return self::urlResult(null, self::URL_PRIVATE_IP);
        }

        if (
            $resolved['state'] !== self::URL_OK &&
            ! self::allowUnresolvedHosts()
        ) {
            return self::urlResult(null, self::URL_UNRESOLVED);
        }

        return self::urlResult($uri->toString(), self::URL_OK);
    }

    /**
     * @return array{url: ?string, reason: string}
     */
    private static function urlResult(?string $url, string $reason): array
    {
        return ['url' => $url, 'reason' => $reason];
    }

    /**
     * Validate a URL that may be used for federation.
     *
     * Thin wrapper over validateUrlWithReason() for the many callers that
     * only need a usable url or false. Anything that logs a rejection should
     * call validateUrlWithReason() directly so the log says why.
     */
    public static function validateUrl(
        mixed $url,
        bool $disableDNSCheck = false,
        bool $forceBanCheck = false
    ): string|bool {
        return self::validateUrlWithReason(
            $url,
            $disableDNSCheck,
            $forceBanCheck
        )['url'] ?? false;
    }

    /**
     * Whether url validation may bypass resolution and ban checks for urls
     * on this instance's own domain.
     */
    public static function shouldSkipLocalChecks(): bool
    {
        return (bool) config('federation.url_validation.skip_local_checks', true);
    }

    /**
     * Whether a host that returned no records at all may still be used.
     *
     * Defaults to true. An empty answer is far more often a container with
     * no usable resolver than a hostile url, and failing closed there takes
     * the whole instance off the network. Hosts that positively resolve into
     * non-global space are rejected regardless of this setting, and
     * normalizeHost() has already refused ip literals, single label hosts
     * and the loopback names before resolution is ever attempted.
     */
    public static function allowUnresolvedHosts(): bool
    {
        return (bool) config('federation.url_validation.allow_unresolved', true);
    }

    /**
     * Hosts that belong to this instance.
     *
     * APP_URL is the canonical source. APP_DOMAIN is included because
     * deployments are free to set it to a different value than the url host,
     * and both forms appear in locally generated uris.
     *
     * @return array<int, string>
     */
    public static function localDomains(): array
    {
        $domains = [];

        foreach ([config('app.url'), config('pixelfed.domain.app')] as $candidate) {
            $host = self::hostFromSetting($candidate);

            if ($host !== null) {
                $domains[$host] = true;
            }
        }

        return array_keys($domains);
    }

    /**
     * Whether a host is one of this instance's own domains.
     */
    public static function isAppDomain(?string $host): bool
    {
        $host = self::normalizeHostLoose($host);

        if ($host === null) {
            return false;
        }

        return in_array($host, self::localDomains(), true);
    }

    /**
     * Pull a host out of a config value.
     *
     * Admins set these by hand, so accept what they actually write:
     * APP_DOMAIN=https://example.com/ and APP_DOMAIN=example.com:8443 are
     * both common, and treating either as a literal hostname silently
     * disables the local skip for the people who most need it.
     */
    private static function hostFromSetting(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        if ($value === '') {
            return null;
        }

        if (str_contains($value, '://')) {
            $value = (string) parse_url($value, PHP_URL_HOST);
        } else {
            $value = explode('/', $value, 2)[0];

            // Strip a port, but leave bare ipv6 literals alone.
            if (substr_count($value, ':') === 1) {
                $value = explode(':', $value, 2)[0];
            }
        }

        return self::normalizeHostLoose($value);
    }

    /**
     * Normalize URL input
     */
    public static function normalizeUrl(mixed $url): ?string
    {
        if (is_array($url)) {
            $url = $url[0] ?? null;
        }

        if (! is_string($url)) {
            return null;
        }

        $url = trim($url);

        if ($url === '' || strlen($url) > self::MAX_URL_LENGTH) {
            return null;
        }

        if (preg_match('/[\x00-\x20\x7f]/', $url)) {
            return null;
        }

        if (str_contains($url, '\\')) {
            return null;
        }

        return $url;
    }

    /**
     * Validate basic URI requirements
     */
    public static function isValidUri(Uri $uri): bool
    {
        if (! $uri) {
            return false;
        }

        if (strtolower((string) $uri->getScheme()) !== 'https') {
            return false;
        }

        if (! $uri->getHost()) {
            return false;
        }

        $userInfo = $uri->getUserInfo();

        if ($userInfo !== null && $userInfo !== '') {
            return false;
        }

        $port = $uri->getPort();

        if ($port !== null && ($port < 1 || $port > 65535)) {
            return false;
        }

        return true;
    }

    /**
     * Case, trailing dot and punycode normalization only.
     *
     * Deliberately does not apply the routable-host rules in
     * normalizeHost(): an instance may legitimately live on a single label
     * host, an ip literal or a name that only resolves internally, and
     * comparing such a host to the app domain must still work.
     */
    public static function normalizeHostLoose(?string $host): ?string
    {
        if (! is_string($host)) {
            return null;
        }

        $host = strtolower(rtrim(trim($host), '.'));

        if ($host === '' || strlen($host) > 253) {
            return null;
        }

        if (preg_match('/[^\x00-\x7f]/', $host)) {
            if (! function_exists('idn_to_ascii')) {
                return null;
            }

            $host = idn_to_ascii(
                $host,
                IDNA_DEFAULT,
                INTL_IDNA_VARIANT_UTS46
            );

            if (! $host) {
                return null;
            }

            $host = strtolower(rtrim($host, '.'));
        }

        return $host === '' ? null : $host;
    }

    /**
     * Normalize a host that we intend to make outbound requests to.
     */
    public static function normalizeHost(?string $host): ?string
    {
        $host = self::normalizeHostLoose($host);

        if ($host === null) {
            return null;
        }

        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return null;
        }

        if (! filter_var(
            $host,
            FILTER_VALIDATE_DOMAIN,
            FILTER_FLAG_HOSTNAME
        )) {
            return null;
        }

        if (! str_contains($host, '.')) {
            return null;
        }

        if (in_array($host, self::LOCALHOST_DOMAINS, true)) {
            return null;
        }

        return $host;
    }

    /**
     * Resolve a host to its A/AAAA records.
     *
     * Three outcomes, not two. A lookup that returns nothing is ambiguous:
     * NXDOMAIN, SERVFAIL, a resolver the container cannot reach, or a libc
     * whose res_* functions PHP cannot use (musl/Alpine images, where
     * dns_get_record() fails for every host while getaddrinfo works fine).
     * A record that resolves into non-global address space is unambiguous.
     * Only the second is a reason to refuse the url outright.
     *
     * @return array{state: string, ips: array<int, string>}
     */
    private static function lookupHostIps(string $host): array
    {
        // No trailing dot. glibc tolerates the fully qualified form, musl
        // and some resolvers return nothing for it.
        $records = @dns_get_record($host, DNS_A | DNS_AAAA);

        $ips = [];

        if (is_array($records)) {
            foreach ($records as $record) {
                $ip = $record['ip'] ?? $record['ipv6'] ?? null;

                if (! is_string($ip) || $ip === '' || isset($ips[$ip])) {
                    continue;
                }

                if (! self::isPublicIp($ip)) {
                    return ['state' => self::URL_PRIVATE_IP, 'ips' => []];
                }

                $ips[$ip] = true;
            }
        }

        if ($ips !== []) {
            return ['state' => self::URL_OK, 'ips' => array_keys($ips)];
        }

        // dns_get_record() goes through libc res_*, which is unavailable or
        // broken on musl and needs a resolv.conf the container may not have.
        // gethostbynamel() goes through getaddrinfo, the same path cURL uses
        // to connect. IPv4 only, which is why it is the fallback and not the
        // primary.
        $fallback = @gethostbynamel($host);

        if (! is_array($fallback) || $fallback === []) {
            return ['state' => self::URL_UNRESOLVED, 'ips' => []];
        }

        foreach ($fallback as $ip) {
            if (! is_string($ip) || $ip === '' || isset($ips[$ip])) {
                continue;
            }

            if (! self::isPublicIp($ip)) {
                return ['state' => self::URL_PRIVATE_IP, 'ips' => []];
            }

            $ips[$ip] = true;
        }

        return $ips === []
            ? ['state' => self::URL_UNRESOLVED, 'ips' => []]
            : ['state' => self::URL_OK, 'ips' => array_keys($ips)];
    }

    /**
     * Cached host resolution.
     *
     * @return array{state: string, ips: array<int, string>}
     */
    public static function resolveHostIps(string $host): array
    {
        $host = self::normalizeHost($host);

        if (! $host) {
            return ['state' => self::URL_HOST, 'ips' => []];
        }

        // v2: the cached value used to be a bare list of ips. Anything that
        // does not carry a state is a stale entry from that shape and is
        // looked up again.
        $key = self::URL_CACHE_PREFIX.'public-ips:v2:'.hash('xxh128', $host);

        $cached = Cache::get($key);

        if (is_array($cached) && isset($cached['state'], $cached['ips'])) {
            return $cached;
        }

        $result = self::lookupHostIps($host);

        $ttl = match ($result['state']) {
            self::URL_OK => self::DNS_TTL_POSITIVE,
            self::URL_UNRESOLVED => self::DNS_TTL_UNRESOLVED,
            default => self::DNS_TTL_NEGATIVE,
        };

        Cache::put($key, $result, $ttl);

        return $result;
    }

    /**
     * Resolved public addresses for a host, or an empty array.
     *
     * Kept for callers that only want the addresses. Note that an empty
     * result no longer means the host is unusable, see resolveHostIps().
     *
     * @return array<int, string>
     */
    public static function resolvePublicIps(string $host): array
    {
        return self::resolveHostIps($host)['ips'];
    }

    /**
     * Validate host requirements
     */
    public static function isValidHost(?string $host): bool
    {
        return self::normalizeHost($host) !== null;
    }

    public static function isPublicIp(string $ip): bool
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_GLOBAL_RANGE
        ) !== false;
    }

    /**
     * Check DNS and banned status if required
     *
     * @deprecated validateUrlWithReason() no longer routes through this.
     *             Left in place for external callers.
     */
    public static function passesSecurityChecks(string $host, bool $disableDNSCheck, bool $forceBanCheck): bool
    {
        if ($disableDNSCheck !== true && self::shouldCheckDNS()) {
            if (! self::hasValidDNS($host)) {
                return false;
            }
        }

        if ($forceBanCheck || self::shouldCheckBans()) {
            if (self::isHostBanned($host)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if DNS validation is required
     */
    public static function shouldCheckDNS(): bool
    {
        return app()->environment() === 'production';
    }

    /**
     * Validate domain DNS records
     *
     * @deprecated Superseded by resolveHostIps(), which distinguishes an
     *             unresolvable host from one resolving into private space.
     */
    public static function hasValidDNS(string $host): bool
    {
        $hash = hash('sha256', $host);
        $key = self::URL_CACHE_PREFIX."valid-dns:sha256-{$hash}";

        return Cache::remember($key, self::CACHE_TTL, function () use ($host) {
            return DomainService::hasValidDns($host);
        });
    }

    /**
     * Check if domain bans should be validated
     */
    public static function shouldCheckBans(): bool
    {
        return app()->environment() === 'production';
    }

    /**
     * Check if host is in banned domains list
     */
    public static function isHostBanned(string $host): bool
    {
        $host = strtolower(rtrim($host, '.'));

        $bannedInstances = array_map(
            fn ($domain): string => strtolower(rtrim($domain, '.')),
            InstanceService::getBannedDomains()
        );

        return in_array($host, $bannedInstances, true);
    }

    /**
     * Validate local URL
     */
    public static function validateLocalUrl(string $url): string|bool
    {
        $url = self::validateUrl($url);

        if (! $url) {
            return false;
        }

        return self::isAppDomain(parse_url($url, PHP_URL_HOST)) ? $url : false;
    }

    /**
     * Get user agent string
     */
    public static function zttpUserAgent(): array
    {
        $version = config('pixelfed.version');
        $url = config('app.url');

        return [
            'Accept' => 'application/activity+json',
            'User-Agent' => "(Pixelfed/{$version}; +{$url})",
        ];
    }

    public static function fetchFromUrl($url = false)
    {
        if (self::validateUrl($url) == false) {
            return;
        }

        if (is_array($url)) {
            $url = $url[0] ?? null;
        }

        if (! is_string($url)) {
            return;
        }

        $key = self::fetchCacheKey($url);

        $cached = Cache::get($key);

        if ($cached !== null) {
            return $cached;
        }

        $res = self::fetchAndDecode($url);

        // Successes and failures get different lifetimes, see FETCH_NEGATIVE_TTL.
        Cache::put(
            $key,
            $res,
            $res === false
                ? self::FETCH_NEGATIVE_TTL
                : now()->addMinutes(self::FETCH_CACHE_TTL)
        );

        return $res;
    }

    public static function fetchCacheKey(string $url): string
    {
        return 'helpers:url:fetcher:sha256-'.hash('sha256', $url);
    }

    private static function fetchAndDecode(string $url): array|false
    {
        $res = ActivityPubFetchService::get($url);

        if (! $res || empty($res)) {
            return false;
        }

        $res = json_decode($res, true, 8);

        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($res)) {
            return false;
        }

        return $res;
    }

    public static function fetchProfileFromUrl($url)
    {
        return self::fetchFromUrl($url);
    }

    public static function pluckval($val)
    {
        if (is_string($val)) {
            return $val;
        }

        if (is_array($val)) {
            return ! empty($val) ? head($val) : null;
        }

        return null;
    }

    public static function validateTimestamp($timestamp)
    {
        try {
            $date = Carbon::parse($timestamp);
            $now = now();
            $tenYearsAgo = $now->copy()->subYears(20);
            $isMoreThanTenYearsOld = $date->lt($tenYearsAgo);
            $tomorrow = $now->copy()->addDay();
            $isMoreThanOneDayFuture = $date->gt($tomorrow);

            return ! ($isMoreThanTenYearsOld || $isMoreThanOneDayFuture);
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Fetch or create a status from URL
     *
     * $depth counts how many inReplyTo hops we are from the status that
     * started this resolution. Every recursive call passes $depth + 1 so the
     * walk terminates at MAX_REPLY_DEPTH regardless of what the remote
     * server keeps answering.
     */
    public static function statusFirstOrFetch(string $url, bool $replyTo = false, int $depth = 0): ?Status
    {
        if (! $validUrl = self::validateUrl($url)) {
            return null;
        }

        if ($status = self::findExistingStatus($url)) {
            return $status;
        }

        // A local URL we cannot map to a row is a deleted, archived or
        // unknown status. There is nothing to fetch, and fetching our own
        // domain would store a remote copy of a local status.
        $host = parse_url($url, PHP_URL_HOST);

        if (is_string($host) && self::isLocalDomain($host)) {
            return null;
        }

        // Bound how far up an inReplyTo chain a single fetch may walk.
        // Checked after the DB lookup so a reply to an already-known
        // status still links even at the limit, but we never fetch past it.
        if ($depth > self::MAX_REPLY_DEPTH) {
            return null;
        }

        return self::createStatusFromUrl($url, $replyTo, $depth);
    }

    private static function matchPathTemplate(
        string $path,
        string $template,
        array $constraints = []
    ): ?array {
        $path = '/'.trim($path, '/');
        $template = '/'.trim($template, '/');

        $offset = 0;
        $pattern = '';

        preg_match_all(
            '/\{([a-zA-Z][a-zA-Z0-9_]*)\}/',
            $template,
            $placeholders,
            PREG_OFFSET_CAPTURE
        );

        foreach ($placeholders[0] as $index => [$placeholder, $position]) {
            $name = $placeholders[1][$index][0];

            $literal = substr(
                $template,
                $offset,
                $position - $offset
            );

            $pattern .= preg_quote($literal, '#');

            $constraint = $constraints[$name] ?? '[^/]+';

            $pattern .= '(?P<'.$name.'>'.$constraint.')';

            $offset = $position + strlen($placeholder);
        }

        $pattern .= preg_quote(
            substr($template, $offset),
            '#'
        );

        if (! preg_match('#^'.$pattern.'/?$#', $path, $matches)) {
            return null;
        }

        return collect($matches)
            ->filter(fn ($value, $key) => is_string($key))
            ->all();
    }

    private static function extractLocalStatusId(string $url): ?int
    {
        $path = parse_url($url, PHP_URL_PATH);

        if (! is_string($path) || $path === '') {
            return null;
        }

        $templates = [
            '/p/{username}/{id}',
            '/p/{username}/{id}/activity',
        ];

        foreach ($templates as $template) {
            $match = self::matchPathTemplate(
                $path,
                $template,
                [
                    'username' => '[A-Za-z0-9._-]+',
                    'id' => '\d+',
                ]
            );

            if ($match !== null) {
                return (int) $match['id'];
            }
        }

        return null;
    }

    /**
     * Find existing status by URL
     */
    public static function findExistingStatus(string $url): ?Status
    {
        $host = parse_url($url, PHP_URL_HOST);

        if (! is_string($host) || $host === '') {
            return null;
        }

        if (self::isLocalDomain($host)) {
            $id = self::extractLocalStatusId($url);

            if ($id === null) {
                return null;
            }

            return Status::whereNotIn('scope', ['draft', 'archived'])
                ->find($id);
        }

        return Status::whereNotIn('scope', ['draft', 'archived'])
            ->where(function ($query) use ($url) {
                $query->whereUri($url)
                    ->orWhere('object_url', $url);
            })
            ->first();
    }

    /**
     * Create a new status from ActivityPub data
     */
    public static function createStatusFromUrl(
        string $url,
        bool $replyTo,
        int $depth = 0
    ): ?Status {
        $res = self::fetchFromUrl($url);

        if (! $res || ! self::isValidStatusData($res)) {
            return null;
        }

        if (! self::validateTimestamp($res['published'])) {
            return null;
        }

        if (! self::passesContentFilters($res)) {
            return null;
        }

        $object = self::statusObject($res);
        $activity = ['object' => $object];

        if (! $profile = self::getStatusProfile($activity)) {
            return null;
        }

        if (! self::validateStatusUrls($url, $activity)) {
            return null;
        }

        $scope = self::getScope($object, $url);
        $cw = self::getSensitive($object, $url);

        if (($object['type'] ?? null) === 'Question') {
            $resolution = self::resolveReplyParent(
                $activity,
                $profile,
                $depth
            );

            if (! self::replyParentAllowsStore($resolution)) {
                return null;
            }

            return self::storePoll(
                $profile,
                $object,
                $url,
                $object['published'] ?? $res['published'],
                $resolution['status']?->id,
                $cw,
                $scope,
                $object['id'] ?? $url
            );
        }

        return self::storeStatus(
            $url,
            $profile,
            $object,
            $depth
        );
    }

    /**
     * Validate status data
     */
    public static function isValidStatusData(?array $res): bool
    {
        return $res &&
            ! empty($res) &&
            ! isset($res['error']) &&
            isset($res['@context']) &&
            isset($res['published']);
    }

    /**
     * Check if content passes filters
     */
    public static function passesContentFilters(array $res): bool
    {
        if (! config('autospam.live_filters.enabled')) {
            return true;
        }

        $filters = config('autospam.live_filters.filters');
        if (empty($filters) || ! isset($res['content']) || strlen($filters) <= 3) {
            return true;
        }

        $filters = array_map('trim', explode(',', $filters));
        $content = strtolower($res['content']);

        foreach ($filters as $filter) {
            $filter = trim(strtolower($filter));
            if ($filter && str_contains($content, $filter)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get profile for status
     */
    public static function getStatusProfile(array $activity): ?Profile
    {
        if (! isset($activity['object']['attributedTo'])) {
            return null;
        }

        $attributedTo = self::extractAttributedTo($activity['object']['attributedTo']);

        return $attributedTo ? self::profileFirstOrNew($attributedTo) : null;
    }

    /**
     * Extract attributed to value
     */
    public static function extractAttributedTo(string|array $attributedTo): ?string
    {
        if (is_string($attributedTo)) {
            return $attributedTo;
        }

        if (is_array($attributedTo)) {
            return collect($attributedTo)
                ->filter(fn ($o): bool => $o && isset($o['type']) && $o['type'] == 'Person')
                ->pluck('id')
                ->first();
        }

        return null;
    }

    /**
     * Validate status URLs match
     */
    public static function validateStatusUrls(string $url, array $activity): bool
    {
        $id = self::extractActivityPubUrl(
            $activity['id'] ?? $url
        );

        if (! $id) {
            return false;
        }

        $idDomain = parse_url($id, PHP_URL_HOST);
        $urlDomain = parse_url($url, PHP_URL_HOST);

        if (! is_string($idDomain) || ! is_string($urlDomain)) {
            return false;
        }

        if (strcasecmp($idDomain, $urlDomain) !== 0) {
            return false;
        }

        $attributedTo = $activity['attributedTo']
            ?? $activity['object']['attributedTo']
            ?? null;

        if ($attributedTo !== null) {
            $author = self::extractActivityPubUrl($attributedTo);

            if (! $author) {
                return false;
            }

            $authorDomain = parse_url($author, PHP_URL_HOST);

            if (
                ! is_string($authorDomain) ||
                strcasecmp($idDomain, $authorDomain) !== 0
            ) {
                return false;
            }
        }

        return true;
    }

    private static function extractActivityPubUrl($value): ?string
    {
        $value = self::pluckval($value);

        if (is_string($value)) {
            return $value !== '' ? $value : null;
        }

        if (is_array($value)) {
            foreach ($value as $item) {
                $item = self::pluckval($item);

                if (is_string($item) && $item !== '') {
                    return $item;
                }

                if (is_array($item) && isset($item['id']) && is_string($item['id'])) {
                    return $item['id'];
                }
            }
        }

        return null;
    }

    /**
     * Resolve the parent of a reply.
     *
     * Resolves (and fetches if needed) the status referenced by
     * object.inReplyTo, one hop deeper than the caller, and reports why when
     * it cannot. "This is not a reply" and "this is a reply whose parent we
     * could not get" are different answers: only the first may be stored
     * without in_reply_to_id.
     *
     * @return array{state: string, status: ?Status}
     */
    public static function resolveReplyParent(
        array $activity,
        Profile $profile,
        int $depth = 0
    ): array {
        $object = self::statusObject($activity);

        $raw = $object['inReplyTo'] ?? null;

        if ($raw === null || $raw === '' || $raw === []) {
            return self::replyParentResult(self::REPLY_PARENT_NONE);
        }

        $inReplyTo = self::extractInReplyTo($raw);

        if ($inReplyTo === null) {
            // Declared as a reply, but to nothing we can address.
            return self::replyParentResult(self::REPLY_PARENT_REJECTED);
        }

        $parent = self::statusFirstOrFetch(
            $inReplyTo,
            false,
            $depth + 1
        );

        if (! $parent) {
            return self::replyParentResult(self::REPLY_PARENT_UNRESOLVED);
        }

        if (self::parentRefusesReplyFrom($parent, $profile)) {
            return self::replyParentResult(self::REPLY_PARENT_REJECTED);
        }

        return self::replyParentResult(self::REPLY_PARENT_RESOLVED, $parent);
    }

    /**
     * @return array{state: string, status: ?Status}
     */
    private static function replyParentResult(string $state, ?Status $status = null): array
    {
        return ['state' => $state, 'status' => $status];
    }

    /**
     * Whether an object with this resolution may be written to the database.
     *
     * @param  array{state: string, status: ?Status}  $resolution
     */
    public static function replyParentAllowsStore(array $resolution): bool
    {
        return in_array($resolution['state'], [
            self::REPLY_PARENT_NONE,
            self::REPLY_PARENT_RESOLVED,
        ], true);
    }

    /**
     * Pull a single URL out of an inReplyTo value. JSON-LD allows a string,
     * a list, an embedded object, or a Link.
     */
    private static function extractInReplyTo(mixed $value): ?string
    {
        if (is_string($value)) {
            $value = trim($value);

            return $value !== '' ? $value : null;
        }

        if (! is_array($value) || $value === []) {
            return null;
        }

        if (array_is_list($value)) {
            return self::extractInReplyTo($value[0]);
        }

        foreach (['id', 'href'] as $key) {
            if (isset($value[$key]) && is_string($value[$key]) && trim($value[$key]) !== '') {
                return trim($value[$key]);
            }
        }

        return null;
    }

    /**
     * Whether the parent's author has opted out of replies from this profile.
     *
     * Ids are compared as strings: UserFilterService returns them from Redis
     * as strings while model keys are integers, so a strict in_array() on the
     * raw values never matches.
     */
    private static function parentRefusesReplyFrom(Status $parent, Profile $profile): bool
    {
        if ((string) $parent->profile_id === (string) $profile->id) {
            return false;
        }

        $blocks = array_map(
            'strval',
            (array) UserFilterService::blocks((int) $parent->profile_id)
        );

        if (in_array((string) $profile->id, $blocks, true)) {
            return true;
        }

        // Domain blocks and the comments toggle only exist for local authors.
        // For a remote parent the origin server is the authority.
        if (! empty($parent->uri)) {
            return false;
        }

        if ($parent->comments_disabled) {
            return true;
        }

        return $profile->domain !== null &&
            AccountService::blocksDomain($parent->profile_id, $profile->domain) === true;
    }

    /**
     * Get reply-to status ID
     *
     * Kept for callers that only need the id. It cannot tell "not a reply"
     * from "parent unavailable", so nothing that stores a status should use
     * it. Use resolveReplyParent().
     */
    public static function getReplyToId(
        array $activity,
        Profile $profile,
        bool $replyTo = false,
        int $depth = 0
    ): ?int {
        return self::resolveReplyParent($activity, $profile, $depth)['status']?->id;
    }

    /**
     * Store a new regular status
     *
     * Returns null, and writes nothing, when the object is a reply whose
     * parent is unresolved or refuses it. A reply is never stored without
     * in_reply_to_id. Inbox deliveries that want a retry should check
     * resolveReplyParent() first, see HandlesCreates::handleNoteReply().
     */
    public static function storeStatus(
        string $url,
        Profile $profile,
        array $activity,
        int $depth = 0
    ): ?Status {
        $object = self::statusObject($activity);

        $id = self::getStatusId($object, $url);
        $url = self::getStatusUrl($object, $id);

        if (
            (! isset($object['type']) ||
                in_array($object['type'], ['Create', 'Note'], true)) &&
            ! self::validateStatusDomains($id, $url)
        ) {
            throw new \Exception(json_encode([
                'message' => 'Invalid status domains',
                'checked' => [
                    'id' => $id,
                    'id_host' => parse_url($id, PHP_URL_HOST),
                    'id_valid_url' => self::validateUrl($id),
                    'id_reason' => self::validateUrlWithReason($id)['reason'],
                    'url' => $url,
                    'url_host' => parse_url($url, PHP_URL_HOST),
                    'url_valid_url' => self::validateUrl($url),
                    'url_reason' => self::validateUrlWithReason($url)['reason'],
                ],
                'expected' => 'id host and url host to be valid and match (case-insensitive)',
                'payload' => $object,
            ]));
        }

        $resolution = self::resolveReplyParent(
            ['object' => $object],
            $profile,
            $depth
        );

        if (! self::replyParentAllowsStore($resolution)) {
            return null;
        }

        $parent = $resolution['status'];
        $replyTo = $parent?->id;

        $published = self::pluckval(
            $object['published']
                ?? $activity['published']
                ?? null
        );

        if (! is_string($published) || $published === '') {
            throw new \Exception('Missing ActivityPub published timestamp');
        }

        $scope = self::getScope($object, $url);

        $commentsDisabled =
            isset($object['commentsEnabled']) &&
            (bool) $object['commentsEnabled'] === false;

        $cw = self::getSensitive($object, $url);

        if ($profile->unlisted) {
            $scope = 'unlisted';
        }

        $status = self::createOrUpdateStatus(
            $url,
            $profile,
            $id,
            $object,
            $published,
            $replyTo,
            $cw,
            $scope,
            $commentsDisabled,
            $parent?->profile_id
        );

        if (! $status) {
            return null;
        }

        // False when another worker stored this status first, or when this
        // is a refresh of a known status. Counters, notifications and feed
        // fan-out must only run once per status.
        $isNew = $status->wasRecentlyCreated;

        if ($replyTo === null) {
            self::importNoteAttachment($object, $status);
        } else {
            if (
                isset($object['attachment']) &&
                ! empty($object['attachment'])
            ) {
                self::importNoteAttachment($object, $status);
            }

            if ($isNew) {
                StatusReplyPipeline::dispatch($status);
            }
        }

        if (
            isset($object['tag']) &&
            is_array($object['tag']) &&
            ! empty($object['tag'])
        ) {
            StatusTagsPipeline::dispatch($object, $status);
        }

        if ($isNew) {
            self::handleStatusPostProcessing(
                $status,
                $profile->id,
                $url
            );
        }

        return $status;
    }

    /**
     * Get status ID from activity
     */
    public static function getStatusId(array $activity, string $url): string
    {
        return isset($activity['id']) ?
            self::pluckval($activity['id']) :
            self::pluckval($url);
    }

    /**
     * Get status URL from activity
     */
    public static function getStatusUrl(array $activity, string $id): string
    {
        return isset($activity['url']) && is_string($activity['url']) ?
            self::pluckval($activity['url']) :
            self::pluckval($id);
    }

    /**
     * Validate the status URL and ID are valid
     */
    public static function validateStatusDomains(string $id, string $url): bool
    {
        if (! self::validateUrl($id) || ! self::validateUrl($url)) {
            return false;
        }

        $idDomain = parse_url($id, PHP_URL_HOST);
        $urlDomain = parse_url($url, PHP_URL_HOST);

        return $idDomain && $urlDomain && strtolower($idDomain) === strtolower($urlDomain);
    }

    private static function statusObject(array $payload): array
    {
        if (
            isset($payload['object']) &&
            is_array($payload['object'])
        ) {
            return $payload['object'];
        }

        return $payload;
    }

    /**
     * Create or update status record
     *
     * Returns null when the status exists but was deleted locally, or when
     * its uri or object_url is already held by another profile.
     */
    public static function createOrUpdateStatus(
        string $url,
        Profile $profile,
        string $id,
        array $activity,
        string $ts,
        ?int $reply_to,
        bool $cw,
        string $scope,
        bool $commentsDisabled,
        ?int $replyToProfileId = null
    ): ?Status {
        $caption = isset($activity['content']) ?
            app(SanitizeService::class)->html($activity['content']) :
            '';
        $cwSummary = ($cw && isset($activity['summary'])) ?
            app(SanitizeService::class)->html($activity['summary']) :
            null;

        $attributes = [
            'profile_id' => $profile->id,
            'url' => $url,
            'object_url' => $id,
            'caption' => strip_tags($caption),
            'rendered' => $caption,
            'created_at' => Carbon::parse($ts)->tz('UTC'),
            'in_reply_to_id' => $reply_to,
            'in_reply_to_profile_id' => $reply_to ? $replyToProfileId : null,
            'local' => false,
            'is_nsfw' => $cw,
            'scope' => $scope,
            'visibility' => $scope,
            'cw_summary' => $cwSummary ? strip_tags($cwSummary) : null,
            'comments_disabled' => $commentsDisabled,
        ];

        try {
            return Status::updateOrCreate(['uri' => $url], $attributes);
        } catch (UniqueConstraintViolationException) {
            // uri and object_url are both unique. We land here when another
            // worker inserted the same status between updateOrCreate's
            // select and insert (two replies to one unknown parent arriving
            // together), or when the row exists but is soft deleted.
            // Returning the winner keeps the rest of the reply chain alive
            // instead of failing the whole job. A soft deleted row means the
            // status was removed and must not come back.
            $existing = Status::withTrashed()
                ->where(function ($query) use ($url, $id) {
                    $query->where('uri', $url)
                        ->orWhere('object_url', $id);
                })
                ->first();

            // Only hand back a row by the same author. A row that holds this
            // uri or object_url under another profile is not this status.
            return $existing &&
                ! $existing->trashed() &&
                (string) $existing->profile_id === (string) $profile->id
                ? $existing
                : null;
        }
    }

    /**
     * Handle post-creation status processing
     */
    public static function handleStatusPostProcessing(Status $status, int $profileId, string $url): void
    {
        if (
            config('instance.timeline.network.cached') &&
            self::isEligibleForNetwork($status) &&
            ! FeedInsertRemotePipeline::isTooOld($status->created_at)
        ) {
            $urlDomain = parse_url($url, PHP_URL_HOST);
            $filteredDomains = self::getFilteredDomains();

            if (! in_array($urlDomain, $filteredDomains)) {
                NetworkTimelineService::add($status->id);
            }
        }

        AccountStatService::incrementPostCount($profileId);

        if (
            $status->in_reply_to_id === null &&
            in_array($status->type, ['photo', 'photo:album', 'video', 'video:album', 'photo:video:album'])
        ) {
            FeedInsertRemotePipeline::dispatch($status->id, $profileId)
                ->onQueue('feed');
        }
    }

    /**
     * Check if status is eligible for network timeline
     */
    public static function isEligibleForNetwork(Status $status): bool
    {
        return $status->in_reply_to_id === null &&
            $status->reblog_of_id === null &&
            in_array($status->type, ['photo', 'photo:album', 'video', 'video:album', 'photo:video:album']) &&
            $status->created_at->gt(now()->subHours(config('instance.timeline.network.max_hours_old'))) &&
            (config('instance.hide_nsfw_on_public_feeds') ? ! $status->is_nsfw : true);
    }

    /**
     * Get filtered domains list
     */
    public static function getFilteredDomains(): array
    {
        return collect(InstanceService::getBannedDomains())
            ->merge(InstanceService::getUnlistedDomains())
            ->unique()
            ->values()
            ->toArray();
    }

    public static function getSensitive($activity, $url)
    {
        if (! $url || ! strlen($url)) {
            return true;
        }

        $urlDomain = parse_url($url, PHP_URL_HOST);
        $cw = isset($activity['sensitive']) ? (bool) $activity['sensitive'] : false;

        if (in_array($urlDomain, InstanceService::getNsfwDomains())) {
            $cw = true;
        }

        return $cw;
    }

    /**
     * Resolve the parent status id for an object's inReplyTo, one hop deeper
     * than the caller. Shares the same depth bound as getReplyToId so the
     * storeStatus path cannot restart the walk from zero.
     */
    public static function getReplyTo($activity, int $depth = 0)
    {
        $inReplyTo = ! empty($activity['inReplyTo']) ?
            self::pluckval($activity['inReplyTo']) :
            null;

        if (! is_string($inReplyTo) || $inReplyTo === '') {
            return null;
        }

        return self::statusFirstOrFetch($inReplyTo, false, $depth + 1)?->id;
    }

    public static function getScope($activity, $url): string
    {
        $id = isset($activity['id']) ? self::pluckval($activity['id']) : self::pluckval($url);
        $url = isset($activity['url']) ? self::pluckval($activity['url']) : self::pluckval($id);
        $urlDomain = parse_url(self::pluckval($url), PHP_URL_HOST);
        $scope = 'private';

        if (isset($activity['to']) === true) {
            if (is_array($activity['to']) && in_array('https://www.w3.org/ns/activitystreams#Public', $activity['to'])) {
                $scope = 'public';
            }
            if (is_string($activity['to']) && $activity['to'] == 'https://www.w3.org/ns/activitystreams#Public') {
                $scope = 'public';
            }
        }

        if (isset($activity['cc']) === true) {
            if (is_array($activity['cc']) && in_array('https://www.w3.org/ns/activitystreams#Public', $activity['cc'])) {
                $scope = 'unlisted';
            }
            if (is_string($activity['cc']) && $activity['cc'] == 'https://www.w3.org/ns/activitystreams#Public') {
                $scope = 'unlisted';
            }
        }

        if ($scope === 'public' && in_array($urlDomain, InstanceService::getUnlistedDomains())) {
            $scope = 'unlisted';
        }

        return $scope;
    }

    public static function storePoll($profile, $res, $url, $ts, $reply_to, $cw, $scope, $id)
    {
        if (! isset($res['endTime']) || ! isset($res['oneOf']) || ! is_array($res['oneOf']) || count($res['oneOf']) > 4) {
            return null;
        }

        $options = collect($res['oneOf'])->map(function ($option) {
            return $option['name'];
        })->toArray();

        $cachedTallies = collect($res['oneOf'])->map(function ($option) {
            return $option['replies']['totalItems'] ?? 0;
        })->toArray();

        $defaultCaption = '';
        $cleanedCaption = ! empty($res['content']) ?
            app(SanitizeService::class)->html($res['content']) :
            null;
        $status = new Status;
        $status->profile_id = $profile->id;
        $status->url = isset($res['url']) ? $res['url'] : $url;
        $status->uri = isset($res['url']) ? $res['url'] : $url;
        $status->object_url = $id;
        $status->caption = $cleanedCaption ? strip_tags($cleanedCaption) : $defaultCaption;
        $status->rendered = Purify::clean($res['content'] ?? $defaultCaption);
        $status->created_at = Carbon::parse($ts)->tz('UTC');
        $status->in_reply_to_id = $reply_to;
        $status->local = false;
        $status->is_nsfw = $cw;
        $status->scope = 'draft';
        $status->visibility = 'draft';
        $status->cw_summary = $cw == true && isset($res['summary']) ?
            Purify::clean(strip_tags($res['summary'])) : null;
        $status->save();

        $poll = new Poll;
        $poll->status_id = $status->id;
        $poll->profile_id = $status->profile_id;
        $poll->poll_options = $options;
        $poll->cached_tallies = $cachedTallies;
        $poll->votes_count = array_sum($cachedTallies);
        $poll->expires_at = now()->parse($res['endTime']);
        $poll->last_fetched_at = now();
        $poll->save();

        $status->type = 'poll';
        $status->scope = $scope;
        $status->visibility = $scope;
        $status->save();

        return $status;
    }

    public static function statusFetch($url)
    {
        return self::statusFirstOrFetch($url);
    }

    /**
     * Process and store note attachments
     */
    public static function importNoteAttachment(array $data, Status $status): void
    {
        if (! self::verifyAttachments($data)) {
            $status->viewType();

            return;
        }

        $attachments = self::getAttachments($data);
        $profile = $status->profile;
        $storagePath = MediaPathService::get($profile, 2);
        $allowedTypes = explode(',', config_cache('pixelfed.media_types'));

        foreach ($attachments as $key => $media) {
            if (! self::isValidAttachment($media, $allowedTypes)) {
                continue;
            }

            $mediaModel = self::createMediaAttachment($media, $status, $key);
            if ($mediaModel) {
                self::handleMediaStorage($mediaModel);
            }
        }

        $status->viewType();
    }

    /**
     * Get attachments from ActivityPub data
     */
    public static function getAttachments(array $data): array
    {
        $object = isset($data['object']) ?
            $data['object'] :
            $data;

        if (
            ! is_array($object) ||
            ! isset($object['attachment']) ||
            empty($object['attachment']) ||
            ! is_array($object['attachment'])
        ) {
            return [];
        }

        // JSON-LD compaction can collapse a single-item attachment array into a
        // bare object. Normalize both shapes to a list so callers can iterate
        // uniformly (pixelfed#6588).
        return array_is_list($object['attachment']) ?
            $object['attachment'] :
            [$object['attachment']];
    }

    /**
     * Validate individual attachment
     */
    public static function isValidAttachment(array $media, array $allowedTypes): bool
    {
        $type = $media['mediaType'];
        $url = $media['url'];

        return in_array($type, $allowedTypes) &&
            self::validateUrl($url);
    }

    /**
     * Create media attachment record.
     *
     * Idempotent on the (status_id, media_path) unique key: if a row already
     * exists (e.g. a re-fetch, an Announce racing another inbox job, or a
     * duplicate url within one activity's attachments) the existing row is
     * returned instead of triggering a duplicate-key violation.
     *
     * @return Media|null the newly created model, or null when the attachment
     *                    already existed (so the caller can skip re-storage)
     */
    public static function createMediaAttachment(array $media, Status $status, int $key): ?Media
    {
        // Fast path: already imported for this status.
        if (Media::whereStatusId($status->id)->whereMediaPath($media['url'])->exists()) {
            return null;
        }

        $mediaModel = new Media;

        self::setBasicMediaAttributes($mediaModel, $media, $status, $key);
        self::setOptionalMediaAttributes($mediaModel, $media);

        try {
            $mediaModel->save();
        } catch (UniqueConstraintViolationException) {
            // Lost a race with a concurrent inbox job that inserted the same
            // (status_id, media_path). Treat as already-imported.
            return null;
        }

        return $mediaModel;
    }

    /**
     * Set basic media attributes
     */
    public static function setBasicMediaAttributes(Media $media, array $data, Status $status, int $key): void
    {
        $media->remote_media = true;
        $media->status_id = $status->id;
        $media->profile_id = $status->profile_id;
        $media->user_id = null;
        $media->media_path = $data['url'];
        $media->remote_url = $data['url'];
        $media->mime = $data['mediaType'];
        $media->version = 3;
        $media->order = $key + 1;
    }

    /**
     * Set optional media attributes
     */
    public static function setOptionalMediaAttributes(Media $media, array $data): void
    {
        $media->blurhash = $data['blurhash'] ?? null;
        $media->caption = isset($data['name']) ?
            Purify::clean($data['name']) :
            null;

        if (isset($data['width'])) {
            $media->width = $data['width'];
        }

        if (isset($data['height'])) {
            $media->height = $data['height'];
        }

        if (isset($data['license'])) {
            $media->license = License::nameToId($data['license']);
        }
    }

    /**
     * Handle media storage processing
     */
    public static function handleMediaStorage(Media $media): void
    {
        if ((bool) config_cache('pixelfed.cloud_storage')) {
            MediaStoragePipeline::dispatch($media);
        }
    }

    /**
     * Validate attachment collection
     */
    public static function validateAttachmentCollection(array $attachments, array $mediaTypes, array $mimeTypes): bool
    {
        return Validator::make($attachments, [
            '*.type' => [
                'required',
                'string',
                Rule::in($mediaTypes),
            ],
            '*.url' => 'required|url',
            '*.mediaType' => [
                'required',
                'string',
                Rule::in($mimeTypes),
            ],
            '*.name' => 'sometimes|nullable|string',
            '*.blurhash' => 'sometimes|nullable|string|min:6|max:164',
            '*.width' => 'sometimes|nullable|integer|min:1|max:5000',
            '*.height' => 'sometimes|nullable|integer|min:1|max:5000',
        ])->passes();
    }

    /**
     * Get supported media types
     */
    public static function getSupportedMediaTypes(): array
    {
        $mimeTypes = explode(',', config_cache('pixelfed.media_types'));

        return in_array('video/mp4', $mimeTypes) ?
            ['Document', 'Image', 'Video'] :
            ['Document', 'Image'];
    }

    /**
     * Process specific media type attachment
     */
    public static function processMediaTypeAttachment(array $media, Status $status, int $order): ?Media
    {
        if (! self::isValidMediaType($media)) {
            return null;
        }

        $mediaModel = new Media;
        self::setMediaAttributes($mediaModel, $media, $status, $order);
        $mediaModel->save();

        return $mediaModel;
    }

    /**
     * Validate media type
     */
    public static function isValidMediaType(array $media): bool
    {
        $requiredFields = ['mediaType', 'url'];

        foreach ($requiredFields as $field) {
            if (! isset($media[$field]) || empty($media[$field])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Set media attributes
     */
    public static function setMediaAttributes(Media $media, array $data, Status $status, int $order): void
    {
        $media->remote_media = true;
        $media->status_id = $status->id;
        $media->profile_id = $status->profile_id;
        $media->user_id = null;
        $media->media_path = $data['url'];
        $media->remote_url = $data['url'];
        $media->mime = $data['mediaType'];
        $media->version = 3;
        $media->order = $order;

        // Optional attributes
        if (isset($data['blurhash'])) {
            $media->blurhash = $data['blurhash'];
        }

        if (isset($data['name'])) {
            $media->caption = Purify::clean($data['name']);
        }

        if (isset($data['width'])) {
            $media->width = $data['width'];
        }

        if (isset($data['height'])) {
            $media->height = $data['height'];
        }

        if (isset($data['license'])) {
            $media->license = License::nameToId($data['license']);
        }
    }

    /**
     * Fetch or create a profile from a URL
     */
    public static function profileFirstOrNew(string $url): ?Profile
    {
        if (! $validatedUrl = self::validateUrl($url)) {
            return null;
        }

        $host = parse_url($validatedUrl, PHP_URL_HOST);

        if (self::isLocalDomain($host)) {
            return self::getLocalProfile($validatedUrl);
        }

        return self::getOrFetchRemoteProfile($validatedUrl);
    }

    /**
     * Check if domain is local
     *
     * Accepts null because every caller feeds this from parse_url(), which
     * returns null for a malformed host.
     */
    public static function isLocalDomain(?string $host): bool
    {
        return self::isAppDomain($host);
    }

    /**
     * Get local profile from URL
     */
    public static function getLocalProfile(string $url): ?Profile
    {
        $username = last(explode('/', $url));

        return Profile::whereNull('status')
            ->whereNull('domain')
            ->whereUsername($username)
            ->firstOrFail();
    }

    /**
     * Get existing or fetch new remote profile
     */
    public static function getOrFetchRemoteProfile(string $url): ?Profile
    {
        $profile = Profile::whereRemoteUrl($url)->first();

        if (! $profile) {
            return self::profileUpdateOrCreate($url);
        }

        if (! self::needsFetch($profile)) {
            return $profile;
        }

        // Attempt a refresh, but fall back to the existing profile if it fails
        // (network/validation error). Discarding a known-good profile here
        // caused null dereferences in downstream activity handlers.
        $refreshed = self::profileUpdateOrCreate($url);

        return $refreshed ?? $profile;
    }

    /**
     * Check if profile needs to be fetched
     */
    public static function needsFetch(?Profile $profile): bool
    {
        return ! $profile?->last_fetched_at ||
            $profile->last_fetched_at->lt(now()->subHours(24));
    }

    /**
     * Update or create a profile from ActivityPub data
     */
    public static function profileUpdateOrCreate(string $url, bool $movedToCheck = false): ?Profile
    {
        $res = self::fetchProfileFromUrl($url);

        if (! $res || ! self::isValidProfileData($res, $url)) {
            return null;
        }

        $domain = parse_url($res['id'], PHP_URL_HOST);
        $username = self::extractUsername($res);

        if (! $username || self::isProfileBanned($res['id'])) {
            return null;
        }

        $webfinger = "@{$username}@{$domain}";
        $instance = self::getOrCreateInstance($domain);
        $movedToPid = $movedToCheck ? null : self::handleMovedTo($res);

        $profile = Profile::updateOrCreate(
            [
                'domain' => strtolower($domain),
                'username' => Purify::clean($webfinger),
            ],
            self::buildProfileData($res, $webfinger, $movedToPid)
        );

        self::handleProfileAvatar($profile);

        return $profile;
    }

    /**
     * Validate profile data from ActivityPub
     */
    public static function isValidProfileData(?array $res, string $url): bool
    {
        if (! $res || ! isset($res['id']) || ! isset($res['inbox'])) {
            return false;
        }

        if (! self::validateUrl($res['inbox']) || ! self::validateUrl($res['id'])) {
            return false;
        }

        $urlDomain = parse_url($url, PHP_URL_HOST);
        $domain = parse_url($res['id'], PHP_URL_HOST);

        if (strtolower($urlDomain) !== strtolower($domain)) {
            return false;
        }

        // The actor's key_id (publicKey.id) must live on the same host as the
        // actor id. Without this, a remote actor could advertise a publicKey.id
        // pointing at a victim's keyId URI, planting a poisoned
        // key_id -> attacker-public-key binding in the unique profiles.key_id
        // column. This mirrors the same-host check UpdatePersonValidator already
        // enforces on the Update pipeline.
        if (isset($res['publicKey']['id'])) {
            if (! self::validateUrl($res['publicKey']['id'])) {
                return false;
            }

            $keyDomain = parse_url($res['publicKey']['id'], PHP_URL_HOST);

            if (strtolower($keyDomain) !== strtolower($domain)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Extract username from profile data
     */
    public static function extractUsername(array $res): ?string
    {
        $username = $res['preferredUsername'] ?? $res['nickname'] ?? null;

        if (! $username || ! ctype_alnum(str_replace(['_', '.', '-'], '', $username))) {
            return null;
        }

        return Purify::clean($username);
    }

    /**
     * Check if profile is banned
     */
    public static function isProfileBanned(string $profileUrl): bool
    {
        return ModeratedProfile::whereProfileUrl($profileUrl)
            ->whereIsBanned(true)
            ->exists();
    }

    /**
     * Get or create federation instance
     */
    public static function getOrCreateInstance(string $domain): Instance
    {
        $instance = Instance::updateOrCreate(['domain' => $domain]);

        if ($instance->wasRecentlyCreated) {
            FetchNodeinfoPipeline::dispatch($instance)
                ->onQueue('low');
        }

        return $instance;
    }

    /**
     * Handle moved profile references
     */
    public static function handleMovedTo(array $res): ?int
    {
        if (! isset($res['movedTo']) || ! self::validateUrl($res['movedTo'])) {
            return null;
        }

        $movedTo = self::profileUpdateOrCreate($res['movedTo'], true);

        return $movedTo?->id;
    }

    /**
     * Build profile data array for database
     */
    public static function buildProfileData(array $res, string $webfinger, ?int $movedToPid): array
    {
        return [
            'webfinger' => Purify::clean($webfinger),
            'key_id' => $res['publicKey']['id'],
            'remote_url' => $res['id'],
            'name' => isset($res['name']) ? Purify::clean($res['name']) : 'user',
            'bio' => isset($res['summary']) ? app(SanitizeService::class)->html($res['summary']) : null,
            'sharedInbox' => $res['endpoints']['sharedInbox'] ?? null,
            'inbox_url' => $res['inbox'],
            'outbox_url' => $res['outbox'] ?? null,
            'followers_url' => FollowersSyncService::followersUrlFromActor($res),
            'public_key' => $res['publicKey']['publicKeyPem'],
            'indexable' => isset($res['indexable']) ? (bool) $res['indexable'] : false,
            'moved_to_profile_id' => $movedToPid,
            'is_private' => isset($res['manuallyApprovesFollowers']) ? (bool) $res['manuallyApprovesFollowers'] : true,
        ];
    }

    /**
     * Handle profile avatar updates
     */
    public static function handleProfileAvatar(Profile $profile): void
    {
        if (
            ! $profile->last_fetched_at ||
            $profile->last_fetched_at->lt(now()->subMonths(3))
        ) {
            RemoteAvatarFetch::dispatch($profile);
        }

        $profile->last_fetched_at = now();
        $profile->save();
    }

    public static function profileFetch($url): ?Profile
    {
        if ($url === null) {
            return null;
        }

        return self::profileFirstOrNew($url);
    }

    public static function getSignedFetch($url)
    {
        return ActivityPubFetchService::get($url);
    }

    public static function sendSignedObject($profile, $url, $body)
    {
        if (app()->environment() !== 'production') {
            return;
        }
        ActivityPubDeliveryService::queue()
            ->from($profile)
            ->to($url)
            ->payload($body)
            ->send();
    }
}
