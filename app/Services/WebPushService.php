<?php

namespace App\Services;

use Laravel\Passport\TransientToken;

/**
 * Shared concerns for the Web Push subscription API.
 */
class WebPushService
{
    /**
     * Prefix marking an identifier derived from a web session rather than an
     * OAuth token, so the two can never be confused for one another.
     */
    private const SESSION_PREFIX = 'session:';

    /**
     * Whether this instance can send Web Push at all.
     *
     * VAPID keys are optional — an instance that has never run `webpush:vapid`
     * has none, and there is nothing to sign a push with. Checking this up
     * front lets /api/v1/push/subscription answer honestly instead of
     * accepting a subscription that could never be delivered to, which is the
     * same courtesy NotificationAppGatewayService::enabled() does for the Expo
     * gateway.
     */
    public static function enabled(): bool
    {
        $vapid = config('webpush.vapid');

        if (! empty($vapid['pem_file'])) {
            return true;
        }

        return ! empty($vapid['public_key']) && ! empty($vapid['private_key']);
    }

    /**
     * A stable identifier for the client that registered a subscription, used
     * to scope DELETE /api/v1/push/subscription to the device making the
     * request.
     *
     * A bearer token supplies its own id. A browser signed in through the web
     * UI does not: `CreateFreshApiToken` sits in the `web` middleware group, so
     * Passport authenticates the session cookie and hands back a
     * TransientToken — its stand-in for "this person is physically logged into
     * the application". That class has no `id` property at all, so reading one
     * off it raises an undefined-property warning and yields null, filing every
     * browser subscription under NULL. Two browsers would then share a single
     * identity, and unsubscribing on either would tear down both.
     *
     * The session is what distinguishes one browser from another, so it stands
     * in for the token. It is hashed rather than stored as-is, because a
     * session id is a live credential and a push subscription row is no place
     * to keep one; the prefix keeps the result from ever colliding with a real
     * token id.
     *
     * Returns null when there is nothing to key on, which the caller treats the
     * same as a row predating this column.
     */
    public static function clientIdentifier(?object $token, ?string $sessionId): ?string
    {
        if (! $token instanceof TransientToken) {
            return $token?->id;
        }

        return $sessionId ? self::SESSION_PREFIX.hash('sha256', $sessionId) : null;
    }
}
