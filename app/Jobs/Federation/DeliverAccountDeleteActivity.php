<?php

namespace App\Jobs\Federation;

use App\Models\Profile;
use App\Services\AccountDeleteFederationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Delivers one account deletion to one chunk of inboxes.
 *
 * Inboxes that time out or answer with a temporary status are not retried
 * in place. They are handed to a fresh, delayed job carrying only those
 * inboxes, so a slow tail never resends to hosts that already accepted and
 * no job sits in a sleep holding a worker.
 */
class DeliverAccountDeleteActivity implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Seconds to wait before each further pass.
     *
     * @var array<int, int>
     */
    public const RETRY_DELAYS = [300, 3600, 21600];

    // Stays under the 300 second Horizon worker timeout
    public $timeout = 240;

    // A pass killed halfway is run again. Delete is idempotent, so the
    // inboxes that already got it lose nothing by getting it twice.
    public $tries = 2;

    /**
     * @param  array<int, string>  $inboxes
     * @param  int  $pass  1 for the first delivery, +1 for every retry job
     */
    public function __construct(
        protected int $profileId,
        protected array $inboxes,
        protected int $pass = 1
    ) {}

    public function profileId(): int
    {
        return $this->profileId;
    }

    /**
     * @return array<int, string>
     */
    public function inboxes(): array
    {
        return $this->inboxes;
    }

    public function pass(): int
    {
        return $this->pass;
    }

    public function handle(AccountDeleteFederationService $service): void
    {
        if ($this->inboxes === []) {
            return;
        }

        if (! app()->environment('production')) {
            return;
        }

        $profile = Profile::withTrashed()->find($this->profileId);

        if (! $profile) {
            return;
        }

        try {
            $prepared = $service->prepare($profile);
        } catch (RuntimeException $e) {
            // Account restored or key wiped since the fanout: retrying cannot help
            Log::warning('DeliverAccountDeleteActivity: cannot federate deletion', [
                'profile_id' => $this->profileId,
                'error' => $e->getMessage(),
            ]);

            return;
        }

        $result = $service->deliver($profile, $prepared, $this->inboxes);

        $pending = array_keys($result['retryable']);

        if ($pending === []) {
            return;
        }

        if ($this->pass > count(self::RETRY_DELAYS)) {
            Log::info('DeliverAccountDeleteActivity: giving up', [
                'profile_id' => $this->profileId,
                'inboxes' => count($pending),
            ]);

            return;
        }

        self::dispatch($this->profileId, $pending, $this->pass + 1)
            ->onQueue('delete')
            ->delay(now()->addSeconds(self::RETRY_DELAYS[$this->pass - 1]));
    }
}
