<?php

namespace App\Jobs\FollowPipeline;

use App\Services\FollowerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class FollowServiceWarmCacheLargeIngestPipeline implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const CHUNK_SIZE = 1000;

    private const SYNC_TTL = 604800; // 7 days

    private const PROCESSING_TTL = 21600; // 6 hours

    public $profileId;

    public $followType;

    public $cursor;

    public $tries = 5;

    public $timeout = 300;

    public $failOnTimeout = false;

    /**
     * Create a new job instance.
     */
    public function __construct(
        $profileId,
        $followType = 'following',
        $cursor = 0
    ) {
        if (! in_array($followType, ['followers', 'following'], true)) {
            throw new InvalidArgumentException(
                'Invalid follow type: '.$followType
            );
        }

        $this->profileId = $profileId;
        $this->followType = $followType;
        $this->cursor = $cursor;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $profileId = (int) $this->profileId;
        $cursor = (int) $this->cursor;
        $type = $this->followType;

        if ($profileId <= 0) {
            return;
        }

        /*
         * Refresh the processing marker while the chain is active.
         */
        Cache::put(
            self::processingKey($profileId, $type),
            1,
            self::PROCESSING_TTL
        );

        if ($type === 'followers') {
            $rows = DB::table('followers')
                ->select([
                    'id',
                    'profile_id',
                ])
                ->where('following_id', $profileId)
                ->where('id', '>', $cursor)
                ->orderBy('id')
                ->limit(self::CHUNK_SIZE)
                ->get();
        } else {
            $rows = DB::table('followers')
                ->select([
                    'id',
                    'following_id',
                ])
                ->where('profile_id', $profileId)
                ->where('id', '>', $cursor)
                ->orderBy('id')
                ->limit(self::CHUNK_SIZE)
                ->get();
        }

        /*
         * Nothing left to process.
         */
        if ($rows->isEmpty()) {
            $this->complete($profileId, $type);

            return;
        }

        foreach ($rows as $row) {
            if ($type === 'followers') {
                $followerId = (int) $row->profile_id;

                if ($followerId > 0) {
                    FollowerService::add(
                        $followerId,
                        $profileId,
                        false
                    );
                }
            } else {
                $followingId = (int) $row->following_id;

                if ($followingId > 0) {
                    FollowerService::add(
                        $profileId,
                        $followingId,
                        false
                    );
                }
            }
        }

        $lastRow = $rows->last();
        $nextCursor = (int) $lastRow->id;

        /*
         * If we received fewer than CHUNK_SIZE rows, we know this was
         * the final page and can finish without dispatching another job.
         */
        if ($rows->count() < self::CHUNK_SIZE) {
            $this->complete($profileId, $type);

            return;
        }

        /*
         * One bounded chunk per queue job.
         *
         * Memory usage remains effectively constant regardless of whether
         * the account has 1,000 or 10,000,000 followers.
         */
        self::dispatch(
            $profileId,
            $type,
            $nextCursor
        )->onQueue('follow');
    }

    public static function processingKey(
        int $profileId,
        string $type
    ): string {
        return 'pf:follow-warm-cache:processing:'.$profileId.':'.$type;
    }

    private function complete(int $profileId, string $type): void
    {
        $syncKey = $type === 'followers'
            ? FollowerService::FOLLOWERS_SYNC_KEY.$profileId
            : FollowerService::FOLLOWING_SYNC_KEY.$profileId;

        Cache::put(
            $syncKey,
            1,
            self::SYNC_TTL
        );

        Cache::forget(
            self::processingKey($profileId, $type)
        );
    }
}
