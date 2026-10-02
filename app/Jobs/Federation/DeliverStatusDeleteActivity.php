<?php

namespace App\Jobs\Federation;

use App\Models\Profile;
use App\Services\ActivityPubFanoutService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class DeliverStatusDeleteActivity implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public const int WAIT_SECONDS = 15;

    public $timeout = 60;

    public $tries = 40;

    public function __construct(
        protected int $profileId,
        protected int $statusId,
        protected array $activity,
        protected array $inboxes
    ) {}

    public function profileId(): int
    {
        return $this->profileId;
    }

    public function statusId(): int
    {
        return $this->statusId;
    }

    public function activity(): array
    {
        return $this->activity;
    }

    public function inboxes(): array
    {
        return $this->inboxes;
    }

    public function handle(): void
    {
        if ($this->inboxes === []) {
            return;
        }

        if (
            ActivityPubFanoutService::pending($this->statusId) > 0 &&
            $this->attempts() < $this->tries
        ) {
            $this->release(self::WAIT_SECONDS);

            return;
        }

        $profile = Profile::withTrashed()->find($this->profileId);

        if (! $profile) {
            return;
        }

        ActivityPubFanoutService::dispatch($profile, $this->activity, $this->inboxes);
    }
}
