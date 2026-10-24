<?php

namespace App\Services;

use App\Jobs\Federation\DeliverActivityChunk;
use App\Models\Profile;
use Illuminate\Support\Facades\Cache;

class ActivityPubFanoutService
{
    public const int PENDING_TTL = 1800;

    private const string PENDING_KEY = 'pf:ap:fanout:create:pending:';

    public static function dispatch(
        Profile $profile,
        array $activity,
        array $audience,
        bool $synchronizeFollowers = false,
        ?int $statusId = null
    ): int {
        $inboxes = array_values(array_unique(array_filter(
            $audience,
            fn ($inbox) => is_string($inbox) && trim($inbox) !== ''
        )));

        if ($inboxes === []) {
            return 0;
        }

        $chunks = array_chunk($inboxes, self::chunkSize());

        if ($statusId !== null) {
            Cache::put(self::pendingKey($statusId), count($chunks), self::PENDING_TTL);
        }

        foreach ($chunks as $chunk) {
            DeliverActivityChunk::dispatch(
                (int) $profile->id,
                $activity,
                $chunk,
                $synchronizeFollowers,
                $statusId
            )->onQueue(self::queue());
        }

        return count($chunks);
    }

    public static function pending(int $statusId): int
    {
        return max(0, (int) Cache::get(self::pendingKey($statusId), 0));
    }

    public static function settle(int $statusId): void
    {
        $key = self::pendingKey($statusId);

        if (! Cache::has($key)) {
            return;
        }

        if ((int) Cache::decrement($key) <= 0) {
            Cache::forget($key);
        }
    }

    public static function queue(): string
    {
        return (string) config('federation.activitypub.delivery.queue', 'deliver');
    }

    public static function chunkSize(): int
    {
        return max(1, (int) config('federation.activitypub.delivery.chunk_size', 50));
    }

    private static function pendingKey(int $statusId): string
    {
        return self::PENDING_KEY.$statusId;
    }
}
