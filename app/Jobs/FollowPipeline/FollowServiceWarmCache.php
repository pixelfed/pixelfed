<?php

namespace App\Jobs\FollowPipeline;

use App\Models\Profile;
use App\Services\AccountService;
use App\Services\FollowerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

class FollowServiceWarmCache implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const INLINE_LIMIT = 100;

    private const SYNC_TTL = 604800; // 7 days

    private const PROCESSING_TTL = 21600; // 6 hours

    public $profileId;

    public $tries = 5;

    public $timeout = 300;

    public $failOnTimeout = false;

    /**
     * Get the middleware the job should pass through.
     *
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('follow-warm-cache:'.$this->profileId))
                ->expireAfter($this->timeout + 60)
                ->dontRelease(),
        ];
    }

    /**
     * Create a new job instance.
     */
    public function __construct($profileId)
    {
        $this->profileId = $profileId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $id = (int) $this->profileId;

        if ($id <= 0) {
            return;
        }

        $followersReady = $this->isReady($id, 'followers');
        $followingReady = $this->isReady($id, 'following');

        if ($followersReady && $followingReady) {
            return;
        }

        $account = AccountService::get($id, true);

        if (! $account) {
            $this->markSynced($id, 'followers');
            $this->markSynced($id, 'following');

            return;
        }

        /*
         * Keep these as separate queries.
         *
         * This lets the database use the indexes on profile_id and
         * following_id independently instead of doing the previous OR scan.
         */
        $followingCount = DB::table('followers')
            ->where('profile_id', $id)
            ->count();

        $followersCount = DB::table('followers')
            ->where('following_id', $id)
            ->count();

        /*
         * Refresh the denormalized counters without hydrating the full model.
         */
        Profile::query()
            ->whereKey($id)
            ->update([
                'following_count' => $followingCount,
                'followers_count' => $followersCount,
            ]);

        /*
         * Only warm directions that are not already synced or currently
         * being processed.
         */
        if (! $followingReady) {
            $this->warmDirection(
                profileId: $id,
                type: 'following',
                count: $followingCount
            );
        }

        if (! $followersReady) {
            $this->warmDirection(
                profileId: $id,
                type: 'followers',
                count: $followersCount
            );
        }

        AccountService::del($id);
    }

    private function warmDirection(
        int $profileId,
        string $type,
        int $count
    ): void {
        if ($count === 0) {
            $this->markSynced($profileId, $type);

            return;
        }

        /*
         * Tiny accounts can be handled immediately without the overhead
         * of another queue job.
         */
        if ($count <= self::INLINE_LIMIT) {
            if ($type === 'followers') {
                $ids = DB::table('followers')
                    ->where('following_id', $profileId)
                    ->orderBy('id')
                    ->limit(self::INLINE_LIMIT)
                    ->pluck('profile_id');

                foreach ($ids as $followerId) {
                    FollowerService::add(
                        (int) $followerId,
                        $profileId
                    );
                }
            } else {
                $ids = DB::table('followers')
                    ->where('profile_id', $profileId)
                    ->orderBy('id')
                    ->limit(self::INLINE_LIMIT)
                    ->pluck('following_id');

                foreach ($ids as $followingId) {
                    FollowerService::add(
                        $profileId,
                        (int) $followingId
                    );
                }
            }

            $this->markSynced($profileId, $type);

            return;
        }

        /*
         * Large graphs are paginated directly from the database.
         *
         * Cache::add() is atomic with Redis and prevents repeated
         * FollowServiceWarmCache jobs from starting duplicate pipelines.
         */
        $processingKey =
            FollowServiceWarmCacheLargeIngestPipeline::processingKey(
                $profileId,
                $type
            );

        if (! Cache::add(
            $processingKey,
            1,
            self::PROCESSING_TTL
        )) {
            return;
        }

        try {
            FollowServiceWarmCacheLargeIngestPipeline::dispatch(
                $profileId,
                $type
            )->onQueue('follow');
        } catch (Throwable $e) {
            Cache::forget($processingKey);

            throw $e;
        }
    }

    private function isReady(int $profileId, string $type): bool
    {
        if (Cache::has($this->syncKey($profileId, $type))) {
            return true;
        }

        return Cache::has(
            FollowServiceWarmCacheLargeIngestPipeline::processingKey(
                $profileId,
                $type
            )
        );
    }

    private function markSynced(int $profileId, string $type): void
    {
        Cache::put(
            $this->syncKey($profileId, $type),
            1,
            self::SYNC_TTL
        );
    }

    private function syncKey(int $profileId, string $type): string
    {
        return $type === 'followers'
            ? FollowerService::FOLLOWERS_SYNC_KEY.$profileId
            : FollowerService::FOLLOWING_SYNC_KEY.$profileId;
    }
}
