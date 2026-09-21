<?php

namespace App\Jobs\StatusPipeline;

use App\Models\Profile;
use App\Util\ActivityPub\Helpers;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Holds a reply that arrived in an inbox while its parent could not be
 * resolved, and tries again later.
 *
 * Nothing is written for the reply until the parent resolves. A reply is
 * never stored without in_reply_to_id, because every timeline and profile
 * query reads "in_reply_to_id is null" as "top-level post". After the last
 * attempt the reply is dropped.
 *
 * Attempts are tracked on the job and re-dispatched with a delay instead of
 * using release()/tries, so an unreachable parent does not end up in
 * failed_jobs.
 *
 * Two guards keep this to one queued job per reply. The pending key spans
 * the whole chain and stops a redelivered activity from parking a second
 * one. The unique lock covers each queued attempt, whatever dispatched it.
 * It has to be ShouldBeUniqueUntilProcessing: plain ShouldBeUnique holds the
 * lock until handle() returns, so the re-dispatch in retryOrDrop() would
 * fail to acquire it and be discarded without an error.
 */
class RemoteReplyResolvePipeline implements ShouldBeUniqueUntilProcessing, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /**
     * Delay in seconds before each attempt: 3m, 15m, 1h, 6h. The first value
     * must stay above Helpers::FETCH_NEGATIVE_TTL, otherwise the first retry
     * only reads back the cached failure.
     */
    public const array BACKOFF = [180, 900, 3600, 21600];

    /**
     * Added to the delay of the attempt being scheduled to get the TTL of
     * the pending key and the unique lock. Has to cover how far the low
     * queue can run behind, plus one attempt's $timeout. If the queue lags
     * more than this, both expire before the attempt runs and a redelivered
     * activity can start a second chain.
     */
    private const int LAG_MARGIN = 3600;

    public $timeout = 300;

    public $tries = 1;

    public $maxExceptions = 1;

    /**
     * @param  array  $object  the delivered ActivityPub object (the Note)
     * @param  int  $profileId  the remote author, already verified by the inbox
     * @param  int  $attempt  index into BACKOFF of the attempt being run
     */
    public function __construct(
        public array $object,
        public int $profileId,
        public int $attempt = 0
    ) {}

    /**
     * Park a delivered reply and schedule the first attempt, once per object
     * id. (Not named queue(): the bus dispatcher treats a queue() method on a
     * job as a custom dispatch hook.)
     */
    public static function park(array $object, Profile $profile): bool
    {
        $id = Helpers::pluckval($object['id'] ?? null);

        if (! is_string($id) || $id === '') {
            return false;
        }

        if (! Cache::add(self::pendingKey($id), 1, self::ttlFor(0))) {
            return false;
        }

        self::dispatch($object, (int) $profile->id, 0)
            ->delay(now()->addSeconds(self::BACKOFF[0]))
            ->onQueue('low');

        return true;
    }

    public static function pendingKey(string $id): string
    {
        return 'pf:ap:reply-resolve:pending:'.hash('sha256', $id);
    }

    /**
     * Seconds the pending key and the unique lock are held while waiting for
     * the given attempt.
     */
    private static function ttlFor(int $attempt): int
    {
        return (self::BACKOFF[$attempt] ?? max(self::BACKOFF)) + self::LAG_MARGIN;
    }

    /**
     * One queued attempt per reply. The lock is taken on dispatch and
     * released as the attempt starts processing.
     */
    public function uniqueId(): string
    {
        return hash('sha256', (string) Helpers::pluckval($this->object['id'] ?? null));
    }

    /**
     * Safety expiry for the lock, in case the queued attempt is lost (queue
     * cleared, payload evicted) and never gets to release it.
     */
    public function uniqueFor(): int
    {
        return self::ttlFor($this->attempt);
    }

    public function handle(): void
    {
        $id = Helpers::pluckval($this->object['id'] ?? null);

        if (! is_string($id) || ! Helpers::validateUrl($id)) {
            return;
        }

        $profile = Profile::find($this->profileId);

        // Author deleted, suspended or somehow local: nothing to store.
        if (! $profile || $profile->domain === null || $profile->status !== null) {
            $this->finish($id);

            return;
        }

        // Stored in the meantime by another path (Announce, Like, search).
        if (Helpers::findExistingStatus($id)) {
            $this->finish($id);

            return;
        }

        $resolution = Helpers::resolveReplyParent($this->object, $profile);

        if ($resolution['state'] === Helpers::REPLY_PARENT_UNRESOLVED) {
            $this->retryOrDrop($id);

            return;
        }

        if (Helpers::replyParentAllowsStore($resolution)) {
            Helpers::storeStatus($id, $profile, $this->object);
        }

        $this->finish($id);
    }

    private function retryOrDrop(string $id): void
    {
        $next = $this->attempt + 1;

        if (! isset(self::BACKOFF[$next])) {
            Log::info('RemoteReplyResolvePipeline: parent never resolved, dropping reply', [
                'id' => $id,
                'inReplyTo' => $this->object['inReplyTo'] ?? null,
                'attempts' => $next,
            ]);

            $this->finish($id);

            return;
        }

        // Re-arm the pending key for the wait ahead. It is sized per attempt
        // instead of once for the whole chain, so a chain that runs late
        // cannot outlive it.
        Cache::put(self::pendingKey($id), 1, self::ttlFor($next));

        self::dispatch($this->object, $this->profileId, $next)
            ->delay(now()->addSeconds(self::BACKOFF[$next]))
            ->onQueue('low');
    }

    private function finish(string $id): void
    {
        Cache::forget(self::pendingKey($id));
    }
}
