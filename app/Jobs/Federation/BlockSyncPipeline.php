<?php

namespace App\Jobs\Federation;

use App\Services\BlockSyncService;
use App\Services\DeliveryHostService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;
use Throwable;

class BlockSyncPipeline implements ShouldBeUniqueUntilProcessing, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public $timeout = 120;

    public $tries = 1;

    public const REMOTE_BUDGET = 90;

    public function __construct(
        protected string $authority,
        protected string $url,
        protected bool $periodic = false
    ) {}

    public function uniqueId(): string
    {
        return ($this->periodic ? 'pull:' : 'push:').hash('sha256', $this->authority);
    }

    public function uniqueFor(): int
    {
        return $this->periodic
            ? 5400
            : max(60, (int) config('federation.activitypub.block_sync.cooldown', 900));
    }

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('block-sync:'.hash('sha256', $this->authority)))
                ->shared()
                ->dontRelease()
                ->expireAfter($this->timeout + 30),
        ];
    }

    public function handle(): void
    {
        $domain = DeliveryHostService::domain($this->authority);

        if ($domain && DeliveryHostService::isUnavailable($domain)) {
            return;
        }

        $signed = BlockSyncService::pullSignedDigest($this->authority);

        try {
            $result = BlockSyncService::synchronize(
                $this->authority,
                $signed['url'] ?? $this->url,
                $signed['digest'] ?? null,
                microtime(true) + self::REMOTE_BUDGET
            );
        } catch (Throwable $e) {
            Log::warning('BlockSync: synchronization failed', [
                'authority' => $this->authority,
                'exception' => $e::class,
                'error' => $e->getMessage(),
            ]);

            BlockSyncService::backOff($this->authority);

            return;
        }

        if ($result['status'] === 'fetch_failed') {
            BlockSyncService::backOff($this->authority);
        }
    }
}
