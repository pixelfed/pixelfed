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

class FixFollowerCount extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:followercount
        {id? : Profile id or username to resync (omit with --all)}
        {--all : Scan all local profiles and resync any with drifted counts}
        {--dispatch : Queue FollowServiceWarmCache instead of recomputing inline}
        {--dry-run : Report drift without changing anything}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Resync profiles.followers_count/following_count from the followers table (fixes cached-count drift)';

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
                : Profile::whereNull('domain')->where('username', $id)->first();

            if (! $profile) {
                $this->error('No profile found for "'.$id.'".');

                return 1;
            }

            $this->resyncOne($profile);

            return 0;
        }

        // --all: only touch profiles whose cached count actually drifted.
        $dryRun = $this->option('dry-run');
        if (! $dryRun && ! $this->confirm('Resync follower/following counts for all drifted local profiles?', true)) {
            $this->comment('Aborted.');

            return 0;
        }

        $fixed = 0;
        $scanned = 0;
        Profile::whereNull('domain')->whereNull('deleted_at')->lazyById(500)->each(function ($profile) use (&$fixed, &$scanned) {
            $scanned++;
            if ($this->resyncOne($profile, true)) {
                $fixed++;
            }
        });

        $this->newLine();
        $this->info('Scanned '.$scanned.' local profiles; '.($this->option('dry-run') ? 'drifted' : 'resynced').': '.$fixed.'.');

        return 0;
    }

    /**
     * Recompute (or report) the counts for a single profile.
     *
     * @return bool whether the profile was drifted
     */
    protected function resyncOne(Profile $profile, bool $quiet = false): bool
    {
        $liveFollowers = (int) Follower::whereFollowingId($profile->id)->count();
        $liveFollowing = (int) Follower::whereProfileId($profile->id)->count();

        $cachedFollowers = (int) $profile->followers_count;
        $cachedFollowing = (int) $profile->following_count;

        $drifted = $liveFollowers !== $cachedFollowers || $liveFollowing !== $cachedFollowing;

        if (! $drifted) {
            if (! $quiet) {
                $this->info($profile->username.' (id '.$profile->id.') is already in sync (followers='.$cachedFollowers.', following='.$cachedFollowing.').');
            }

            return false;
        }

        if (! $quiet) {
            $this->warn($profile->username.' (id '.$profile->id.') drift detected:');
            $this->line('  followers: cached='.$cachedFollowers.' live='.$liveFollowers);
            $this->line('  following: cached='.$cachedFollowing.' live='.$liveFollowing);
        }

        if ($this->option('dry-run')) {
            return true;
        }

        if ($this->option('dispatch')) {
            // Clear the throttle sync keys so the warm-cache job actually
            // re-runs, then queue it (also rebuilds the Redis sets).
            Cache::forget(FollowerService::FOLLOWERS_SYNC_KEY.$profile->id);
            Cache::forget(FollowerService::FOLLOWING_SYNC_KEY.$profile->id);
            FollowServiceWarmCache::dispatch($profile->id)->onQueue('low');
            if (! $quiet) {
                $this->info('  queued FollowServiceWarmCache for profile '.$profile->id.'.');
            }

            return true;
        }

        // Inline recompute of the DB columns from the source-of-truth table.
        $profile->followers_count = (int) DB::table('followers')->whereFollowingId($profile->id)->count();
        $profile->following_count = (int) DB::table('followers')->whereProfileId($profile->id)->count();
        $profile->save();

        // Bust the derived caches so reads reflect the corrected values.
        Cache::forget('profile:follower_count:'.$profile->id);
        Cache::forget('profile:following_count:'.$profile->id);
        AccountService::del($profile->id);

        if (! $quiet) {
            $this->info('  resynced to followers='.$profile->followers_count.', following='.$profile->following_count.'.');
        }

        return true;
    }
}
