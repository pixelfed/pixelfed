<?php

namespace App\Console\Commands\Admin;

use App\Models\Status;
use App\Services\StatusService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('admin:fixPostCounts {id? : Status id to resync (omit with --all)} {--all : Scan statuses and resync any with drifted counts (requires --scope)} {--active=* : Scan only posts by local accounts active within N days (default 30). Bulk mode; mutually exclusive with --all} {--scope= : Which statuses to scan in --all mode: local, remote, or both} {--type= : Restrict to a single metric: likes, boosts, or comments (default: all three)} {--dry-run : Report drift without changing anything} {--force : Skip the confirmation prompt (for scheduled/unattended runs)}')]
#[Description('Resync a post\'s cached counts (likes, boosts, comments) from source-of-truth tables. Use --all --scope=local|remote|both, or --active, for bulk reconciliation.')]
class FixPostCounts extends Command
{
    /**
     * Default active window (days) when --active is passed without a value.
     */
    protected const DEFAULT_ACTIVE_DAYS = 30;

    /**
     * Metrics this command can reconcile.
     *
     * @var array<int, string>
     */
    protected const METRICS = ['likes', 'boosts', 'comments'];

    /**
     * Valid --scope values for --all mode.
     *
     * @var array<int, string>
     */
    protected const SCOPES = ['local', 'remote', 'both'];

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $id = $this->argument('id');
        $all = $this->option('all');
        $activeDays = $this->resolveActiveDays();
        $active = $activeDays !== null;

        // Exactly one mode: a single id, --all, or --active.
        $modes = (int) (bool) $id + (int) $all + (int) $active;

        if ($modes === 0) {
            $this->error('Provide a status id, or pass --all or --active.');

            return 1;
        }

        if ($modes > 1) {
            $this->error('Pass only one of: <id>, --all, or --active.');

            return 1;
        }

        $type = $this->option('type');
        if ($type !== null && ! in_array($type, self::METRICS, true)) {
            $this->error('Invalid --type "'.$type.'". Use one of: '.implode(', ', self::METRICS).'.');

            return 1;
        }

        $scope = $this->option('scope');
        if ($scope !== null && ! in_array($scope, self::SCOPES, true)) {
            $this->error('Invalid --scope "'.$scope.'". Use one of: '.implode(', ', self::SCOPES).'.');

            return 1;
        }

        if ($all && $scope === null) {
            $this->error('--all requires --scope (local, remote, or both).');

            return 1;
        }

        if ($active && $scope !== null && $scope !== 'local') {
            // --active filters on the author's users.last_active_at, which only
            // exists for local accounts, so remote/both make no sense here.
            $this->error('--active only applies to local accounts; --scope must be omitted or "local".');

            return 1;
        }

        if ($id) {
            $status = Status::find($id);

            if (! $status) {
                $this->error('No status found for id "'.$id.'".');

                return 1;
            }

            $this->resyncOne($status);

            return 0;
        }

        // Bulk mode (--all or --active): scan and only touch drifted statuses.
        $dryRun = $this->option('dry-run');

        $scopeLabel = $active
            ? 'posts by local accounts active in the last '.$activeDays.' days'
            : $scope.' statuses';

        if (! $dryRun && ! $this->option('force') && ! $this->confirm('Resync cached counts for '.$scopeLabel.'?', true)) {
            $this->comment('Aborted.');

            return 0;
        }

        $query = Status::whereNull('deleted_at');

        if ($active) {
            // Restrict to statuses authored by LOCAL profiles whose linked user
            // logged in recently. Remote posts have no local user, so they are
            // excluded here.
            $cutoff = now()->subDays($activeDays);
            $query->whereLocal(true)
                ->whereHas('profile.user', function ($q) use ($cutoff) {
                    $q->whereNotNull('last_active_at')
                        ->where('last_active_at', '>=', $cutoff);
                });
        } elseif ($scope === 'local') {
            $query->whereLocal(true);
        } elseif ($scope === 'remote') {
            $query->whereLocal(false);
        }
        // scope === 'both' applies no local/remote filter.

        $fixed = 0;
        $scanned = 0;
        $query->lazyById(500)->each(function ($status) use (&$fixed, &$scanned) {
            $scanned++;
            if ($this->resyncOne($status)) {
                $fixed++;
            }
        });

        $this->newLine();
        $this->info('Scanned '.$scanned.' statuses ('.$scopeLabel.'); '.($this->option('dry-run') ? 'drifted' : 'resynced').': '.$fixed.'.');

        return 0;
    }

    /**
     * Resolve the --active window in days, or null if the flag was not passed.
     */
    protected function resolveActiveDays(): ?int
    {
        $values = (array) $this->option('active');

        if (empty($values)) {
            return null;
        }

        $last = end($values);

        // `--active` with no value arrives as an empty string / null.
        if ($last === null || $last === '') {
            return self::DEFAULT_ACTIVE_DAYS;
        }

        $days = (int) $last;

        return $days > 0 ? $days : self::DEFAULT_ACTIVE_DAYS;
    }

    /**
     * Recompute (or report) the cached counts for a single status.
     * Only emits output when drift is detected; silent otherwise.
     *
     * @return bool whether the status was drifted
     */
    protected function resyncOne(Status $status): bool
    {
        // Which metrics to consider: a single --type, or all three.
        $type = $this->option('type');
        $metrics = $type !== null ? [$type] : self::METRICS;

        // Compute drift only for the selected metrics, using the canonical
        // source-of-truth logic shared with StatusService.
        $drift = [];
        if (in_array('likes', $metrics, true)) {
            $drift['likes'] = [
                'cached' => (int) $status->likes_count,
                'live' => StatusService::recalculateLikeCount($status->id),
            ];
        }
        if (in_array('boosts', $metrics, true)) {
            $drift['boosts'] = [
                'cached' => (int) $status->reblogs_count,
                'live' => StatusService::recalculateReblogCount($status->id),
            ];
        }
        if (in_array('comments', $metrics, true)) {
            $drift['comments'] = [
                'cached' => (int) $status->reply_count,
                'live' => StatusService::recalculateReplyCount($status->id),
            ];
        }

        $drifted = array_filter($drift, fn ($m) => $m['cached'] !== $m['live']);

        if (empty($drifted)) {
            // No drift on the selected metrics: stay silent.
            return false;
        }

        // Drift detected: report exactly what drifted.
        $this->warn('status id '.$status->id.' drift detected:');
        foreach ($drifted as $metric => $m) {
            $this->line('  '.str_pad($metric.':', 11).'cached='.$m['cached'].' live='.$m['live']);
        }

        if ($this->option('dry-run')) {
            return true;
        }

        // Inline recompute of the selected metrics via the shared reconciler.
        StatusService::reconcileStatusCounts($status, $metrics);

        // Report only the metrics that actually changed, as before -> after,
        // so the summary can never imply a metric was touched when it wasn't.
        $changes = [];
        foreach ($drifted as $metric => $m) {
            $changes[] = $metric.' '.$m['cached'].'->'.$m['live'];
        }
        $this->info('  resynced: '.implode(', ', $changes).'.');

        return true;
    }
}
