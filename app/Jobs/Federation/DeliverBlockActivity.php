<?php

namespace App\Jobs\Federation;

use App\Federation\ActivityBuilders\BlockActivityBuilder;
use App\Models\Profile;
use App\Models\UserFilter;
use App\Services\ActivityPubDeliveryService;
use App\Services\BlockSyncService;
use DateTimeInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class DeliverBlockActivity implements ShouldBeUniqueUntilProcessing, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const RETRY_DELAYS = [300, 3600];

    public $timeout = 60;

    public $uniqueFor = 7200;

    public function __construct(
        protected int $blockerId,
        protected int $blockedId,
        protected int $filterId,
        protected bool $undo = false,
        protected int $pass = 1
    ) {}

    public function uniqueId(): string
    {
        return $this->blockerId.':'.$this->blockedId;
    }

    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('block-delivery:'.$this->uniqueId()))
                ->releaseAfter(15)
                ->expireAfter(120),
        ];
    }

    public function retryUntil(): DateTimeInterface
    {
        return now()->addMinutes(10);
    }

    public function handle(): void
    {
        if (! BlockSyncService::disclosing()) {
            return;
        }

        $blocker = Profile::whereNull('domain')
            ->whereNull('status')
            ->find($this->blockerId);

        $blocked = Profile::whereNotNull('domain')->find($this->blockedId);

        if (! $blocker || empty($blocker->private_key) || ! $blocked || ! $blocked->inbox_url) {
            return;
        }

        $filter = UserFilter::whereUserId($this->blockerId)
            ->whereFilterableId($this->blockedId)
            ->whereFilterableType(Profile::class)
            ->whereFilterType('block')
            ->first(['id']);

        $blocking = $filter !== null;

        if ($blocking === $this->undo) {
            return;
        }

        $builder = new BlockActivityBuilder;

        $activity = $this->undo
            ? $builder->buildUndo($blocker, $blocked, $this->filterId)
            : $builder->build($blocker, $blocked, (int) $filter->id);

        try {
            $result = ActivityPubDeliveryService::pool($blocker, [$blocked->inbox_url], $activity);
        } catch (Throwable $e) {
            Log::debug('DeliverBlockActivity: delivery failed', [
                'blocker_id' => $this->blockerId,
                'blocked_id' => $this->blockedId,
                'error' => $e->getMessage(),
            ]);

            return;
        }

        if ($result['failed'] === 0 || $this->pass > count(self::RETRY_DELAYS)) {
            return;
        }

        self::dispatch(
            $this->blockerId,
            $this->blockedId,
            $this->undo ? $this->filterId : (int) $filter->id,
            $this->undo,
            $this->pass + 1
        )
            ->onQueue('high')
            ->delay(now()->addSeconds(self::RETRY_DELAYS[$this->pass - 1]));
    }
}
