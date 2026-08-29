<?php

namespace App\Console\Commands;

use App\Jobs\FollowPipeline\FollowServiceWarmCache;
use App\Models\Follower;
use App\Models\Profile;
use App\Services\AccountService;
use App\Services\FollowerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

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
        {--dry-run : Report drift without changing anything}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Resync a profile\'s cached counts (followers, following, statuses) from source-of-truth tables';

    /**
     * Visible scopes counted for status_count (matches profile status semantics).
     *
     * @var array<int, string>
     */
    protected const STATUS_SCOPES = ['public', 'private', 'unlisted'];

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
        if (! $dryRun && ! $this->confirm('Resync cached counts for all drifted profiles?', true)) {
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
        $liveFollowers = (int) Follower::whereFollowingId($profile->id)->count();
        $liveFollowing = (int) Follower::whereProfileId($profile->id)->count();
        $liveStatuses = (int) $this->liveStatusCount($profile);

        $cachedFollowers = (int) $profile->followers_count;
        $cachedFollowing = (int) $profile->following_count;
        $cachedStatuses = (int) $profile->status_count;

        $followersDrift = $liveFollowers !== $cachedFollowers;
        $followingDrift = $liveFollowing !== $cachedFollowing;
        $statusesDrift = $liveStatuses !== $cachedStatuses;

        if (! $followersDrift && ! $followingDrift && ! $statusesDrift) {
            // No drift: stay silent.
            return false;
        }

        // Drift detected: report exactly what drifted.
        $this->warn($profile->username.' (id '.$profile->id.') drift detected:');
        if ($followersDrift) {
            $this->line('  followers: cached='.$cachedFollowers.' live='.$liveFollowers);
        }
        if ($followingDrift) {
            $this->line('  following: cached='.$cachedFollowing.' live='.$liveFollowing);
        }
        if ($statusesDrift) {
            $this->line('  statuses:  cached='.$cachedStatuses.' live='.$liveStatuses);
        }

        if ($this->option('dry-run')) {
            return true;
        }

        // status_count is always recomputed inline (no queue involved).
        if ($statusesDrift) {
            $profile->status_count = $liveStatuses;
        }

        if ($this->option('dispatch') && ($followersDrift || $followingDrift)) {
            // Persist any status fix first, then let the warm-cache job own
            // the follower/following columns and rebuild the Redis sets.
            if ($statusesDrift) {
                $profile->save();
                Cache::forget('profile:status_count:'.$profile->id);
            }
            Cache::forget(FollowerService::FOLLOWERS_SYNC_KEY.$profile->id);
            Cache::forget(FollowerService::FOLLOWING_SYNC_KEY.$profile->id);
            FollowServiceWarmCache::dispatch($profile->id)->onQueue('low');
            $this->info('  queued FollowServiceWarmCache for profile '.$profile->id.'; statuses='.$profile->status_count.'.');

            return true;
        }

        // Inline recompute of the drifted columns from source-of-truth tables.
        if ($followersDrift) {
            $profile->followers_count = (int) DB::table('followers')->whereFollowingId($profile->id)->count();
        }
        if ($followingDrift) {
            $profile->following_count = (int) DB::table('followers')->whereProfileId($profile->id)->count();
        }
        $profile->save();

        // Bust the derived caches so reads reflect the corrected values.
        Cache::forget('profile:follower_count:'.$profile->id);
        Cache::forget('profile:following_count:'.$profile->id);
        Cache::forget('profile:status_count:'.$profile->id);
        AccountService::del($profile->id);

        $this->info('  resynced to followers='.$profile->followers_count.', following='.$profile->following_count.', statuses='.$profile->status_count.'.');

        return true;
    }

    /**
     * Source-of-truth status count for a profile.
     */
    protected function liveStatusCount(Profile $profile): int
    {
        return $profile->statuses()
            ->getQuery()
            ->whereIn('scope', self::STATUS_SCOPES)
            ->count();
    }
}
