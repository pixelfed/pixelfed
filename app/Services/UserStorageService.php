<?php

namespace App\Services;

use App\Models\Media;
use App\Models\User;

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

    /**
     * Increment a user's cached storage_used by the size (in bytes) of newly
     * stored media, without re-summing the whole media table.
     *
     * This is the counterpart to decrementStorageUsed and is used on the upload
     * path. If storage_used has never been calculated (storage_used_updated_at
     * is null) it seeds the counter from source first so the increment is added
     * to an accurate base rather than zero. Any drift is corrected by the
     * periodic recalculateUpdateStorageUsed reconciler.
     *
     * @param  int  $id  User id
     * @param  int  $sizeInBytes  Size of the added media in bytes
     * @return int|null New storage_used value in KB, or null when skipped
     */
    public static function increaseStorageUsed($id, $sizeInBytes)
    {
        $user = User::find($id);
        if (! $user || $user->status) {
            return null;
        }

        // Seed from source if never calculated, so we add to an accurate base.
        $base = $user->storage_used_updated_at
            ? (int) $user->storage_used
            : self::calculateStorageUsed($id);

        $sizeInKbs = (int) floor(((int) $sizeInBytes) / 1000);
        $updatedVal = max(0, $base + $sizeInKbs);

        $user->storage_used = $updatedVal;
        $user->storage_used_updated_at = now();
        $user->save();

        return $updatedVal;
    }

    /**
     * Decrement a user's cached storage_used by the size (in bytes) of removed
     * media, without re-summing the whole media table.
     *
     * This is the fast path used when media is deleted. Any drift it introduces
     * (e.g. double-processed jobs, deletions that bypass this path) is corrected
     * by the periodic recalculateUpdateStorageUsed reconciler. The value is
     * clamped at zero so it can never go negative.
     *
     * Note: only acts on an already-populated counter. If storage_used has
     * never been calculated (storage_used_updated_at is null), it leaves the
     * value untouched so the next get()/recalculate computes it from source.
     *
     * @param  int  $id  User id
     * @param  int  $sizeInBytes  Size of the removed media in bytes
     * @return int|null New storage_used value in KB, or null when skipped
     */
    public static function decrementStorageUsed($id, $sizeInBytes)
    {
        $user = User::find($id);
        if (! $user || $user->status) {
            return null;
        }

        // Nothing cached yet: let the next full calculation establish the value.
        if (! $user->storage_used_updated_at) {
            return null;
        }

        $sizeInKbs = (int) floor(((int) $sizeInBytes) / 1000);
        $updatedVal = max(0, (int) $user->storage_used - $sizeInKbs);

        $user->storage_used = $updatedVal;
        $user->storage_used_updated_at = now();
        $user->save();

        return $updatedVal;
    }
}
