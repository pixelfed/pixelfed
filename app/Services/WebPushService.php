<?php

namespace App\Services;

/**
 * Whether this instance can send Web Push at all.
 *
 * VAPID keys are optional — an instance that has never run `webpush:vapid`
 * has none, and there is nothing to sign a push with. Checking this up front
 * lets /api/v1/push/subscription answer honestly instead of accepting a
 * subscription that could never be delivered to, which is the same courtesy
 * NotificationAppGatewayService::enabled() does for the Expo gateway.
 */
class WebPushService
{
    public static function enabled(): bool
    {
        $vapid = config('webpush.vapid');

        if (! empty($vapid['pem_file'])) {
            return true;
        }

        return ! empty($vapid['public_key']) && ! empty($vapid['private_key']);
    }
}
