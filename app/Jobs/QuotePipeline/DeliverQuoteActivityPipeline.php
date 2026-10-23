<?php

namespace App\Jobs\QuotePipeline;

use App\Exceptions\InvalidDeliveryDestinationException;
use App\Models\Profile;
use App\Services\ActivityPubDeliveryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * Delivers one FEP-044f activity (the Accept or Reject answering a
 * QuoteRequest, or the Delete revoking a stamp) and keeps trying when the
 * remote is temporarily unable to take it.
 *
 * The quoting server sends its QuoteRequest exactly once, so an Accept that
 * is lost to a timeout or a 503 would leave the quote pending forever.
 */
class DeliverQuoteActivityPipeline implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Seconds to wait before each retry.
     *
     * @var array<int, int>
     */
    public const RETRY_DELAYS = [30, 120, 600, 3600];

    /**
     * Statuses below 500 that are still worth another attempt.
     */
    private const array RETRYABLE_STATUSES = [408, 425, 429];

    public $timeout = 60;

    // One attempt plus the retries above
    public $tries = 5;

    public $maxExceptions = 1;

    /**
     * @param  array<string, mixed>  $activity
     */
    public function __construct(
        protected int $fromProfileId,
        protected int $toProfileId,
        protected array $activity
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function activity(): array
    {
        return $this->activity;
    }

    public function handle(): void
    {
        $from = Profile::find($this->fromProfileId);
        $to = Profile::find($this->toProfileId);

        if (! $from || ! $to || $from->domain !== null || $from->status !== null) {
            return;
        }

        $inbox = $to->sharedInbox ?? $to->inbox_url;

        if (! $inbox) {
            Log::info('DeliverQuoteActivityPipeline: remote actor has no inbox', [
                'profile_id' => $from->id,
                'actor_id' => $to->id,
            ]);

            return;
        }

        if (! app()->environment('production')) {
            return;
        }

        try {
            $response = ActivityPubDeliveryService::queue()
                ->from($from)
                ->to($inbox)
                ->payload($this->activity)
                ->deliver();
        } catch (InvalidDeliveryDestinationException|InvalidArgumentException $e) {
            // Banned host, bad inbox URL, sender without keys: retrying cannot help
            return;
        }

        // Null means nothing reached the remote (connection failure, or the
        // host is currently marked unavailable), which is worth retrying.
        if ($response && ! self::shouldRetry($response->status())) {
            return;
        }

        $attempt = $this->attempts();

        if ($attempt > count(self::RETRY_DELAYS)) {
            Log::warning('DeliverQuoteActivityPipeline: giving up', [
                'profile_id' => $from->id,
                'actor_id' => $to->id,
                'type' => $this->activity['type'] ?? null,
                'id' => $this->activity['id'] ?? null,
                'status' => $response?->status(),
            ]);

            return;
        }

        $this->release(self::RETRY_DELAYS[$attempt - 1]);
    }

    private static function shouldRetry(int $status): bool
    {
        return $status >= 500 || in_array($status, self::RETRYABLE_STATUSES, true);
    }
}
