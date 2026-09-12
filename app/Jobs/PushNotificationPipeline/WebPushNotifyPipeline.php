<?php

namespace App\Jobs\PushNotificationPipeline;

use App\Models\User;
use App\Services\WebPushEndpointGuard;
use App\Services\WebPushService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\ContentEncoding;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

/**
 * Sends a real Web Push (RFC 8291) notification to every subscription a user
 * has registered through PushSubscriptionController — the generic counterpart
 * to the Expo-based *PushNotifyPipeline jobs.
 *
 * Deliberately independent of NotificationAppGatewayService: that gate is
 * about the hosted Expo gateway, which an instance may not have configured at
 * all, and it has no bearing on a self-contained Web Push subscription. It is
 * *not* independent of the user's own notification preferences; see
 * maybeDispatch().
 */
class WebPushNotifyPipeline implements ShouldQueue
{
    use Queueable;

    /**
     * A push service that is briefly down should not cost the user a
     * notification, so transient send failures are retried. Note that with
     * more than one subscription a retry re-sends to any that already
     * succeeded, since the job has no memory across attempts — acceptable
     * because a user normally has one subscription per device and a duplicate
     * banner beats a lost one.
     */
    public $tries = 3;

    public $backoff = [10, 30];

    /**
     * Guzzle's default is 30s. A push endpoint is a small HTTPS POST to a
     * service that either answers promptly or is broken, and every second
     * spent waiting is a blocked pushnotify worker.
     */
    private const REQUEST_TIMEOUT = 10;

    /**
     * How long a follow request stays "announced" while it sits unanswered. A
     * request can wait indefinitely, so this is generous; the cost of it
     * lapsing is one redundant notification, and the cost of it never lapsing
     * is a suppressed follow push years later from someone who was rejected
     * once. FollowRequestObserver shortens it to minutes as soon as the
     * request is actually answered.
     */
    private const FOLLOW_REQUEST_MARKER_TTL = 7776000; // 90 days

    /**
     * The window after an accept in which the `follow` push it triggers is
     * expected to arrive — long enough for a backed-up pushnotify queue, short
     * enough that a stale marker cannot outlive the accept it belongs to.
     */
    public const FOLLOW_REQUEST_MARKER_ACCEPTED_TTL = 900; // 15 minutes

    public $user;

    public $type;

    public $actor;

    public $actorId;

    public $statusId;

    private const TITLES = [
        'like' => 'New Like',
        'follow' => 'New Follower',
        'follow_request' => 'New Follow Request',
        'mention' => 'New Mention',
        'comment' => 'New Comment',
    ];

    private const BODIES = [
        'like' => ':actor liked your post',
        'follow' => ':actor started following you',
        'follow_request' => ':actor requested to follow you',
        'mention' => ':actor mentioned you',
        'comment' => ':actor commented on your post',
    ];

    /**
     * Types with no notify_* column of their own, and the preference they read
     * instead.
     *
     * The notify_* columns cover the four types the Expo path sends. A type
     * without one is not merely unconfigurable: maybeDispatch would read a
     * missing attribute, Eloquent would return null, and the push would be
     * dropped with nothing logged. Aliasing avoids that without adding a
     * column to the users table for a preference no UI currently sets.
     *
     * A follow request is a prospective follower, so notify_follow is the
     * switch a user would expect to govern it.
     */
    private const PREFERENCE_ALIASES = [
        'follow_request' => 'follow',
    ];

    /**
     * Create a new job instance.
     *
     * $actor is the actor's username, used to render the notification body.
     * $actorId and $statusId are optional and carry the ids a client needs to
     * deep-link from the notification to the profile or post it refers to;
     * $statusId is null where no status exists (a follow, or a DM, which has
     * no public status object).
     */
    public function __construct($user, $type, $actor, $actorId = null, $statusId = null)
    {
        $this->user = $user;
        $this->type = $type;
        $this->actor = $actor;
        $this->actorId = $actorId;
        $this->statusId = $statusId;
    }

    /**
     * Single entry point used by every call site, so the preference and
     * subscription checks can't drift apart between them.
     *
     * Respecting notify_enabled and notify_{type} is the whole point: those
     * are the same switches the Expo path honours via
     * PushNotificationService::check(), and a user who turns off like
     * notifications means it regardless of which transport delivers them.
     *
     * Dispatched afterCommit() because several call sites sit inside the
     * transaction that writes the notification row, and a push must not
     * describe something a rollback then undoes. Outside a transaction this
     * is a no-op.
     */
    public static function maybeDispatch($profileId, $type, $actorUsername, $actorId = null, $statusId = null): void
    {
        if (! WebPushService::enabled()) {
            return;
        }

        $user = User::whereProfileId($profileId)->first();

        if (! $user || ! $user->notify_enabled) {
            return;
        }

        $preference = 'notify_'.(self::PREFERENCE_ALIASES[$type] ?? $type);

        if (! $user->{$preference}) {
            return;
        }

        if (! $user->pushSubscriptions()->exists()) {
            return;
        }

        if ($actorId) {
            $marker = self::followRequestMarkerKey($profileId, $actorId);

            // Accepting a follow request creates a Follower row, which sends
            // the accepting user "X started following you" — about their own
            // tap, and about a person they were already told about. One
            // notification per follower, so the accept is swallowed by the
            // request that announced it. See followRequestMarkerKey().
            if ($type === 'follow' && Cache::pull($marker)) {
                return;
            }

            if ($type === 'follow_request') {
                Cache::put($marker, true, self::FOLLOW_REQUEST_MARKER_TTL);
            }
        }

        self::dispatch($user, $type, $actorUsername, $actorId, $statusId)
            ->onQueue('pushnotify')
            ->afterCommit();
    }

    /**
     * Marks that this (target, actor) pair has already been told about a
     * follow request, so the `follow` push that an accept produces can be
     * dropped.
     *
     * Set here rather than at the accept, because there is more than one
     * accept path (ApiV1Controller::accountFollowRequestAccept and
     * AccountController), and any third one added later would silently miss
     * the suppression. Written when the request push goes out and read when
     * the follow push would, so the two are never racing: the marker predates
     * the accept by however long the request sat unanswered.
     *
     * Cleared by FollowRequestObserver::deleted() on a reject or a
     * cancellation, where no accept follows and the next genuine follow must
     * still notify.
     */
    public static function followRequestMarkerKey($profileId, $actorId): string
    {
        return "webpush:follow-request-announced:{$profileId}:{$actorId}";
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $user = $this->user;
        $type = $this->type;
        $actor = $this->actor;

        if (! $user) {
            Log::info('WebPushNotifyPipeline: User not provided, skipping job');

            return;
        }

        $subscriptions = $user->pushSubscriptions;

        if ($subscriptions->isEmpty()) {
            return;
        }

        $title = self::TITLES[$type] ?? 'New notification';
        $body = str_replace(':actor', $actor, self::BODIES[$type] ?? ':actor sent you a notification');

        // Ids go over the wire as strings: they exceed what a JSON number can
        // represent exactly, and a client parsing them as doubles would
        // silently deep-link to the wrong post.
        $payload = json_encode([
            'notification_type' => $type,
            'title' => $title,
            'body' => $body,
            'account_id' => $this->actorId ? (string) $this->actorId : null,
            'status_id' => $this->statusId ? (string) $this->statusId : null,
        ]);

        // Re-inspect every endpoint at send time and pin the connection to the
        // addresses just validated. Checking only at registration leaves a
        // hostile nameserver free to answer with a public address then and a
        // private one now; an endpoint that cannot be pinned is skipped rather
        // than sent unpinned. Matters most on an open-registration instance,
        // where anyone can register an endpoint at all.
        $resolveEntries = [];
        $sendable = [];

        foreach ($subscriptions as $subscription) {
            $result = WebPushEndpointGuard::inspect($subscription->endpoint);

            if (! $result['ok']) {
                Log::warning("WebPushNotifyPipeline: Refusing to send to subscription {$subscription->id}: endpoint {$result['reason']}");

                continue;
            }

            if ($result['resolve'] !== null) {
                $resolveEntries[] = $result['resolve'];
            }

            $sendable[] = $subscription;
        }

        if (empty($sendable)) {
            return;
        }

        $webPush = $this->buildWebPushClient(array_values(array_unique($resolveEntries)));

        $transientFailure = null;

        foreach ($sendable as $subscription) {
            // Subscriptions registered before content_encoding was recorded
            // have it null; every current push service expects aes128gcm,
            // while the library's own default is the legacy "aesgcm", which
            // would silently produce a payload the receiver can't decrypt.
            $sub = new Subscription(
                $subscription->endpoint,
                $subscription->public_key,
                $subscription->auth_token,
                $subscription->content_encoding ?: ContentEncoding::aes128gcm,
            );

            try {
                $report = $webPush->sendOneNotification($sub, $payload);

                if (! $report->isSuccess()) {
                    Log::warning("WebPushNotifyPipeline: Failed to send {$type} notification to subscription {$subscription->id}: ".$report->getReason());

                    // A gone/expired endpoint is permanent — drop it rather
                    // than retrying it forever. Anything else may well work on
                    // the next attempt.
                    if ($report->isSubscriptionExpired()) {
                        $subscription->delete();
                    } else {
                        $transientFailure = $report->getReason();
                    }
                }
            } catch (Exception $e) {
                Log::warning("WebPushNotifyPipeline: Exception sending {$type} notification to subscription {$subscription->id}: ".$e->getMessage());
                $transientFailure = $e->getMessage();
            }
        }

        // Thrown after the loop so one dead subscription doesn't stop the
        // others from being tried on this attempt.
        if ($transientFailure !== null) {
            throw new Exception("WebPushNotifyPipeline: {$type} notification failed, will retry: {$transientFailure}");
        }
    }

    /**
     * @param  array<int, string>  $resolveEntries  CURLOPT_RESOLVE entries pinning each
     *                                              endpoint host to addresses already
     *                                              validated as public.
     */
    private function buildWebPushClient(array $resolveEntries = []): WebPush
    {
        $vapid = config('webpush.vapid');

        $auth = [
            'VAPID' => [
                'subject' => $vapid['subject'] ?: url('/'),
                'publicKey' => $vapid['public_key'],
                'privateKey' => $vapid['private_key'],
            ],
        ];

        if (! empty($vapid['pem_file'])) {
            $auth['VAPID']['pemFile'] = $vapid['pem_file'];
        }

        $clientOptions = [
            // A push service has no business redirecting us, and following one
            // would hand back the DNS control that pinning just took away.
            'allow_redirects' => false,
        ];

        if (! empty($resolveEntries)) {
            $clientOptions['curl'] = [CURLOPT_RESOLVE => $resolveEntries];
        }

        return new WebPush($auth, [], self::REQUEST_TIMEOUT, $clientOptions);
    }
}
