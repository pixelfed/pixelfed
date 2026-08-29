<?php

namespace App\Console\Commands;

use App\Jobs\FollowPipeline\FollowServiceWarmCache;
use App\Models\Profile;
use App\Services\Account\AccountStatService;
use App\Services\FollowerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class FixProfileCounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:profilecounts
        {id? : Profile id or username to resync (omit with --all)}
        {--all : Scan all profiles and resync any with drifted counts}
        {--dispatch : Queue FollowServiceWarmCache for follower/following instead of recomputing inline}
        {--dry-run : Report drift without changing anything}
        {--force : Skip the confirmation prompt (for scheduled/unattended runs)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Resync a profile\'s cached counts (followers, following, statuses) from source-of-truth tables';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $id = $this->argument('id');
        $all = $this->option('all');

        if (! $id && ! $all) {
            $this->error('Provide a profile id/username, or pass --all.');

            return 1;
        }

        if ($id && $all) {
            $this->error('Pass either an id or --all, not both.');

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

        // --all: scan every profile, only touch/report the ones that drifted.
        $dryRun = $this->option('dry-run');
        if (! $dryRun && ! $this->option('force') && ! $this->confirm('Resync cached counts for all drifted profiles?', true)) {
            $this->comment('Aborted.');

            return 0;
        }

        $fixed = 0;
        $scanned = 0;
        Profile::whereNull('deleted_at')->lazyById(500)->each(function ($profile) use (&$fixed, &$scanned) {
            $scanned++;
            if ($this->resyncOne($profile)) {
                $fixed++;
            }
        });

        $this->newLine();
        $this->info('Scanned '.$scanned.' profiles; '.($this->option('dry-run') ? 'drifted' : 'resynced').': '.$fixed.'.');

        return 0;
    }

    /**
     * Recompute (or report) the cached counts for a single profile.
     * Only emits output when drift is detected; silent otherwise.
     *
     * @return bool whether the profile was drifted
     */
    protected function resyncOne(Profile $profile): bool
    {
        // Compute drift for all three metrics using the canonical
        // source-of-truth logic (shared with the scheduled updater).
        $followers = [
            'cached' => (int) $profile->followers_count,
            'live' => AccountStatService::recalculateFollowerCount($profile->id),
        ];
        $following = [
            'cached' => (int) $profile->following_count,
            'live' => AccountStatService::recalculateFollowingCount($profile->id),
        ];
        $statuses = [
            'cached' => (int) $profile->status_count,
            'live' => AccountStatService::recalculateStatusCount($profile->id),
        ];

        $followersDrift = $followers['cached'] !== $followers['live'];
        $followingDrift = $following['cached'] !== $following['live'];
        $statusesDrift = $statuses['cached'] !== $statuses['live'];

        if (! $followersDrift && ! $followingDrift && ! $statusesDrift) {
            // No drift: stay silent.
            return false;
        }

        // Drift detected: report exactly what drifted.
        $this->warn($profile->username.' (id '.$profile->id.') drift detected:');
        if ($followersDrift) {
            $this->line('  followers: cached='.$followers['cached'].' live='.$followers['live']);
        }
        if ($followingDrift) {
            $this->line('  following: cached='.$following['cached'].' live='.$following['live']);
        }
        if ($statusesDrift) {
            $this->line('  statuses:  cached='.$statuses['cached'].' live='.$statuses['live']);
        }

        if ($this->option('dry-run')) {
            return true;
        }

        if ($this->option('dispatch') && ($followersDrift || $followingDrift)) {
            // Fix status_count now (via the shared reconciler), then let the
            // warm-cache job own the follower/following columns and rebuild
            // the Redis sets.
            AccountStatService::reconcileProfileCounts($profile, ['statuses']);
            Cache::forget(FollowerService::FOLLOWERS_SYNC_KEY.$profile->id);
            Cache::forget(FollowerService::FOLLOWING_SYNC_KEY.$profile->id);
            FollowServiceWarmCache::dispatch($profile->id)->onQueue('low');
            $this->info('  queued FollowServiceWarmCache for profile '.$profile->id.'; statuses='.$statuses['live'].'.');

            return true;
        }

        // Inline recompute of all drifted columns via the shared reconciler.
        AccountStatService::reconcileProfileCounts($profile);
        $this->info('  resynced to followers='.$profile->followers_count.', following='.$profile->following_count.', statuses='.$profile->status_count.'.');

        return true;
    }
}
