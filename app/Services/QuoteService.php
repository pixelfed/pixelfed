<?php

namespace App\Services;

use App\Jobs\QuotePipeline\DeliverQuoteActivityPipeline;
use App\Models\Profile;
use App\Models\QuoteAuthorization;
use App\Models\Status;
use Illuminate\Support\Facades\Cache;

/**
 * FEP-044f: Consent-respecting quote posts.
 *
 * Lets remote actors request approval to quote a local post, issues and
 * serves QuoteAuthorization stamps, and revokes them. Only automatic
 * approval is supported, there is no manual review queue.
 */
class QuoteService
{
    public const POLICY_EVERYONE = 'everyone';

    public const POLICY_FOLLOWERS = 'followers';

    public const POLICY_NOBODY = 'nobody';

    public const POLICIES = [
        self::POLICY_EVERYONE,
        self::POLICY_FOLLOWERS,
        self::POLICY_NOBODY,
    ];

    /**
     * Audience flags for the per-post bitmask in statuses.quote_policy.
     *
     * The low byte holds the automaticApproval audiences, the high byte
     * the manualApproval ones, so a policy like "followers automatically,
     * everyone else after review" fits in one SMALLINT UNSIGNED. Local posts
     * only ever use the automatic half, the rest is there for policies read
     * off remote posts.
     *
     * 0 means nobody. NULL (no override, use the account default) is a
     * different value: never test the column for truthiness, go through
     * statusFlags().
     */
    public const FLAG_PUBLIC = 1;

    public const FLAG_FOLLOWERS = 2;

    public const FLAG_FOLLOWING = 4;

    /** An audience we could not map, e.g. a list of individual actors. */
    public const FLAG_UNSUPPORTED = 8;

    public const FLAGS_NOBODY = 0;

    public const MANUAL_SHIFT = 8;

    public const SUBPOLICY_MASK = 0xFF;

    public const POLICY_FLAGS = [
        self::POLICY_EVERYONE => self::FLAG_PUBLIC,
        self::POLICY_FOLLOWERS => self::FLAG_FOLLOWERS,
        self::POLICY_NOBODY => self::FLAGS_NOBODY,
    ];

    /**
     * Mastodon API `quote_approval_policy` values mapped to ours.
     */
    public const API_POLICIES = [
        'public' => self::POLICY_EVERYONE,
        'followers' => self::POLICY_FOLLOWERS,
        'nobody' => self::POLICY_NOBODY,
    ];

    public const AS_PUBLIC = 'https://www.w3.org/ns/activitystreams#Public';

    public const QUOTABLE_SCOPES = ['public', 'unlisted'];

    /**
     * Status types that are never quotable.
     */
    public const UNQUOTABLE_TYPES = [
        'share',
        'story',
        'story:reply',
        'story:reaction',
        'story:live',
    ];

    /**
     * Properties an inlined quote post may use to point at what it quotes.
     */
    public const QUOTE_PROPERTIES = ['quote', 'quoteUrl', 'quoteUri', '_misskey_quote'];

    private const string POLICY_CACHE_KEY = 'pf:services:quotes:policy:';

    private const int POLICY_CACHE_TTL = 86400;

    /**
     * Term definitions merged into the inline @context of Note and
     * Question objects so `interactionPolicy.canQuote` compacts the same
     * way it does on Mastodon and GoToSocial.
     *
     * @var array<string, mixed>
     */
    public const NOTE_CONTEXT_TERMS = [
        'gts' => 'https://gotosocial.org/ns#',
        'interactionPolicy' => [
            '@id' => 'gts:interactionPolicy',
            '@type' => '@id',
        ],
        'canQuote' => [
            '@id' => 'gts:canQuote',
            '@type' => '@id',
        ],
        'automaticApproval' => [
            '@id' => 'gts:automaticApproval',
            '@type' => '@id',
        ],
        'manualApproval' => [
            '@id' => 'gts:manualApproval',
            '@type' => '@id',
        ],
    ];

    /**
     * @var array<int, mixed>
     */
    public const STAMP_CONTEXT = [
        'https://www.w3.org/ns/activitystreams',
        [
            'gts' => 'https://gotosocial.org/ns#',
            'QuoteAuthorization' => 'https://w3id.org/fep/044f#QuoteAuthorization',
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
     * @var array<int, mixed>
     */
    public const REQUEST_CONTEXT = [
        'https://www.w3.org/ns/activitystreams',
        [
            'QuoteRequest' => 'https://w3id.org/fep/044f#QuoteRequest',
        ],
    ];

    /**
     * The account-wide default canQuote policy a local profile has chosen.
     */
    public static function accountPolicy(Profile $profile): string
    {
        if ($profile->domain !== null) {
            return self::POLICY_NOBODY;
        }

        return Cache::remember(
            self::POLICY_CACHE_KEY.$profile->id,
            self::POLICY_CACHE_TTL,
            function () use ($profile) {
                $policy = $profile->user?->settings?->can_quote;

                return in_array($policy, self::POLICIES, true)
                    ? $policy
                    : self::POLICY_EVERYONE;
            }
        );
    }

    public static function forgetPolicy(int $profileId): void
    {
        Cache::forget(self::POLICY_CACHE_KEY.$profileId);
    }

    /**
     * The effective bitmask for one post: nobody when the post is not
     * public or unlisted, otherwise the per-post override, otherwise the
     * author's account default.
     */
    public static function statusFlags(Status $status): int
    {
        if (! self::isQuotable($status)) {
            return self::FLAGS_NOBODY;
        }

        // Strict null check on purpose, an override of 0 means "nobody"
        if ($status->quote_policy !== null) {
            return (int) $status->quote_policy & 0xFFFF;
        }

        return self::toFlags(self::accountPolicy($status->profile));
    }

    /**
     * The effective policy for one post as a named policy, for the API and
     * anything else that shows it to a person.
     */
    public static function statusPolicy(Status $status): string
    {
        return self::fromFlags(self::statusFlags($status));
    }

    /**
     * Audiences that are approved automatically.
     */
    public static function automatic(int $flags): int
    {
        return $flags & self::SUBPOLICY_MASK;
    }

    /**
     * Audiences that need the author's manual approval.
     */
    public static function manual(int $flags): int
    {
        return ($flags >> self::MANUAL_SHIFT) & self::SUBPOLICY_MASK;
    }

    /**
     * Bitmask for a named policy. Unknown names are treated as nobody.
     */
    public static function toFlags(string $policy): int
    {
        return self::POLICY_FLAGS[$policy] ?? self::FLAGS_NOBODY;
    }

    /**
     * Closest named policy for a bitmask, going by who is approved
     * automatically.
     */
    public static function fromFlags(int $flags): string
    {
        $automatic = self::automatic($flags);

        if ($automatic & self::FLAG_PUBLIC) {
            return self::POLICY_EVERYONE;
        }

        if ($automatic & self::FLAG_FOLLOWERS) {
            return self::POLICY_FOLLOWERS;
        }

        return self::POLICY_NOBODY;
    }

    /**
     * Structural check, independent of who is asking.
     */
    public static function isQuotable(Status $status): bool
    {
        if ($status->reblog_of_id !== null) {
            return false;
        }

        if (in_array($status->type, self::UNQUOTABLE_TYPES, true)) {
            return false;
        }

        if (! in_array($status->scope, self::QUOTABLE_SCOPES, true)) {
            return false;
        }

        $profile = $status->profile;

        return $profile
            && $profile->domain === null
            && $profile->status === null
            && ! $profile->deleted_at;
    }

    /**
     * Translate a Mastodon API `quote_approval_policy` value into the
     * bitmask stored on the post. Returns null for anything unknown, which
     * means "no override, use the account default". Note that "nobody" is
     * a valid override and comes back as 0, not null.
     */
    public static function fromApiPolicy(mixed $value): ?int
    {
        if (! is_string($value) || ! isset(self::API_POLICIES[$value])) {
            return null;
        }

        return self::toFlags(self::API_POLICIES[$value]);
    }

    public static function toApiPolicy(int $flags): string
    {
        $key = array_search(self::fromFlags($flags), self::API_POLICIES, true);

        return is_string($key) ? $key : 'nobody';
    }

    /**
     * The actors and collections a set of audience flags stands for.
     *
     * @return array<int, string>
     */
    public static function audienceUris(int $subpolicy, Profile $profile): array
    {
        $uris = [];

        if ($subpolicy & self::FLAG_PUBLIC) {
            $uris[] = self::AS_PUBLIC;
        }

        if ($subpolicy & self::FLAG_FOLLOWERS) {
            $uris[] = $profile->permalink('/followers');
        }

        if ($subpolicy & self::FLAG_FOLLOWING) {
            $uris[] = $profile->permalink('/following');
        }

        return $uris;
    }

    /**
     * interactionPolicy fragment for a Note or Question.
     *
     * Per the FEP an empty array is equivalent to a missing property under
     * JSON-LD canonicalization, so "nobody" is expressed as the author's
     * own id. manualApproval is only emitted when it has something in it.
     *
     * @return array<string, mixed>
     */
    public static function interactionPolicy(Status $status): array
    {
        $profile = $status->profile;
        $flags = self::statusFlags($status);

        $automatic = self::audienceUris(self::automatic($flags), $profile);
        $manual = self::audienceUris(self::manual($flags), $profile);

        $policy = [
            'automaticApproval' => $automatic ?: [$profile->permalink()],
        ];

        if ($manual) {
            $policy['manualApproval'] = $manual;
        }

        return [
            'canQuote' => $policy,
        ];
    }

    /**
     * May $actor quote $status, based on policy and blocks?
     *
     * Previously revoked quotes are handled by the caller, since that
     * decision is per quote post rather than per actor.
     */
    public static function canQuote(Status $status, Profile $actor): bool
    {
        if (! self::isQuotable($status)) {
            return false;
        }

        if ($actor->domain === null || $actor->status !== null) {
            return false;
        }

        $target = $status->profile;

        if (AccountService::blocksDomain($target->id, $actor->domain)) {
            return false;
        }

        $blocks = UserFilterService::blocks($target->id);

        if ($blocks && in_array($actor->id, $blocks)) {
            return false;
        }

        // Only automatic approval is acted on. There is no manual review
        // queue yet, so an audience listed under manualApproval is refused.
        $automatic = self::automatic(self::statusFlags($status));

        if ($automatic & self::FLAG_PUBLIC) {
            return true;
        }

        if (($automatic & self::FLAG_FOLLOWERS) && FollowerService::follows($actor->id, $target->id)) {
            return true;
        }

        if (($automatic & self::FLAG_FOLLOWING) && FollowerService::follows($target->id, $actor->id)) {
            return true;
        }

        return false;
    }

    public static function find(Status $status, string $quoteUrl): ?QuoteAuthorization
    {
        return QuoteAuthorization::whereStatusId($status->id)
            ->whereQuoteUrl($quoteUrl)
            ->first();
    }

    /**
     * Issue (or re-use) the stamp for a quote post.
     */
    public static function authorize(
        Status $status,
        Profile $actor,
        string $quoteUrl,
        ?string $requestUrl = null
    ): QuoteAuthorization {
        $auth = self::find($status, $quoteUrl);

        if ($auth instanceof QuoteAuthorization) {
            return $auth;
        }

        $auth = new QuoteAuthorization;
        $auth->id = SnowflakeService::next();
        $auth->profile_id = $status->profile_id;
        $auth->status_id = $status->id;
        $auth->actor_id = $actor->id;
        $auth->quote_url = $quoteUrl;
        $auth->request_url = $requestUrl;
        $auth->state = QuoteAuthorization::STATE_APPROVED;
        $auth->save();

        return $auth;
    }

    /**
     * Withdraw consent. The row is kept (state revoked) so a repeat request
     * for the same quote post is auto-rejected, and the stamp URL serves
     * 410 Gone. The Delete is delivered from a queued job, with retries.
     */
    public static function revoke(QuoteAuthorization $auth, bool $notify = true): void
    {
        if ($auth->isRevoked()) {
            return;
        }

        $auth->state = QuoteAuthorization::STATE_REVOKED;
        $auth->revoked_at = now();
        $auth->save();

        if ($notify) {
            self::sendDelete($auth);
        }
    }

    /**
     * Revoke every stamp a profile issued to a specific remote actor.
     * Used when the profile blocks that actor.
     */
    public static function revokeForActor(int $profileId, int $actorId): void
    {
        QuoteAuthorization::whereProfileId($profileId)
            ->whereActorId($actorId)
            ->approved()
            ->chunkById(100, function ($auths) {
                $auths->each(fn (QuoteAuthorization $auth) => self::revoke($auth));
            });
    }

    /**
     * Revoke every stamp a profile issued to actors on a domain.
     * Used when the profile blocks that domain.
     */
    public static function revokeForDomain(int $profileId, string $domain): void
    {
        $domain = strtolower($domain);

        QuoteAuthorization::whereProfileId($profileId)
            ->approved()
            ->whereIn('actor_id', Profile::where('domain', $domain)->select('id'))
            ->chunkById(100, function ($auths) {
                $auths->each(fn (QuoteAuthorization $auth) => self::revoke($auth));
            });
    }

    /**
     * Drop the stamp for a quote post whose author deleted it. Nothing is
     * federated, the quote is already gone on their side.
     */
    public static function forgetQuote(int $actorId, string $quoteUrl): void
    {
        QuoteAuthorization::whereActorId($actorId)
            ->whereQuoteUrl($quoteUrl)
            ->delete();
    }

    /**
     * The QuoteAuthorization object served at the stamp URL and embedded
     * in the revocation Delete. References only, per the FEP neither the
     * quote post nor the quoted post may be inlined here.
     *
     * @return array<string, mixed>
     */
    public static function stampObject(QuoteAuthorization $auth): array
    {
        return [
            '@context' => self::STAMP_CONTEXT,
            'id' => $auth->permalink(),
            'type' => 'QuoteAuthorization',
            'attributedTo' => $auth->profile->permalink(),
            'interactingObject' => $auth->quote_url,
            'interactionTarget' => $auth->status->url(),
        ];
    }

    /**
     * The QuoteRequest as embedded in our Accept or Reject.
     *
     * @return array<string, mixed>
     */
    public static function requestObject(Profile $actor, Status $status, string $quoteUrl, string $requestUrl): array
    {
        return [
            'id' => $requestUrl,
            'type' => 'QuoteRequest',
            'actor' => $actor->permalink(),
            'object' => $status->url(),
            'instrument' => $quoteUrl,
        ];
    }

    /**
     * Accept for a QuoteRequest, with the stamp as its `result`.
     *
     * @return array<string, mixed>
     */
    public static function acceptActivity(QuoteAuthorization $auth, string $requestUrl): array
    {
        $target = $auth->profile;

        return [
            '@context' => self::REQUEST_CONTEXT,
            'id' => $target->permalink('#accepts/quotes/'.$auth->id),
            'type' => 'Accept',
            'actor' => $target->permalink(),
            'to' => $auth->actor->permalink(),
            'object' => self::requestObject($auth->actor, $auth->status, $auth->quote_url, $requestUrl),
            'result' => $auth->permalink(),
        ];
    }

    /**
     * Reject for a QuoteRequest.
     *
     * @return array<string, mixed>
     */
    public static function rejectActivity(Status $status, Profile $actor, string $quoteUrl, string $requestUrl): array
    {
        $target = $status->profile;

        return [
            '@context' => self::REQUEST_CONTEXT,
            'id' => $target->permalink('#rejects/quotes/'.hash('xxh3', $requestUrl)),
            'type' => 'Reject',
            'actor' => $target->permalink(),
            'to' => $actor->permalink(),
            'object' => self::requestObject($actor, $status, $quoteUrl, $requestUrl),
        ];
    }

    /**
     * Delete that revokes a stamp.
     *
     * @return array<string, mixed>
     */
    public static function deleteActivity(QuoteAuthorization $auth): array
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

    public static function sendAccept(QuoteAuthorization $auth, string $requestUrl): void
    {
        self::deliver($auth->profile, $auth->actor, self::acceptActivity($auth, $requestUrl));
    }

    public static function sendReject(Status $status, Profile $actor, string $quoteUrl, string $requestUrl): void
    {
        self::deliver($status->profile, $actor, self::rejectActivity($status, $actor, $quoteUrl, $requestUrl));
    }

    public static function sendDelete(QuoteAuthorization $auth): void
    {
        if (! $auth->profile || ! $auth->actor || ! $auth->status) {
            return;
        }

        self::deliver($auth->profile, $auth->actor, self::deleteActivity($auth));
    }

    /**
     * Hand the activity to a queued job that retries on temporary failures.
     * The quoting server sends its QuoteRequest once, so a lost Accept would
     * leave the quote pending on their side for good.
     *
     * @param  array<string, mixed>  $activity
     */
    private static function deliver(Profile $from, Profile $to, array $activity): void
    {
        DeliverQuoteActivityPipeline::dispatch($from->id, $to->id, $activity)->onQueue('high');
    }

    /**
     * Same URL, ignoring scheme/host case and a trailing slash.
     */
    public static function sameUrl(?string $a, ?string $b): bool
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

            return strtolower($parts['scheme'] ?? 'https').'://'.strtolower($parts['host']).$path;
        };

        $na = $norm($a);

        return $na !== null && $na === $norm($b);
    }
}
