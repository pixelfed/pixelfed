<?php

namespace App\Jobs\IpDataPipeline;

use App\Models\IpDataset;
use App\Services\IpDataImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Str;
use Throwable;

class ImportIpDataset implements ShouldQueue
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
        $dataset = IpDataset::find($this->datasetId);

        if (! $dataset) {
            return;
        }

        if (! IpDataImportService::importSlice($dataset)) {
            self::dispatch($this->datasetId);
        }
    }

    public function failed(?Throwable $exception): void
    {
        $dataset = IpDataset::find($this->datasetId);

        if ($dataset && $dataset->isRunning()) {
            IpDataImportService::fail($dataset, 'The import stopped: '.Str::limit((string) $exception?->getMessage(), 400));
        }
    }
}
