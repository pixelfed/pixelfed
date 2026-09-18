<?php

namespace App\Services;

use App\Jobs\FeaturedCollectionPipeline\RevokeFeatureAuthorizationPipeline;
use App\Models\FeatureAuthorization;
use App\Models\Profile;
use App\Util\ActivityPub\Helpers;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FeaturedCollectionService
{
    public const POLICY_EVERYONE = 'everyone';

    public const POLICY_FOLLOWERS = 'followers';

    public const POLICY_NOBODY = 'nobody';

    public const POLICIES = [
        self::POLICY_EVERYONE,
        self::POLICY_FOLLOWERS,
        self::POLICY_NOBODY,
    ];

    public const AS_PUBLIC = 'https://www.w3.org/ns/activitystreams#Public';

    public const COLLECTION_TYPE = 'FeaturedCollection';

    private const string POLICY_CACHE_KEY = 'pf:services:featured:policy:';

    private const int POLICY_CACHE_TTL = 86400;

    /**
     * Same inline term definitions Mastodon emits, so the stamp and the
     * activities that reference it compact identically on both sides.
     *
     * @var array<int, mixed>
     */
    public const CONTEXT = [
        'https://www.w3.org/ns/activitystreams',
        [
            'gts' => 'https://gotosocial.org/ns#',
            'FeatureAuthorization' => 'https://w3id.org/fep/7aa9#FeatureAuthorization',
            'interactingObject' => [
                '@id' => 'gts:interactingObject',
                '@type' => '@id',
            ],
            'interactionTarget' => [
                '@id' => 'gts:interactionTarget',
                '@type' => '@id',
            ],
        ],
    ];

    /**
     * The canFeature policy a local profile has chosen.
     */
    public static function policy(Profile $profile): string
    {
        if ($profile->domain !== null) {
            return self::POLICY_NOBODY;
        }

        return Cache::remember(
            self::POLICY_CACHE_KEY.$profile->id,
            self::POLICY_CACHE_TTL,
            function () use ($profile) {
                $settings = $profile->user?->settings;

                $policy = $settings?->can_feature;

                return in_array($policy, self::POLICIES, true)
                    ? $policy
                    : self::POLICY_EVERYONE;
            }
        );
    }

    public static function forgetPolicy(int $profileId): void
    {
        Cache::forget(self::POLICY_CACHE_KEY.$profileId);
        Cache::forget('pf:activitypub:user-object:by-id:'.$profileId);
    }

    /**
     * interactionPolicy fragment for the actor document.
     *
     * Automatic approval only. Per the FEP an empty array is equivalent to
     * a missing property under JSON-LD canonicalization, so "nobody" is
     * expressed as the actor's own id.
     *
     * @return array<string, mixed>
     */
    public static function interactionPolicy(Profile $profile): array
    {
        $uri = match (self::policy($profile)) {
            self::POLICY_EVERYONE => self::AS_PUBLIC,
            self::POLICY_FOLLOWERS => $profile->permalink('/followers'),
            default => $profile->permalink(),
        };

        return [
            'canFeature' => [
                'automaticApproval' => [$uri],
            ],
        ];
    }

    /**
     * May $actor feature $target, based on policy and blocks?
     *
     * Previously revoked collections are handled by the caller, since that
     * decision is per collection rather than per actor.
     */
    public static function canFeature(Profile $target, Profile $actor): bool
    {
        if ($target->domain !== null || $target->status !== null) {
            return false;
        }

        if ($actor->domain === null || $actor->status !== null) {
            return false;
        }

        if (AccountService::blocksDomain($target->id, $actor->domain)) {
            return false;
        }

        $blocks = UserFilterService::blocks($target->id);

        if ($blocks && in_array($actor->id, $blocks)) {
            return false;
        }

        return match (self::policy($target)) {
            self::POLICY_EVERYONE => true,
            self::POLICY_FOLLOWERS => (bool) FollowerService::follows($actor->id, $target->id),
            default => false,
        };
    }

    /**
     * Fetch a remote FeaturedCollection and confirm it belongs to $owner.
     *
     * Returns a small normalized array, or null when the collection cannot
     * be fetched, is not a FeaturedCollection, or is attributed to someone
     * else.
     *
     * @return array{id: string, name: ?string, summary: ?string}|null
     */
    public static function fetchCollection(string $url, Profile $owner): ?array
    {
        $body = ActivityPubFetchService::get($url);

        if (! $body || ! is_string($body)) {
            return null;
        }

        $json = json_decode($body, true);

        if (! is_array($json)) {
            return null;
        }

        $types = (array) ($json['type'] ?? []);

        if (! in_array(self::COLLECTION_TYPE, $types, true)) {
            return null;
        }

        $id = $json['id'] ?? null;

        // The document must be the collection we were pointed at.
        if (! is_string($id) || ! self::sameActor($id, $url)) {
            return null;
        }

        $attributedTo = Helpers::pluckval($json['attributedTo'] ?? null);

        if (is_array($attributedTo)) {
            $attributedTo = $attributedTo['id'] ?? null;
        }

        if (! is_string($attributedTo) || ! self::sameActor($attributedTo, $owner->remote_url)) {
            return null;
        }

        return [
            'id' => $id,
            'name' => self::cleanText($json['name'] ?? null, 200),
            'summary' => self::cleanText($json['summary'] ?? null, 500),
        ];
    }

    public static function find(Profile $target, string $collectionUrl): ?FeatureAuthorization
    {
        return FeatureAuthorization::whereProfileId($target->id)
            ->whereCollectionUrl($collectionUrl)
            ->first();
    }

    /**
     * Issue (or re-use) the stamp for a collection.
     */
    public static function authorize(
        Profile $target,
        Profile $actor,
        string $collectionUrl,
        ?string $requestUrl = null,
        ?string $collectionName = null
    ): FeatureAuthorization {
        $auth = self::find($target, $collectionUrl);

        if ($auth instanceof FeatureAuthorization) {
            if ($collectionName !== null && $auth->collection_name !== $collectionName) {
                $auth->collection_name = $collectionName;
                $auth->save();
            }

            return $auth;
        }

        $auth = new FeatureAuthorization;
        $auth->id = SnowflakeService::next();
        $auth->profile_id = $target->id;
        $auth->actor_id = $actor->id;
        $auth->collection_url = $collectionUrl;
        $auth->collection_name = $collectionName;
        $auth->request_url = $requestUrl;
        $auth->state = FeatureAuthorization::STATE_APPROVED;
        $auth->save();

        return $auth;
    }

    /**
     * Withdraw consent. The row is kept (state revoked) so a repeat request
     * from the same collection is auto-rejected, and the stamp URL serves
     * 410 Gone. The Delete activity is sent from a queued job.
     */
    public static function revoke(FeatureAuthorization $auth, bool $notify = true): void
    {
        if ($auth->isRevoked()) {
            return;
        }

        $auth->state = FeatureAuthorization::STATE_REVOKED;
        $auth->revoked_at = now();
        $auth->save();

        if ($notify) {
            RevokeFeatureAuthorizationPipeline::dispatch($auth->id)->onQueue('high');
        }
    }

    /**
     * Revoke every stamp a profile issued to a specific remote actor.
     * Used when the profile blocks that actor.
     */
    public static function revokeForActor(int $profileId, int $actorId): void
    {
        FeatureAuthorization::whereProfileId($profileId)
            ->whereActorId($actorId)
            ->approved()
            ->get()
            ->each(fn (FeatureAuthorization $auth) => self::revoke($auth));
    }

    /**
     * Revoke every stamp a profile issued to actors on a domain.
     * Used when the profile blocks that domain.
     */
    public static function revokeForDomain(int $profileId, string $domain): void
    {
        $domain = strtolower($domain);

        FeatureAuthorization::whereProfileId($profileId)
            ->approved()
            ->with('actor')
            ->get()
            ->filter(fn (FeatureAuthorization $auth): bool => $auth->actor && strtolower((string) $auth->actor->domain) === $domain)
            ->each(fn (FeatureAuthorization $auth) => self::revoke($auth));
    }

    /**
     * The FeatureAuthorization object served at the stamp URL and embedded
     * in the revocation Delete.
     *
     * @return array<string, mixed>
     */
    public static function stampObject(FeatureAuthorization $auth): array
    {
        return [
            '@context' => self::CONTEXT,
            'id' => $auth->permalink(),
            'type' => 'FeatureAuthorization',
            'interactingObject' => $auth->collection_url,
            'interactionTarget' => $auth->profile->permalink(),
        ];
    }

    /**
     * Accept for a FeatureRequest. `object` is the request's id and
     * `result` is the stamp, matching Mastodon's AcceptFeatureRequest.
     *
     * @return array<string, mixed>
     */
    public static function acceptActivity(FeatureAuthorization $auth, string $requestUrl): array
    {
        $target = $auth->profile;

        return [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id' => $target->permalink('#accepts/features/'.$auth->id),
            'type' => 'Accept',
            'actor' => $target->permalink(),
            'to' => $auth->actor->permalink(),
            'object' => $requestUrl,
            'result' => $auth->permalink(),
        ];
    }

    /**
     * Reject for a FeatureRequest.
     *
     * @return array<string, mixed>
     */
    public static function rejectActivity(Profile $target, Profile $actor, string $requestUrl): array
    {
        return [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id' => $target->permalink('#rejects/features/'.hash('xxh3', $requestUrl)),
            'type' => 'Reject',
            'actor' => $target->permalink(),
            'to' => $actor->permalink(),
            'object' => $requestUrl,
        ];
    }

    /**
     * Delete that revokes a stamp. The full object is embedded so the
     * receiver can match on either the id or the fields.
     *
     * @return array<string, mixed>
     */
    public static function deleteActivity(FeatureAuthorization $auth): array
    {
        $object = self::stampObject($auth);

        $context = $object['@context'];

        unset($object['@context']);

        return [
            '@context' => $context,
            'id' => $auth->permalink().'#delete',
            'type' => 'Delete',
            'actor' => $auth->profile->permalink(),
            'to' => $auth->actor->permalink(),
            'object' => $object,
        ];
    }

    public static function sendAccept(FeatureAuthorization $auth, string $requestUrl): void
    {
        self::deliver($auth->profile, $auth->actor, self::acceptActivity($auth, $requestUrl));
    }

    public static function sendReject(Profile $target, Profile $actor, string $requestUrl): void
    {
        self::deliver($target, $actor, self::rejectActivity($target, $actor, $requestUrl));
    }

    public static function sendDelete(FeatureAuthorization $auth): void
    {
        if (! $auth->profile || ! $auth->actor) {
            return;
        }

        self::deliver($auth->profile, $auth->actor, self::deleteActivity($auth));
    }

    /**
     * @param  array<string, mixed>  $activity
     */
    private static function deliver(Profile $from, Profile $to, array $activity): void
    {
        $inbox = $to->sharedInbox ?? $to->inbox_url;

        if (! $inbox) {
            Log::info('FeaturedCollectionService: remote actor has no inbox', [
                'profile_id' => $from->id,
                'actor_id' => $to->id,
            ]);

            return;
        }

        Helpers::sendSignedObject($from, $inbox, $activity);
    }

    public static function sameHost(string $a, string $b): bool
    {
        $ha = parse_url($a, PHP_URL_HOST);
        $hb = parse_url($b, PHP_URL_HOST);

        return is_string($ha) && is_string($hb) && strtolower($ha) === strtolower($hb);
    }

    /**
     * Same actor id, ignoring scheme/host case and a trailing slash.
     */
    public static function sameActor(?string $a, ?string $b): bool
    {
        if (! is_string($a) || ! is_string($b)) {
            return false;
        }

        $norm = function (string $url): ?string {
            $parts = parse_url($url);

            if (! is_array($parts) || ! isset($parts['host'])) {
                return null;
            }

            $path = rtrim($parts['path'] ?? '/', '/');

            return strtolower($parts['scheme'] ?? 'https').'://'.strtolower($parts['host']).($path);
        };

        $na = $norm($a);

        return $na !== null && $na === $norm($b);
    }

    private static function cleanText(mixed $value, int $max): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim(strip_tags($value));

        if ($value === '') {
            return null;
        }

        return Str::limit($value, $max, '');
    }
}
