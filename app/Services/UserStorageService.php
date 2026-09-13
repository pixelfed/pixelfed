<?php

namespace App\Services;

use App\Enums\MediaQuotaStatus;
use App\Models\Media;
use App\Models\User;
use Carbon\Carbon;

class UserStorageService
{
    /**
     * How long (in hours) a cached storage_used value is trusted for
     * incremental add/subtract before the hot path recalculates it from source.
     *
     * Mirrors the scheduled `user:storage:recalculate --stale=168` reconciler:
     * an active user who uploads or deletes will self-heal a stale counter
     * without waiting for the nightly job.
     */
    const STALE_AFTER_HOURS = 168;

    /**
     * Whether a user's cached storage_used is too old (or missing) to be
     * trusted for an incremental delta and should be recalculated from source.
     */
    protected static function isStale(User $user): bool
    {
        $updatedAt = $user->storage_used_updated_at;
        if (! $updatedAt) {
            return true;
        }

        // Be defensive: the value is normally cast to Carbon on the User model,
        // but tolerate a raw string if a model is hydrated without casts.
        if (! $updatedAt instanceof Carbon) {
            $updatedAt = Carbon::parse($updatedAt);
        }

        return $updatedAt->lt(now()->subHours(self::STALE_AFTER_HOURS));
    }

    public static function get($id)
    {
        $user = User::find($id);
        if (! $user || $user->status) {
            return -1;
        }

        // Self-heal: when the storage cached counter is missing or stale, recompute from source before returning.
        if (self::isStale($user)) {
            $updatedVal = self::calculateStorageUsed($id);
            $user->storage_used = $updatedVal;
            $user->storage_used_updated_at = now();
            $user->save();

            return (int) $user->storage_used;
        }

        return (int) $user->storage_used;
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
     * path, AFTER the new media row has been saved.
     *
     * Self-healing: if the cached counter is missing or stale (see
     * STALE_AFTER_HOURS) it recalculates from source instead of trusting the
     * incremental value. Because callers save the media row before calling
     * this, a from-source recalc already accounts for the new media, so the
     * delta is NOT re-applied on the recalc path (that would double count).
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

        // Stale or uncalculated: recompute from source. The just-saved media is
        // already included, so return the source value without adding the delta.
        if (self::isStale($user)) {
            return self::recalculateUpdateStorageUsed($id);
        }

        $sizeInKbs = (int) ceil(((int) $sizeInBytes) / 1000);
        $updatedVal = max(0, (int) $user->storage_used + $sizeInKbs);

        $user->storage_used = $updatedVal;
        $user->storage_used_updated_at = now();
        $user->save();

        return $updatedVal;
    }

    /**
     * Charge the raw upload size of a freshly uploaded media to its owner's
     * storage_used, moving it Pending -> OriginalSize.
     *
     * Called synchronously at upload (after the media row is saved) so the
     * quota reflects the file immediately and never under-counts while
     * optimization is still queued. Guarded by quota_status so it applies once.
     *
     * @return bool True when the charge was applied, false when skipped.
     */
    public static function chargeOriginal(Media $media): bool
    {
        // A freshly saved row may not have the DB default hydrated on the model
        // instance yet, so treat a null status as Pending.
        $status = $media->quota_status ?? MediaQuotaStatus::Pending;
        if (! $media->user_id || $status !== MediaQuotaStatus::Pending) {
            return false;
        }

        $bytes = (int) ($media->original_size ?? $media->size);
        if ($bytes <= 0) {
            return false;
        }

        if (self::increaseStorageUsed($media->user_id, $bytes) === null) {
            return false;
        }

        $media->quota_status = MediaQuotaStatus::OriginalSize;
        $media->saveQuietly();

        return true;
    }

    /**
     * Correct a charged media down to its optimized on-disk size, moving it
     * OriginalSize -> OptimizedSize by subtracting (original_size - size).
     *
     * Called from the async finalize job once media.size holds the optimized
     * value. The delta is signed: if optimization somehow grew the file the
     * quota is adjusted up instead. Guarded by quota_status so it applies once.
     *
     * @return bool True when the correction was applied, false when skipped.
     */
    public static function chargeOptimized(Media $media): bool
    {
        if (! $media->user_id || $media->quota_status !== MediaQuotaStatus::OriginalSize) {
            return false;
        }

        $original = (int) ($media->original_size ?? $media->size);
        $optimized = (int) $media->size;
        $delta = $original - $optimized; // bytes freed by optimization (usually > 0)

        if ($delta > 0) {
            self::decrementStorageUsed($media->user_id, $delta);
        } elseif ($delta < 0) {
            self::increaseStorageUsed($media->user_id, -$delta);
        }

        $media->quota_status = MediaQuotaStatus::OptimizedSize;
        $media->saveQuietly();

        return true;
    }

    /**
     * Refund a deleted media's currently-reflected size to its owner's
     * storage_used, moving it to Subtracted.
     *
     * Refunds the raw size when still OriginalSize, the optimized size when
     * OptimizedSize, and nothing when Pending (never charged) or already
     * Subtracted. Guarded by quota_status so it refunds once.
     *
     * @return bool True when a refund was applied, false when skipped.
     */
    public static function subtractMedia(Media $media): bool
    {
        if (! $media->user_id || ! $media->quota_status->isCharged()) {
            return false;
        }

        $bytes = $media->quota_status === MediaQuotaStatus::OriginalSize
            ? (int) ($media->original_size ?? $media->size)
            : (int) $media->size;

        if ($bytes > 0) {
            self::decrementStorageUsed($media->user_id, $bytes);
        }

        // The media row is deleted right after this in the delete pipeline, so
        // only stamp the status when the row still exists (e.g. tests).
        if ($media->exists) {
            $media->quota_status = MediaQuotaStatus::Subtracted;
            $media->saveQuietly();
        }

        return true;
    }

    /**
     * Decrement a user's cached storage_used by the size (in bytes) of removed
     * media, without re-summing the whole media table.
     *
     * This is the fast path used when media is deleted, AFTER the media row has
     * been removed. The value is clamped at zero so it can never go negative.
     *
     * Self-healing: if the cached counter is missing or stale (see
     * STALE_AFTER_HOURS) it recalculates from source instead of trusting the
     * incremental value. Because callers delete the media row before calling
     * this, a from-source recalc already excludes the removed media, so the
     * delta is NOT re-applied on the recalc path (that would over-subtract).
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

        // Stale or uncalculated: recompute from source. The removed media is
        // already excluded, so return the source value without subtracting.
        if (self::isStale($user)) {
            return self::recalculateUpdateStorageUsed($id);
        }

        $sizeInKbs = (int) ceil(((int) $sizeInBytes) / 1000);
        $updatedVal = max(0, (int) $user->storage_used - $sizeInKbs);

        $user->storage_used = $updatedVal;
        $user->storage_used_updated_at = now();
        $user->save();

        return $updatedVal;
    }
}
