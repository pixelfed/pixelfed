<?php

namespace App\Console\Commands\Admin;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PruneOldNotifications extends Command
{
    protected $signature = 'notifications:prune-old
        {--months=18 : Keep notifications newer than this many months}
        {--batch=100000 : Number of rows to delete per batch}';

    protected $description = 'Prune notifications older than the retention period';

    public function handle(): int
    {
        $cutoff = now()->subMonths((int) $this->option('months'));
        $batch = max(100, (int) $this->option('batch'));

        $this->info("Pruning notifications older than {$cutoff->toDateTimeString()}");
        $this->info("Batch size: {$batch}");

        $total = 0;

        do {
            $deleted = DB::delete(
                '
                DELETE FROM `notifications`
                WHERE `created_at` < ?
                ORDER BY `created_at`
                LIMIT ?
                ',
                [$cutoff, $batch]
            );

            $total += $deleted;

            $this->line("Deleted {$deleted} rows ({$total} total)");

            if ($deleted > 0) {
                usleep(100_000);
            }
        } while ($deleted === $batch);

        $this->info("Finished. Deleted {$total} notifications.");

        return self::SUCCESS;
    }
}
