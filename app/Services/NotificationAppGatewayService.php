<?php

namespace App\Services;

use Cache;
use Exception;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class NotificationAppGatewayService
{
    const GATEWAY_SUPPORT_CHECK = 'px:nags:gateway-support-check';

    public static function config()
    {
        return config('instance.notifications.nag');
    }

    public static function enabled()
    {
        if ((bool) config('instance.notifications.nag.enabled') === false) {
            return false;
        }

        $apiKey = config('instance.notifications.nag.api_key');
        if (! $apiKey || strlen($apiKey) !== 45) {
            return false;
        }

        return Cache::remember(self::GATEWAY_SUPPORT_CHECK, 43200, function () {
            return self::checkServerSupport();
        });
    }

    public static function checkServerSupport()
    {
        $endpoint = 'https://'.config('instance.notifications.nag.endpoint').'/api/v1/instance-check?domain='.config('pixelfed.domain.app');
        try {
            $res = Http::withHeaders(['X-PIXELFED-API' => 1])
                ->retry(3, 500)
                ->throw()
                ->get($endpoint);

            $data = $res->json();
        } catch (RequestException $e) {
            return false;
        } catch (Exception $e) {
            return false;
        }

        if ($res->successful() && isset($data['active']) && $data['active'] === true) {
            return true;
        }

        return false;
    }

    public static function forceSupportRecheck()
    {
        Cache::forget(self::GATEWAY_SUPPORT_CHECK);

        return self::enabled();
    }

    public static function isValidExpoPushToken($token)
    {
        if (! $token) {
            return false;
        }

        if (str_starts_with($token, 'ExponentPushToken[') && mb_strlen($token) < 26) {
            return false;
        }

        if (! str_starts_with($token, 'ExponentPushToken[') && ! str_starts_with($token, 'ExpoPushToken[')) {
            return false;
        }

        if (! str_ends_with($token, ']')) {
            return false;
        }

        return true;
    }

    public static function send($userToken, $type, $actor = '')
    {
        if (! self::enabled()) {
            return false;
        }

        if (! $userToken || ! self::isValidExpoPushToken($userToken)) {
            return false;
        }

        $types = PushNotificationService::NOTIFY_TYPES;

        if (! $type || ! in_array($type, $types)) {
            return false;
        }

        $apiKey = config('instance.notifications.nag.api_key');

        if (! $apiKey) {
            return false;
        }
        $url = 'https://'.config('instance.notifications.nag.endpoint').'/api/v1/relay/deliver';

        try {
            $response = Http::withToken($apiKey)
                ->withHeaders(['X-PIXELFED-API' => 1])
                ->post($url, [
                    'token' => $userToken,
                    'type' => $type,
                    'actor' => $actor,
                ]);

            $response->throw();
        } catch (RequestException $e) {
            return;
        } catch (Exception $e) {
            return;
        }
    }
}
