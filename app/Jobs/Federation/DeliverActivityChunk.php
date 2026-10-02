<?php

namespace App\Jobs\Federation;

use App\Models\Profile;
use App\Models\Status;
use App\Services\ActivityPubDeliveryService;
use App\Services\ActivityPubFanoutService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

class DeliverActivityChunk implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public $timeout = 280;

    public $tries = 2;

    public function __construct(
        protected int $profileId,
        protected array $activity,
        protected array $inboxes,
        protected bool $synchronizeFollowers = false,
        protected ?int $statusId = null
    ) {}

    public function profileId(): int
    {
        return $this->profileId;
    }

    public function activity(): array
    {
        return $this->activity;
    }

    public function inboxes(): array
    {
        return $this->inboxes;
    }

    public function statusId(): ?int
    {
        return $this->statusId;
    }

    public function handle(): void
    {
        try {
            $this->deliver();
        } catch (Throwable $e) {
            Log::warning('DeliverActivityChunk: delivery aborted', [
                'profile_id' => $this->profileId,
                'status_id' => $this->statusId,
                'type' => $this->activity['type'] ?? null,
                'inboxes' => count($this->inboxes),
                'exception' => $e::class,
                'error' => $e->getMessage(),
            ]);
        } finally {
            $this->settle();
        }
    }

    public function failed(?Throwable $e): void
    {
        $this->settle();
    }

    protected function deliver(): void
    {
        if ($this->inboxes === []) {
            return;
        }

        if ($this->statusId !== null && ! Status::whereKey($this->statusId)->exists()) {
            return;
        }

        $profile = Profile::withTrashed()->find($this->profileId);

        if (! $profile) {
            return;
        }

        ActivityPubDeliveryService::pool(
            $profile,
            $this->inboxes,
            $this->activity,
            null,
            $this->synchronizeFollowers
        );
    }

    protected function settle(): void
    {
        if ($this->statusId !== null) {
            ActivityPubFanoutService::settle($this->statusId);
        }
    }
}
