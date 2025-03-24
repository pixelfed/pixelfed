<?php

namespace App\Services;

use App\User;

class PushNotificationService {

    public const NOTIFY_TYPES = ['follow', 'like', 'mention', 'comment'];

    public const PUSH_GATEWAY_VERSION = '1.0';

    public static function check($listId, $memberId) {
        $user = User::where('notify_enabled', true)->where('profile_id', $memberId)->first();
        return $user ? (bool) $user->{"notify_{$listId}"} : false;
    }
}
