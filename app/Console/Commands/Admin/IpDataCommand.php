<?php

namespace App\Console\Commands\Admin;

use App\Jobs\IpDataPipeline\BackfillSignupNetworks;
use App\Models\IpDataset;
use App\Services\IpDataImportService;
use App\Services\IpDataService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class IpDataCommand extends Command
{
    protected $signature = 'app:ip-data
        {action : status, import, lookup, backfill, cancel or purge}
        {args?* : import: <path to .csv or .csv.zip>, lookup: <ip>, cancel: <id>}
        {--force : Activate the import even when it has far fewer networks than the active data (import)}
        {--queue : Run the import on the queue instead of in this process (import, backfill)}
        {--all : Recompute country and ASN for every account, not only accounts missing them (backfill)}';

    protected $description = 'Import and manage IP to Country and IP to ASN data used by the admin dashboard and registration blocks';

    public function handle(): int
    {
        return match ($this->argument('action')) {
            'status' => $this->status(),
            'import' => $this->import(),
            'lookup' => $this->lookup(),
            'backfill' => $this->backfill(),
            'cancel' => $this->cancel(),
            'purge' => $this->purge(),
            default => $this->unknown(),
        };
    }

    protected function unknown(): int
    {
        $this->error('Unknown action. Use status, import, lookup, backfill, cancel or purge.');

        return self::FAILURE;
    }

    protected function status(): int
    {
        $rows = IpDataset::query()
            ->orderByDesc('id')
            ->limit(20)
            ->get()
            ->map(fn (IpDataset $d) => [
                $d->id,
                $d->label(),
                $d->status,
                number_format($d->rows_imported),
                $d->isRunning() ? $d->progress().'%' : '',
                (string) $d->source_name,
                $d->created_at?->toDateTimeString(),
                (string) $d->error,
            ]);

        if ($rows->isEmpty()) {
            $this->info('No IP data has been imported yet.');

            return self::SUCCESS;
        }

        $this->table(['ID', 'Dataset', 'Status', 'Networks', 'Progress', 'File', 'Created', 'Error'], $rows);

        return self::SUCCESS;
    }

    protected function import(): int
    {
        $path = $this->argument('args')[0] ?? null;

        if (! $path) {
            $this->error('Usage: app:ip-data import <path to .csv or .csv.zip> [--force] [--queue]');

            return self::FAILURE;
        }

        try {
            $dataset = IpDataImportService::stageLocal($path, (bool) $this->option('force'), null, (bool) $this->option('queue'));
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        if ($dataset->status === IpDataset::STATUS_FAILED) {
            $this->error((string) $dataset->error);

            return self::FAILURE;
        }

        if ($this->option('queue')) {
            $this->info("Queued {$dataset->label()} import #{$dataset->id}. Follow it with php artisan app:ip-data status");

            return self::SUCCESS;
        }

        $this->info("Importing {$dataset->label()} data from {$dataset->source_name}");
        $bar = $this->output->createProgressBar(100);
        $bar->start();

        while (! IpDataImportService::importSlice($dataset, IpDataImportService::SLICE_ROWS, 3600)) {
            $dataset->refresh();
            $bar->setProgress($dataset->progress());
        }

        $bar->finish();
        $this->newLine(2);
        $dataset->refresh();

        if ($dataset->status !== IpDataset::STATUS_ACTIVE) {
            $this->error('Import '.$dataset->status.': '.$dataset->error);

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Imported %s networks (%s IPv4, %s IPv6) in %ss. Lookups now use this data.',
            number_format($dataset->rows_imported),
            number_format($dataset->networks_v4),
            number_format($dataset->networks_v6),
            $dataset->duration()
        ));

        if ($dataset->rows_skipped > 0) {
            $this->warn(number_format($dataset->rows_skipped).' rows could not be read and were skipped.');
        }

        $this->line('Old data is removed and accounts get their signup country and ASN on the low queue.');

        return self::SUCCESS;
    }

    protected function lookup(): int
    {
        $ip = $this->argument('args')[0] ?? null;

        if (! $ip || IpDataService::key($ip) === null) {
            $this->error('Usage: app:ip-data lookup <ip>');

            return self::FAILURE;
        }

        if (! IpDataService::available()) {
            $this->warn('No IP data has been imported yet.');

            return self::FAILURE;
        }

        $result = IpDataService::lookup($ip);

        if ($result === null) {
            $this->info('No match in the active IP data.');

            return self::SUCCESS;
        }

        $this->table(['Field', 'Value'], [
            ['IP', $result['ip']],
            ['Country', $result['country'] ? $result['country']['code'].' '.$result['country']['name'] : ''],
            ['Country network', (string) ($result['country']['network'] ?? '')],
            ['ASN', $result['asn'] ? 'AS'.$result['asn']['number'].' '.$result['asn']['name'] : ''],
            ['Organization', (string) ($result['asn']['org'] ?? '')],
            ['Domain', (string) ($result['asn']['domain'] ?? '')],
            ['ASN network', (string) ($result['asn']['network'] ?? '')],
        ]);

        return self::SUCCESS;
    }

    protected function backfill(): int
    {
        if (! IpDataService::available()) {
            $this->warn('No IP data has been imported yet.');

            return self::FAILURE;
        }

        $all = (bool) $this->option('all');

        if ($this->option('queue')) {
            BackfillSignupNetworks::dispatch(0, $all);
            $this->info('Queued the backfill on the low queue.');

            return self::SUCCESS;
        }

        $total = DB::table('users')
            ->whereNotNull('app_register_ip')
            ->when(! $all, fn ($q) => $q->whereNull('signup_country')->whereNull('signup_asn'))
            ->count();

        $bar = $this->output->createProgressBar($total);
        $bar->start();
        $cursor = 0;

        while (($next = IpDataImportService::backfillSlice($cursor, 1000, $all)) !== null) {
            $bar->setProgress(min($total, $bar->getProgress() + 1000));
            $cursor = $next;
        }

        $bar->finish();
        $this->newLine(2);
        $this->info('Signup country and ASN are up to date.');

        return self::SUCCESS;
    }

    protected function cancel(): int
    {
        $id = (int) ($this->argument('args')[0] ?? 0);
        $dataset = $id ? IpDataset::find($id) : null;

        if (! $dataset) {
            $this->error('Usage: app:ip-data cancel <id>');

            return self::FAILURE;
        }

        if (! IpDataImportService::cancel($dataset, 'Cancelled from the command line.')) {
            $this->warn('That import is not running.');

            return self::FAILURE;
        }

        $this->info("Cancelled import #{$dataset->id}. The active data was not changed.");

        return self::SUCCESS;
    }

    protected function purge(): int
    {
        $datasets = IpDataset::query()
            ->whereNotIn('status', array_merge([IpDataset::STATUS_ACTIVE], IpDataset::RUNNING))
            ->whereNull('purged_at')
            ->get();

        $deleted = 0;

        foreach ($datasets as $dataset) {
            do {
                $count = IpDataImportService::purgeSlice($dataset);
                $deleted += $count;
            } while ($count >= IpDataImportService::PURGE_BATCH);
        }

        $files = IpDataImportService::cleanupUploads(3600);

        $this->info(sprintf('Removed %s old networks and %s unfinished uploads.', number_format($deleted), $files));

        return self::SUCCESS;
    }
}
