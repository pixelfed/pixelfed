<?php

namespace App\Services;

use App\Media;
use App\User;

class UserStorageService
{
    const CACHE_KEY = 'pf:services:user-storage:byId:';

    public static function get($id)
    {
        $user = User::find($id);
        if (! $user || $user->status) {
            return -1;
        }

        if ($user->storage_used_updated_at) {
            return (int) $user->storage_used;
        }
        $updatedVal = self::calculateStorageUsed($id);
        $user->storage_used = $updatedVal;
        $user->storage_used_updated_at = now();
        $user->save();

        return $user->storage_used;
    }

    public static function calculateStorageUsed($id)
    {
        return (int) floor(Media::whereUserId($id)->sum('size') / 1000);
    }

    public static function recalculateUpdateStorageUsed($id)
    {
        $user = User::find($id);
        if (! $user || $user->status) {
            return;
        }
        $updatedVal = (int) floor(Media::whereUserId($id)->sum('size') / 1000);
        $user->storage_used = $updatedVal;
        $user->storage_used_updated_at = now();
        $user->save();

        return $updatedVal;
    }
}
