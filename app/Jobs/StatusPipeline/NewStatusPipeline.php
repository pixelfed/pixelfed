<?php

namespace App\Jobs\StatusPipeline;

use App\Models\Media;
use App\Models\Status;
use App\Support\TransientException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class NewStatusPipeline implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Seconds between checks while attached media is still being processed.
     */
    public const MEDIA_WAIT_SECONDS = 10;

    /**
     * How many checks to make before giving up (30 x 10s = 5 minutes).
     */
    public const MEDIA_WAIT_MAX = 30;

    /**
     * Seconds to hold the publish lock. Long enough to cover the waiting
     * job and the media pipeline both dispatching for the same status.
     */
    private const int LOCK_TTL = 900;

    protected $status;

    /**
     * How many times this status has already been re-queued waiting on media.
     */
    protected int $mediaWait;

    /**
     * Delete the job if its models no longer exist.
     *
     * @var bool
     */
    public $deleteWhenMissingModels = true;

    public $timeout = 30;

    public $tries = 3;

    public $maxExceptions = 1;

    /**
     * The number of seconds to wait before retrying.
     *
     * @var array<int, int>
     */
    public $backoff = [5, 10];

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Status $status, int $mediaWait = 0)
    {
        $this->status = $status;
        $this->mediaWait = $mediaWait;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $status = $this->status;

        // Verify status exists
        if (! $status) {
            Log::info('NewStatusPipeline: Status no longer exists, skipping job');

            return;
        }

        // A transient DB/cache/queue failure here (connection drop, failover,
        // deadlock) should retry via tries=3/backoff, not fail the job on the
        // first exception. maxExceptions=1 would fail-fast on a rethrow, so
        // release the job instead — that reschedules without incrementing the
        // worker's exception counter, leaving the retry budget for real bugs,
        // which still propagate and fail fast. publish() frees its own lock
        // before rethrowing, so the retry can re-acquire and federate.
        try {
            $this->publish($status);
        } catch (Throwable $e) {
            if (TransientException::matches($e)) {
                Log::warning("NewStatusPipeline: transient failure for status {$status->id}, retrying: ".$e->getMessage());
                $this->release($this->backoff[$this->attempts() - 1] ?? 5);

                return;
            }

            throw $e;
        }
    }

    /**
     * Publish the status. No-ops when there is nothing to publish (deleted
     * status, still-processing media, or the publish lock is already held).
     */
    protected function publish(Status $status): void
    {
        if (! Status::where('id', $status->id)->exists()) {
            // The status has already been deleted by the time the job is running
            // Don't publish the status, and just no-op
            return;
        }

        if (config_cache('pixelfed.cloud_storage') && ! config('pixelfed.media_fast_process')) {
            $still_processing = Media::whereStatusId($status->id)
                ->whereNull('cdn_url')
                ->exists();

            if ($still_processing) {
                // The media items in the status are still being processed.
                // We can't publish the status to ActivityPub because the final remote URL is not
                // yet known.
                //
                // MediaStorageService::cloudStore() re-dispatches this job when the last attachment
                // lands on cloud storage, but that only helps if the media pipeline actually gets
                // there. Poll as a fallback so a post is never silently left unpublished.
                if ($this->mediaWait >= self::MEDIA_WAIT_MAX) {
                    Log::warning(
                        "NewStatusPipeline: Media for status {$status->id} never finished processing, giving up after "
                            .(self::MEDIA_WAIT_MAX * self::MEDIA_WAIT_SECONDS).'s'
                    );

                    return;
                }

                self::dispatch($status, $this->mediaWait + 1)
                    ->onQueue($this->queue)
                    ->delay(now()->addSeconds(self::MEDIA_WAIT_SECONDS));

                return;
            }
        }

        /*
         * Both the fallback above and MediaStorageService::cloudStore() can
         * dispatch this job for the same status once its media is ready.
         * Only the first one through should lex and federate it.
         */
        $lock = 'pf:status:new-pipeline:'.$status->id;

        if (! Cache::add($lock, 1, self::LOCK_TTL)) {
            return;
        }

        try {
            StatusEntityLexer::dispatch($status);
        } catch (Throwable $e) {
            // Free the lock so the retry (handled by handle()) can re-acquire
            // and federate, then rethrow for transient/fatal classification.
            Cache::forget($lock);

            throw $e;
        }
    }
}
