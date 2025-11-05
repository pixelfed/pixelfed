<?php

namespace App\Util\ActivityPub;

use App\Instance;
use App\Jobs\AvatarPipeline\RemoteAvatarFetch;
use App\Jobs\HomeFeedPipeline\FeedInsertRemotePipeline;
use App\Jobs\MediaPipeline\MediaStoragePipeline;
use App\Jobs\StatusPipeline\StatusReplyPipeline;
use App\Jobs\StatusPipeline\StatusTagsPipeline;
use App\Media;
use App\Models\ModeratedProfile;
use App\Models\Poll;
use App\Profile;
use App\Services\Account\AccountStatService;
use App\Services\ActivityPubDeliveryService;
use App\Services\ActivityPubFetchService;
use App\Services\DomainService;
use App\Services\InstanceService;
use App\Services\MediaPathService;
use App\Services\NetworkTimelineService;
use App\Services\SanitizeService;
use App\Services\UserFilterService;
use App\Status;
use App\Util\Media\License;
use Cache;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use League\Uri\Exceptions\UriException;
use League\Uri\Uri;
use Purify;
use Validator;

class Helpers
{
    private const PUBLIC_TIMELINE = 'https://www.w3.org/ns/activitystreams#Public';

    private const CACHE_TTL = 14440;

    private const URL_CACHE_PREFIX = 'helpers:url:';

    private const FETCH_CACHE_TTL = 15;

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

        $activity = $data['object'];
        $mimeTypes = explode(',', config_cache('pixelfed.media_types'));
        $mediaTypes = in_array('video/mp4', $mimeTypes) ?
            ['Document', 'Image', 'Video'] :
            ['Document', 'Image'];

        if (! isset($activity['attachment']) || empty($activity['attachment'])) {
            return false;
        }

        return Validator::make($activity['attachment'], [
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
     * Validate URL with various security and format checks
     */
    public static function validateUrl(?string $url, bool $disableDNSCheck = false, bool $forceBanCheck = false): string|bool
    {
        if (! $normalizedUrl = self::normalizeUrl($url)) {
            return false;
        }

        try {
            $uri = Uri::new($normalizedUrl);

            if (! self::isValidUri($uri)) {
                return false;
            }

            $host = $uri->getHost();
            if (! self::isValidHost($host)) {
                return false;
            }

            if (! $disableDNSCheck && ! self::passesSecurityChecks($host, $disableDNSCheck, $forceBanCheck)) {
                return false;
            }

            return $uri->toString();

        } catch (UriException $e) {
            return false;
        }
    }

    /**
     * Normalize URL input
     */
    public static function normalizeUrl(?string $url): ?string
    {
        if (is_array($url) && ! empty($url)) {
            $url = $url[0];
        }

        return (! $url || strlen($url) === 0) ? null : $url;
    }

    /**
     * Validate basic URI requirements
     */
    public static function isValidUri(Uri $uri): bool
    {
        return $uri && $uri->getScheme() === 'https';
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
        $bannedInstances = InstanceService::getBannedDomains();

        return in_array($host, $bannedInstances);
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
        $id = isset($activity['id']) ?
            self::pluckval($activity['id']) :
            self::pluckval($url);

        $idDomain = parse_url($id, PHP_URL_HOST);
        $urlDomain = parse_url($url, PHP_URL_HOST);

        return $idDomain && $urlDomain;
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
            ! self::validateStatusDomains($id, $url)) {
            throw new \Exception('Invalid status domains');
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
        return self::validateUrl($id) && self::validateUrl($url);
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
        if (config('instance.timeline.network.cached') &&
            self::isEligibleForNetwork($status)
        ) {
            $urlDomain = parse_url($url, PHP_URL_HOST);
            $filteredDomains = self::getFilteredDomains();

            if (! in_array($urlDomain, $filteredDomains)) {
                NetworkTimelineService::add($status->id);
            }
        }

        AccountStatService::incrementPostCount($profileId);

        if ($status->in_reply_to_id === null &&
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
                $reply_to = optional($reply_to)->id;
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
            self::handleMediaStorage($mediaModel);
        }

        $status->viewType();
    }

    /**
     * Get attachments from ActivityPub data
     */
    public static function getAttachments(array $data): array
    {
        return isset($data['object']) ?
            $data['object']['attachment'] :
            $data['attachment'];
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
     * Create media attachment record
     */
    public static function createMediaAttachment(array $media, Status $status, int $key): Media
    {
        $mediaModel = new Media;

        self::setBasicMediaAttributes($mediaModel, $media, $status, $key);
        self::setOptionalMediaAttributes($mediaModel, $media);

        $mediaModel->save();

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
            \App\Jobs\InstancePipeline\FetchNodeinfoPipeline::dispatch($instance)
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
        if (! $profile->last_fetched_at ||
            $profile->last_fetched_at->lt(now()->subMonths(3))
        ) {
            RemoteAvatarFetch::dispatch($profile);
        }

        $profile->last_fetched_at = now();
        $profile->save();
    }

    public static function profileFetch($url): ?Profile
    {
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
