<?php

namespace App\Jobs\Federation;

use App\Models\Profile;
use App\Services\AccountDeleteFederationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Entry point for federating a local account deletion. Dispatched by
 * DeleteAccountPipeline once the account is gone locally.
 *
 * Does no delivery itself: it resolves the audience and queues one
 * DeliverAccountDeleteActivity per chunk of inboxes, so no single job has
 * to outlive the worker timeout.
 */
class FanoutAccountDeleteActivity implements ShouldBeUniqueUntilProcessing, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120;

    public $tries = 3;

    public $uniqueFor = 3600;

    public $backoff = 60;

    /**
     * Takes the id and not the model: the profile is soft deleted by now,
     * and SerializesModels would fail to find it again.
     */
    public function __construct(protected int $profileId) {}

    public function uniqueId(): string
    {
        return 'ap:account-delete:fanout:'.$this->profileId;
    }

    public function profileId(): int
    {
        return $this->profileId;
    }

    public function handle(AccountDeleteFederationService $service): void
    {
        if ((bool) config_cache('federation.activitypub.enabled') !== true) {
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
            $queued = $service->fanout($profile);
        } catch (RuntimeException $e) {
            // Remote profile, account not actually deleted, wiped key:
            // retrying cannot help
            Log::warning('FanoutAccountDeleteActivity: cannot federate deletion', [
                'profile_id' => $this->profileId,
                'error' => $e->getMessage(),
            ]);

            return;
        }

        Log::info('FanoutAccountDeleteActivity: queued', [
            'profile_id' => $this->profileId,
            'inboxes' => $queued,
        ]);
    }
}
