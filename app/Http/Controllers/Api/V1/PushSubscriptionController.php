<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Rules\WebPushEndpoint;
use App\Services\WebPushService;
use Illuminate\Http\Request;
use Minishlink\WebPush\ContentEncoding;

/**
 * Mastodon-compatible Web Push subscription API
 * (POST/DELETE /api/v1/push/subscription).
 *
 * This is a generic Web Push (RFC 8291) subscription endpoint that any
 * client can use — a browser, a PWA, or a native app fronted by its own
 * push relay. It is distinct from the Expo-based endpoints under
 * /api/v1.1/push/*, which are gated on the X-PIXELFED-APP header and route
 * through Pixelfed's hosted notification gateway.
 *
 * Everything this needs is already in the tree: the webpush package, the
 * push_subscriptions table, the HasPushSubscriptions trait on User, the
 * `push` OAuth scope, and the VAPID public key that /api/v2/instance
 * already advertises under configuration.vapid.public_key.
 */
class PushSubscriptionController extends Controller
{
    /**
     * Every registered endpoint is one more outbound HTTPS request the queue
     * worker makes per notification, so an account that keeps registering new
     * endpoints without unsubscribing would multiply the cost of every like it
     * receives. In practice a user has one per device.
     */
    private const MAX_SUBSCRIPTIONS_PER_USER = 10;

    public function json($res, $code = 200, $headers = [])
    {
        return response()->json($res, $code, $headers, JSON_UNESCAPED_SLASHES);
    }

    public function store(Request $request)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('push'), 403);
        abort_unless(WebPushService::enabled(), 404, 'Push notifications are not supported on this server.');

        $this->validate($request, [
            'subscription.endpoint' => ['required', 'string', 'max:500', new WebPushEndpoint],
            'subscription.keys.p256dh' => 'required|string|max:191',
            'subscription.keys.auth' => 'required|string|max:191',
        ]);

        $user = $request->user();
        $sub = $request->input('subscription');

        $this->pruneOldestSubscriptions($user, $sub['endpoint']);

        // content_encoding is recorded rather than left null so the row
        // describes what it actually is; the sending pipeline no longer has to
        // assume, and the library's legacy "aesgcm" default can't creep in.
        $subscription = $user->updatePushSubscription(
            $sub['endpoint'],
            $sub['keys']['p256dh'],
            $sub['keys']['auth'],
            ContentEncoding::aes128gcm->value,
        );

        $subscription->access_token_id = $user->token()->id;
        $subscription->save();

        // notify_enabled defaults to false for every account and is only ever
        // written by the /api/v1.1/user/settings and /api/v1.1/push/update
        // endpoints, both gated on X-PIXELFED-APP — so a generic Web Push
        // client has no way to set it. Without this, registering here
        // succeeds, the row lands in push_subscriptions, and then
        // WebPushNotifyPipeline::maybeDispatch() returns on its very first
        // check: the user sees "push enabled" in their client and never
        // receives anything, with nothing queued, logged or failed to explain
        // it. Registering a subscription is itself the opt-in, so treat it as
        // one — the same way /api/v1.1/push/update sets notify_enabled when
        // the official app registers its Expo token. The per-type notify_*
        // switches are left alone: those are real preferences the user may
        // have deliberately turned off.
        if (! $user->notify_enabled) {
            $user->notify_enabled = true;
            $user->save();
        }

        return $this->json($this->subscriptionResource($subscription, $user));
    }

    public function destroy(Request $request)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('push'), 403);

        $user = $request->user();

        // Per access token, matching Mastodon: unsubscribing on one device
        // must not tear down every other device's subscription.
        $deleted = $user->pushSubscriptions()
            ->where('access_token_id', $user->token()->id)
            ->delete();

        // Rows created before access_token_id existed have none recorded, so
        // there is nothing to match them on. Clearing them when a token finds
        // no subscription of its own keeps unsubscribe working across the
        // upgrade, and they disappear for good once clients re-register.
        if ($deleted === 0) {
            $user->pushSubscriptions()->whereNull('access_token_id')->delete();
        }

        return $this->json([]);
    }

    /**
     * Keeps a user under MAX_SUBSCRIPTIONS_PER_USER, dropping the oldest
     * first. Re-registering an endpoint that already exists is an update
     * rather than an addition, so it is excluded from the count.
     */
    private function pruneOldestSubscriptions($user, string $endpoint): void
    {
        $existing = $user->pushSubscriptions()->where('endpoint', $endpoint)->exists();

        if ($existing) {
            return;
        }

        $excess = $user->pushSubscriptions()->count() - (self::MAX_SUBSCRIPTIONS_PER_USER - 1);

        if ($excess <= 0) {
            return;
        }

        $user->pushSubscriptions()
            ->oldest('id')
            ->limit($excess)
            ->get()
            ->each
            ->delete();
    }

    /**
     * Mirrors Mastodon's PushSubscription entity. `alerts` reflects the
     * account's notification preferences, which is what the sending pipeline
     * actually honours.
     *
     * Divergence worth knowing: Mastodon stores `alerts` per subscription and
     * lets a client set them through `data[alerts][…]` on this endpoint. There
     * is no per-subscription column for that here, so inbound `data` is
     * ignored and these values are read from the account-wide preferences
     * instead. Per-type opt-out still works — it is set in Pixelfed's own
     * notification settings rather than through this API.
     */
    private function subscriptionResource($subscription, $user): array
    {
        return [
            'id' => (string) $subscription->id,
            'endpoint' => $subscription->endpoint,
            'standard' => true,
            'alerts' => [
                'follow' => (bool) $user->notify_enabled && (bool) $user->notify_follow,
                'favourite' => (bool) $user->notify_enabled && (bool) $user->notify_like,
                'mention' => (bool) $user->notify_enabled && (bool) $user->notify_mention,
                'reblog' => false,
                'poll' => false,
                'status' => false,
            ],
            'server_key' => config('webpush.vapid.public_key'),
        ];
    }
}
