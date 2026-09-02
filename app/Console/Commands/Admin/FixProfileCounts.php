<?php

namespace App\Console\Commands\Admin;

use App\Jobs\FollowPipeline\FollowServiceWarmCache;
use App\Models\Profile;
use App\Services\Account\AccountStatService;
use App\Services\FollowerService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

#[Signature('admin:fixProfileCounts {id? : Profile id or username to resync (omit with --all)} {--all : Scan all profiles and resync any with drifted counts (requires --scope)} {--active=* : Scan only local accounts active within N days (default 30). Bulk mode; mutually exclusive with --all} {--scope= : Which profiles to scan in --all mode: local, remote, or both} {--type= : Restrict to a single metric: followers, following, or statuses (default: all three)} {--dispatch : Queue FollowServiceWarmCache for follower/following instead of recomputing inline} {--dry-run : Report drift without changing anything} {--force : Skip the confirmation prompt (for scheduled/unattended runs)}')]
#[Description('Resync a profile\'s cached counts (followers, following, statuses) from source-of-truth tables. Use --all --scope=local|remote|both, or --active, for bulk reconciliation.')]
class FixProfileCounts extends Command
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
    protected const METRICS = ['followers', 'following', 'statuses'];

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
            $this->error('Provide a profile id/username, or pass --all or --active.');

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
            // --active filters on users.last_active_at, which only exists for
            // local accounts, so remote/both make no sense here.
            $this->error('--active only applies to local accounts; --scope must be omitted or "local".');

            return 1;
        }

        if ($id) {
            $profile = ctype_digit((string) $id)
                ? Profile::find($id)
                : Profile::where('username', $id)->first();

            if (! $profile) {
                $this->error('No profile found for "'.$id.'".');

                return 1;
            }

            $this->resyncOne($profile);

            return 0;
        }

        // Bulk mode (--all or --active): scan and only touch drifted profiles.
        $dryRun = $this->option('dry-run');

        $scopeLabel = $active
            ? 'local accounts active in the last '.$activeDays.' days'
            : $scope.' profiles';

        if (! $dryRun && ! $this->option('force') && ! $this->confirm('Resync cached counts for '.$scopeLabel.'?', true)) {
            $this->comment('Aborted.');

            return 0;
        }

        $query = Profile::whereNull('deleted_at');

        if ($active) {
            // Restrict to LOCAL profiles whose linked user logged in recently.
            // Remote profiles have no user row, so they are excluded here.
            $cutoff = now()->subDays($activeDays);
            $query->whereNotNull('user_id')
                ->whereHas('user', function ($q) use ($cutoff) {
                    $q->whereNotNull('last_active_at')
                        ->where('last_active_at', '>=', $cutoff);
                });
        } elseif ($scope === 'local') {
            // Local profiles have no domain.
            $query->whereNull('domain');
        } elseif ($scope === 'remote') {
            // Remote/federated profiles have a domain set.
            $query->whereNotNull('domain');
        }
        // scope === 'both' applies no domain filter.

        $fixed = 0;
        $scanned = 0;
        $query->lazyById(500)->each(function ($profile) use (&$fixed, &$scanned) {
            $scanned++;
            if ($this->resyncOne($profile)) {
                $fixed++;
            }
        });

        $this->newLine();
        $this->info('Scanned '.$scanned.' profiles ('.$scopeLabel.'); '.($this->option('dry-run') ? 'drifted' : 'resynced').': '.$fixed.'.');

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
     * Recompute (or report) the cached counts for a single profile.
     * Only emits output when drift is detected; silent otherwise.
     *
     * @return bool whether the profile was drifted
     */
    protected function resyncOne(Profile $profile): bool
    {
        // Which metrics to consider: a single --type, or all three.
        $type = $this->option('type');
        $metrics = $type !== null ? [$type] : self::METRICS;

        // Compute drift only for the selected metrics, using the canonical
        // source-of-truth logic (shared with the scheduled updater).
        $drift = [];
        if (in_array('followers', $metrics, true)) {
            $drift['followers'] = [
                'cached' => (int) $profile->followers_count,
                'live' => AccountStatService::recalculateFollowerCount($profile->id),
            ];
        }
        if (in_array('following', $metrics, true)) {
            $drift['following'] = [
                'cached' => (int) $profile->following_count,
                'live' => AccountStatService::recalculateFollowingCount($profile->id),
            ];
        }
        if (in_array('statuses', $metrics, true)) {
            $drift['statuses'] = [
                'cached' => (int) $profile->status_count,
                'live' => AccountStatService::recalculateStatusCount($profile->id),
            ];
        }

        $drifted = array_filter($drift, fn ($m) => $m['cached'] !== $m['live']);

        if (empty($drifted)) {
            // No drift on the selected metrics: stay silent.
            return false;
        }

        // Drift detected: report exactly what drifted.
        $this->warn($profile->username.' (id '.$profile->id.') drift detected:');
        foreach ($drifted as $metric => $m) {
            $this->line('  '.str_pad($metric.':', 11).'cached='.$m['cached'].' live='.$m['live']);
        }

        if ($this->option('dry-run')) {
            return true;
        }

        $followOrFollowingDrift = isset($drifted['followers']) || isset($drifted['following']);

        if ($this->option('dispatch') && $followOrFollowingDrift) {
            // Fix status_count now (if in scope), then let the warm-cache job
            // own the follower/following columns and rebuild the Redis sets.
            if (isset($drift['statuses'])) {
                AccountStatService::reconcileProfileCounts($profile, ['statuses']);
            }
            Cache::forget(FollowerService::FOLLOWERS_SYNC_KEY.$profile->id);
            Cache::forget(FollowerService::FOLLOWING_SYNC_KEY.$profile->id);
            FollowServiceWarmCache::dispatch($profile->id)->onQueue('low');
            $this->info('  queued FollowServiceWarmCache for profile '.$profile->id.'.');

            return true;
        }

        // Inline recompute of the selected metrics via the shared reconciler.
        AccountStatService::reconcileProfileCounts($profile, $metrics);
        $profile->refresh();
        $this->info('  resynced to followers='.$profile->followers_count.', following='.$profile->following_count.', statuses='.$profile->status_count.'.');

        return true;
    }
}
