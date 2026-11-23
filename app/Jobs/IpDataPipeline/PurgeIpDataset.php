<?php

namespace App\Jobs\IpDataPipeline;

use App\Models\IpDataset;
use App\Services\IpDataImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class PurgeIpDataset implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public $timeout = 280;

    public $tries = 3;

    public $backoff = 15;

    public function __construct(public int $datasetId)
    {
        $this->onQueue('low');
    }

    public function handle(): void
    {
        $started = microtime(true);

        while (microtime(true) - $started < 200) {
            $dataset = IpDataset::find($this->datasetId);

            if (! $dataset) {
                return;
            }

            if (IpDataImportService::purgeSlice($dataset) < IpDataImportService::PURGE_BATCH) {
                return;
            }
        }

        self::dispatch($this->datasetId);
    }
}
