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
use App\Services\ActivityPubDeliveryService;
use App\Services\ActivityPubFetchService;
use App\Services\DomainService;
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
    private const PUBLIC_TIMELINE = 'https://www.w3.org/ns/activitystreams#Public';

    private const CACHE_TTL = 14440;

    private const URL_CACHE_PREFIX = 'helpers:url:';

    private const FETCH_CACHE_TTL = 15;

    private const MAX_URL_LENGTH = 4096;

    private const LOCALHOST_DOMAINS = [
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
     * Validate a URL that may be used for federation.
     */
    public static function validateUrl(
        mixed $url,
        bool $disableDNSCheck = false,
        bool $forceBanCheck = false
    ): string|bool {
        $url = self::normalizeUrl($url);

        if (! $url) {
            return false;
        }

        try {
            $uri = Uri::new($url);
        } catch (\Throwable $e) {
            return false;
        }

        if (! self::isValidUri($uri)) {
            return false;
        }

        $host = self::normalizeHost($uri->getHost());

        if (! $host) {
            return false;
        }

        try {
            $uri = $uri->withHost($host);
        } catch (\Throwable $e) {
            return false;
        }

        if ($forceBanCheck || self::shouldCheckBans()) {
            if (self::isHostBanned($host)) {
                return false;
            }
        }

        // SSRF guard: when DNS verification is enabled, reject any host that
        // resolves into a non-global (private/reserved/link-local) range. This
        // closes the bypass where a public-looking hostname (e.g.
        // metadata.google.internal) resolves to a reserved address such as
        // 169.254.169.254. resolvePublicIps() fails closed: it returns an empty
        // array if the host does not resolve or any resolved IP is non-global.
        if ($disableDNSCheck !== true && self::shouldCheckDNS()) {
            if (empty(self::resolvePublicIps($host))) {
                return false;
            }
        }

        return $uri->toString();
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

        if ($url === '' || strlen($url) > 4096) {
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
        if (strtolower($uri->getScheme()) !== 'https') {
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

    public static function normalizeHost(?string $host): ?string
    {
        if (! is_string($host) || $host === '') {
            return null;
        }

        $host = strtolower(rtrim($host, '.'));

        if ($host === '' || strlen($host) > 253) {
            return null;
        }

        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
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

    public static function resolvePublicIps(string $host): array
    {
        $host = self::normalizeHost($host);

        if (! $host) {
            return [];
        }

        $key = self::URL_CACHE_PREFIX.
            'public-ips:sha256-'.
            hash('sha256', $host);

        return Cache::remember($key, 60, function () use ($host) {
            $ips = [];

            $aRecords = @dns_get_record($host.'.', DNS_A);

            if (is_array($aRecords)) {
                foreach ($aRecords as $record) {
                    if (! empty($record['ip'])) {
                        $ips[] = $record['ip'];
                    }
                }
            }

            $aaaaRecords = @dns_get_record($host.'.', DNS_AAAA);

            if (is_array($aaaaRecords)) {
                foreach ($aaaaRecords as $record) {
                    if (! empty($record['ipv6'])) {
                        $ips[] = $record['ipv6'];
                    }
                }
            }

            $ips = array_values(array_unique($ips));

            if (empty($ips)) {
                return [];
            }

            foreach ($ips as $ip) {
                if (! self::isPublicIp($ip)) {
                    return [];
                }
            }

            return $ips;
        });
    }

    /**
     * Validate host requirements
     */
    public static function isValidHost(?string $host): bool
    {
        if (! $host || $host === '') {
            return false;
        }

        if (! filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
            return false;
        }

        if (! str_contains($host, '.')) {
            return false;
        }

        if (in_array($host, self::LOCALHOST_DOMAINS)) {
            return false;
        }

        return true;
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
        return app()->environment() === 'production' &&
            (bool) config('security.url.verify_dns');
    }

    /**
     * Validate domain DNS records
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
            fn ($domain) => strtolower(rtrim($domain, '.')),
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
        if ($url) {
            $domain = config('pixelfed.domain.app');
            $uri = Uri::new($url);
            $host = $uri->getHost();

            if (! $host || empty($host)) {
                return false;
            }

            return strtolower($domain) === strtolower($host) ? $url : false;
        }

        return false;
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

        $hash = hash('sha256', $url);
        $key = "helpers:url:fetcher:sha256-{$hash}";
        $ttl = now()->addMinutes(15);

        return Cache::remember($key, $ttl, function () use ($url) {
            $res = ActivityPubFetchService::get($url);
            if (! $res || empty($res)) {
                return false;
            }
            $res = json_decode($res, true, 8);
            if (json_last_error() == JSON_ERROR_NONE) {
                return $res;
            } else {
                return false;
            }
        });
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
            $now = Carbon::now();
            $tenYearsAgo = $now->copy()->subYears(20);
            $isMoreThanTenYearsOld = $date->lt($tenYearsAgo);
            $tomorrow = $now->copy()->addDay();
            $isMoreThanOneDayFuture = $date->gt($tomorrow);

            return ! ($isMoreThanTenYearsOld || $isMoreThanOneDayFuture);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Fetch or create a status from URL
     */
    public static function statusFirstOrFetch(string $url, bool $replyTo = false): ?Status
    {
        if (! $validUrl = self::validateUrl($url)) {
            return null;
        }

        if ($status = self::findExistingStatus($url)) {
            return $status;
        }

        return self::createStatusFromUrl($url, $replyTo);
    }

    /**
     * Find existing status by URL
     */
    public static function findExistingStatus(string $url): ?Status
    {
        $host = parse_url($url, PHP_URL_HOST);

        if (self::isLocalDomain($host)) {
            $id = (int) last(explode('/', $url));

            return Status::whereNotIn('scope', ['draft', 'archived'])
                ->findOrFail($id);
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
    public static function createStatusFromUrl(string $url, bool $replyTo): ?Status
    {
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

        $activity = isset($res['object']) ? $res : ['object' => $res];

        if (! $profile = self::getStatusProfile($activity)) {
            return null;
        }

        if (! self::validateStatusUrls($url, $activity)) {
            return null;
        }

        $reply_to = self::getReplyToId($activity, $profile, $replyTo);
        $scope = self::getScope($activity, $url);
        $cw = self::getSensitive($activity, $url);

        if ($res['type'] === 'Question') {
            return self::storePoll(
                $profile,
                $res,
                $url,
                $res['published'],
                $reply_to,
                $cw,
                $scope,
                $activity['id'] ?? $url
            );
        }

        return self::storeStatus($url, $profile, $res);
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
                ->filter(fn ($o) => $o && isset($o['type']) && $o['type'] == 'Person')
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
     * Get reply-to status ID
     */
    public static function getReplyToId(array $activity, Profile $profile, bool $replyTo): ?int
    {
        $inReplyTo = $activity['object']['inReplyTo'] ?? null;

        if (! $inReplyTo && ! $replyTo) {
            return null;
        }

        $reply = self::statusFirstOrFetch(self::pluckval($inReplyTo), false);

        if (! $reply) {
            return null;
        }

        $blocks = UserFilterService::blocks($reply->profile_id);

        return in_array($profile->id, $blocks) ? null : $reply->id;
    }

    /**
     * Store a new regular status
     */
    public static function storeStatus(string $url, Profile $profile, array $activity): Status
    {
        $id = self::getStatusId($activity, $url);
        $url = self::getStatusUrl($activity, $id);

        if ((! isset($activity['type']) ||
                in_array($activity['type'], ['Create', 'Note'])) &&
            ! self::validateStatusDomains($id, $url)
        ) {
            throw new \Exception(json_encode([
                'message' => 'Invalid status domains',
                'checked' => [
                    'id' => $id,
                    'id_host' => parse_url($id, PHP_URL_HOST),
                    'id_valid_url' => self::validateUrl($id),
                    'url' => $url,
                    'url_host' => parse_url($url, PHP_URL_HOST),
                    'url_valid_url' => self::validateUrl($url),
                ],
                'expected' => 'id host and url host to be valid and match (case-insensitive)',
                'payload' => $activity,
            ]));
        }

        $reply_to = self::getReplyTo($activity);
        $ts = self::pluckval($activity['published']);
        $scope = self::getScope($activity, $url);
        $commentsDisabled = isset($activity['commentsEnabled']) ? (bool) $activity['commentsEnabled'] == false : false;
        $cw = self::getSensitive($activity, $url);

        if ($profile->unlisted) {
            $scope = 'unlisted';
        }

        $status = self::createOrUpdateStatus($url, $profile, $id, $activity, $ts, $reply_to, $cw, $scope, $commentsDisabled);

        if ($reply_to === null) {
            self::importNoteAttachment($activity, $status);
        } else {
            if (isset($activity['attachment']) && ! empty($activity['attachment'])) {
                self::importNoteAttachment($activity, $status);
            }
            StatusReplyPipeline::dispatch($status);
        }

        if (isset($activity['tag']) && is_array($activity['tag']) && ! empty($activity['tag'])) {
            StatusTagsPipeline::dispatch($activity, $status);
        }

        self::handleStatusPostProcessing($status, $profile->id, $url);

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

    /**
     * Create or update status record
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
        bool $commentsDisabled
    ): Status {
        $caption = isset($activity['content']) ?
            app(SanitizeService::class)->html($activity['content']) :
            '';
        $cwSummary = ($cw && isset($activity['summary'])) ?
            app(SanitizeService::class)->html($activity['summary']) :
            null;

        return Status::updateOrCreate(
            ['uri' => $url],
            [
                'profile_id' => $profile->id,
                'url' => $url,
                'object_url' => $id,
                'caption' => strip_tags($caption),
                'rendered' => $caption,
                'created_at' => Carbon::parse($ts)->tz('UTC'),
                'in_reply_to_id' => $reply_to,
                'local' => false,
                'is_nsfw' => $cw,
                'scope' => $scope,
                'visibility' => $scope,
                'cw_summary' => $cwSummary ? strip_tags($cwSummary) : null,
                'comments_disabled' => $commentsDisabled,
            ]
        );
    }

    /**
     * Handle post-creation status processing
     */
    public static function handleStatusPostProcessing(Status $status, int $profileId, string $url): void
    {
        if (
            config('instance.timeline.network.cached') &&
            self::isEligibleForNetwork($status)
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

    public static function getReplyTo($activity)
    {
        $reply_to = null;
        $inReplyTo = isset($activity['inReplyTo']) && ! empty($activity['inReplyTo']) ?
            self::pluckval($activity['inReplyTo']) :
            false;

        if ($inReplyTo) {
            $reply_to = self::statusFirstOrFetch($inReplyTo);
            if ($reply_to) {
                $reply_to = $reply_to?->id;
            }
        } else {
            $reply_to = null;
        }

        return $reply_to;
    }

    public static function getScope($activity, $url)
    {
        $id = isset($activity['id']) ? self::pluckval($activity['id']) : self::pluckval($url);
        $url = isset($activity['url']) ? self::pluckval($activity['url']) : self::pluckval($id);
        $urlDomain = parse_url(self::pluckval($url), PHP_URL_HOST);
        $scope = 'private';

        if (isset($activity['to']) == true) {
            if (is_array($activity['to']) && in_array('https://www.w3.org/ns/activitystreams#Public', $activity['to'])) {
                $scope = 'public';
            }
            if (is_string($activity['to']) && $activity['to'] == 'https://www.w3.org/ns/activitystreams#Public') {
                $scope = 'public';
            }
        }

        if (isset($activity['cc']) == true) {
            if (is_array($activity['cc']) && in_array('https://www.w3.org/ns/activitystreams#Public', $activity['cc'])) {
                $scope = 'unlisted';
            }
            if (is_string($activity['cc']) && $activity['cc'] == 'https://www.w3.org/ns/activitystreams#Public') {
                $scope = 'unlisted';
            }
        }

        if ($scope == 'public' && in_array($urlDomain, InstanceService::getUnlistedDomains())) {
            $scope = 'unlisted';
        }

        return $scope;
    }

    public static function storePoll($profile, $res, $url, $ts, $reply_to, $cw, $scope, $id)
    {
        if (! isset($res['endTime']) || ! isset($res['oneOf']) || ! is_array($res['oneOf']) || count($res['oneOf']) > 4) {
            return;
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
        $status->in_reply_to_id = null;
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

        if (! is_array($object) ||
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
        } catch (UniqueConstraintViolationException $e) {
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
     */
    public static function isLocalDomain(string $host): bool
    {
        return config('pixelfed.domain.app') == $host;
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

        if ($profile && ! self::needsFetch($profile)) {
            return $profile;
        }

        return self::profileUpdateOrCreate($url);
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

        return strtolower($urlDomain) === strtolower($domain);
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
