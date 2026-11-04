<?php

namespace App\Console\Commands\Admin;

use App\Services\Status\RemoteStatusPurgeService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RemoveRemoteStatuses extends Command
{
    protected $signature = 'statuses:remove-remote
        {--days=90 : Remove remote statuses fetched more than this many days ago}
        {--batch=1000 : Status rows to scan per window}
        {--limit=0 : Stop after removing this many statuses (0 = no limit)}
        {--sleep=0 : Milliseconds to pause between windows, to let replicas catch up}
        {--start-id= : Resume below this status id (printed by an interrupted run)}
        {--skip-media : Keep statuses whose media is cached on local or cloud storage}
        {--dry-run : Report what would be removed without changing anything}
        {--force : Skip the confirmation prompt}';

    protected $description = 'Remove old remote statuses that no local account has interacted with, and their related rows';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $batch = max(100, min(5000, (int) $this->option('batch')));
        $limit = max(0, (int) $this->option('limit'));
        $sleepMs = max(0, (int) $this->option('sleep'));
        $dryRun = (bool) $this->option('dry-run');

        if ($days < 1) {
            $this->error('--days must be at least 1.');

            return self::INVALID;
        }

        $cutoff = now()->subDays($days);
        $maxId = RemoteStatusPurgeService::maxIdForCutoff($cutoff);
        $cursor = $maxId;

        if ($this->option('start-id') !== null) {
            $startId = (int) $this->option('start-id');
            if ($startId < 1) {
                $this->error('--start-id must be a positive status id.');

                return self::INVALID;
            }
            $cursor = min($startId, $maxId);
        }

        if ($cursor < 1) {
            $this->info('No statuses are older than the cutoff.');

            return self::SUCCESS;
        }

        $this->line('Removing remote statuses fetched before <info>'.$cutoff->toDateTimeString().' UTC</info> (status id below '.$cursor.').');
        $this->line('Statuses are kept when a local account liked, bookmarked, shared, replied to, was mentioned or tagged in, collected, or voted on them, or when a report, interstitial or DM references them.');

        if ($dryRun) {
            $this->warn('Dry run: nothing will be changed. Parents of purgeable replies are counted as kept, so the real run removes more than this estimate.');
        } elseif (! $this->option('force') && ! $this->confirm('This permanently deletes rows. Continue?')) {
            return self::FAILURE;
        }

        $service = new RemoteStatusPurgeService($dryRun, (bool) $this->option('skip-media'));

        $protected = $service->loadModerationIds();
        $this->line("Loaded {$protected} status ids referenced by moderation records.");

        $totals = ['scanned' => 0, 'candidates' => 0, 'deleted' => 0, 'cascaded' => 0, 'kept' => 0, 'media_jobs' => 0];
        $windows = 0;
        $started = microtime(true);

        while (true) {
            $result = $service->processWindow($cursor, $batch);

            foreach ($totals as $key => $value) {
                $totals[$key] = $value + $result[$key];
            }

            if ($result['next_cursor'] === null) {
                break;
            }

            $cursor = $result['next_cursor'];
            $windows++;

            if ($windows % 100 === 0) {
                $this->progress($totals, $cursor, $started, $dryRun);
            }

            if ($limit > 0 && $totals['deleted'] >= $limit) {
                $this->warn("Reached --limit={$limit}. Resume with --start-id={$cursor}");
                break;
            }

            if ($sleepMs > 0 && $result['deleted'] > 0) {
                usleep($sleepMs * 1000);
            }
        }

        $this->progress($totals, $cursor, $started, $dryRun);

        $verb = $dryRun ? 'Would remove' : 'Removed';
        $this->info("{$verb} {$totals['deleted']} statuses and {$totals['cascaded']} leftover shares. Kept {$totals['kept']} of {$totals['candidates']} remote statuses scanned.");

        if ($totals['media_jobs'] > 0) {
            $this->line("Queued {$totals['media_jobs']} cached media files for deletion on the mmo queue.");
        }

        if (! $dryRun && $totals['deleted'] > 0 && DB::getDriverName() === 'mysql') {
            $this->line('MySQL does not shrink table files on DELETE. Run OPTIMIZE TABLE on statuses, media, likes, mentions and status_hashtags to reclaim disk space (not needed before a dump-based migration).');
        }

        return self::SUCCESS;
    }

    protected function progress(array $totals, int $cursor, float $started, bool $dryRun): void
    {
        $elapsed = max(1, (int) (microtime(true) - $started));
        $verb = $dryRun ? 'purgeable' : 'removed';

        $this->line(sprintf(
            '[%ds] scanned %d, remote %d, %s %d (+%d shares), kept %d, next --start-id=%d',
            $elapsed,
            $totals['scanned'],
            $totals['candidates'],
            $verb,
            $totals['deleted'],
            $totals['cascaded'],
            $totals['kept'],
            $cursor
        ));
    }
}
