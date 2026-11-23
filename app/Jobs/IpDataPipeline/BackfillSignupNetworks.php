<?php

namespace App\Jobs\IpDataPipeline;

use App\Services\IpDataImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class BackfillSignupNetworks implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public $timeout = 280;

    public $tries = 3;

    public $backoff = 15;

    public function __construct(public int $afterId = 0, public bool $all = false)
    {
        $this->onQueue('low');
    }

    public function handle(): void
    {
        $started = microtime(true);
        $cursor = $this->afterId;

        while (microtime(true) - $started < 200) {
            $next = IpDataImportService::backfillSlice($cursor, 1000, $this->all);

            if ($next === null) {
                return;
            }

            $cursor = $next;
        }

        self::dispatch($cursor, $this->all);
    }
}
