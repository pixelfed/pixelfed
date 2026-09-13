<?php

namespace App\Enums;

/**
 * Lifecycle of a media row's contribution to its owner's storage quota
 * (users.storage_used).
 *
 * The amount reflected in the quota changes over the media's life:
 *
 *   Pending       Uploaded and enforced against the quota on its raw size, but
 *                 nothing has been added to storage_used yet.
 *   OriginalSize  The raw upload size has been added to storage_used. This is
 *                 charged synchronously at upload so the quota never
 *                 under-counts while optimization is still queued.
 *   OptimizedSize The async finalize job has optimized the file and corrected
 *                 the quota down by (original_size - size), so storage_used now
 *                 reflects the optimized on-disk footprint.
 *   Subtracted    The media was deleted and whatever it still reflected was
 *                 refunded to storage_used.
 *
 * Each transition is guarded by the current status so retries and overlapping
 * jobs cannot double-apply a delta.
 */
enum MediaQuotaStatus: string
{
    case Pending = 'pending';

    case OriginalSize = 'original_size';

    case OptimizedSize = 'optimized_size';

    case Subtracted = 'subtracted';

    /**
     * Whether this status means bytes are currently reflected in storage_used
     * (and therefore a delete must refund them).
     */
    public function isCharged(): bool
    {
        return $this === self::OriginalSize || $this === self::OptimizedSize;
    }
}
