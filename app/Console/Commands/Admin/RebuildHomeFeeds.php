<?php

namespace App\Console\Commands\Admin;

use App\Jobs\HomeFeedPipeline\FeedWarmCachePipeline;
use App\Models\Profile;
use App\Services\HomeTimelineService;
use App\Services\NetworkTimelineService;
use App\Services\PublicTimelineService;
use Illuminate\Console\Command;

class RebuildHomeFeeds extends Command
{
    protected $signature = 'app:home-feed-rebuild
        {id? : Profile id or username to rebuild (omit with --all or --active)}
        {--all : Rebuild every local account}
        {--active=* : Rebuild only local accounts active within N days (default 30)}
        {--local : Also rebuild the instance-wide local timeline}
        {--network : Also rebuild the instance-wide network timeline}
        {--sync : Rebuild home feeds inline instead of queueing FeedWarmCachePipeline}
        {--dry-run : Report what would be rebuilt without changing anything}
        {--force : Skip the confirmation prompt}';

    protected const DEFAULT_ACTIVE_DAYS = 30;

    protected $description = 'Clear and rebuild cached timelines from the database. Rebuild home feeds for a single account, --active, or --all, and optionally the instance-wide --local and --network timelines.';

    public function handle()
    {
        $id = $this->argument('id');
        $all = (bool) $this->option('all');
        $activeOpt = $this->option('active');
        $active = $activeOpt !== [] && $activeOpt !== null && $activeOpt !== false;
        $activeDays = is_array($activeOpt)
            ? (int) ($activeOpt[0] ?? self::DEFAULT_ACTIVE_DAYS)
            : (int) ($activeOpt ?: self::DEFAULT_ACTIVE_DAYS);
        $sync = (bool) $this->option('sync');
        $dryRun = (bool) $this->option('dry-run');
        $local = (bool) $this->option('local');
        $network = (bool) $this->option('network');

        $hasHomeTarget = $id || $all || $active;

        if (! $hasHomeTarget && ! $local && ! $network) {
            $this->error('Specify a profile id/username, --active, --all, --local, or --network.');

            return 1;
        }

        if ($local) {
            $this->rebuildPublic($dryRun);
        }

        if ($network) {
            $this->rebuildNetwork($dryRun);
        }

        if (! $hasHomeTarget) {
            return 0;
        }

        if ($id) {
            $profile = ctype_digit((string) $id)
                ? Profile::find($id)
                : Profile::whereUsername($id)->first();

            if (! $profile) {
                $this->error('Profile not found.');

                return 1;
            }

            if ($profile->domain !== null) {
                $this->error('Home feeds exist only for local accounts.');

                return 1;
            }

            $this->rebuildOne($profile->id, $sync, $dryRun);
            $this->info('Done.');

            return 0;
        }

        $scopeLabel = $active
            ? 'local accounts active in the last '.$activeDays.' days'
            : 'all local accounts';

        if (! $dryRun && ! $this->option('force') && ! $this->confirm('Rebuild cached home feeds for '.$scopeLabel.'?', true)) {
            $this->comment('Aborted.');

            return 0;
        }

        $query = Profile::whereNull('deleted_at')->whereNull('domain')->whereNotNull('user_id');

        if ($active) {
            $cutoff = now()->subDays($activeDays);
            $query->whereHas('user', function ($q) use ($cutoff) {
                $q->whereNotNull('last_active_at')
                    ->where('last_active_at', '>=', $cutoff);
            });
        }

        $count = 0;
        $query->lazyById(500)->each(function ($profile) use (&$count, $sync, $dryRun) {
            $this->rebuildOne($profile->id, $sync, $dryRun);
            $count++;
        });

        $this->info(($dryRun ? 'Would rebuild ' : 'Rebuilt ').$count.' home feed(s).');

        return 0;
    }

    protected function rebuildOne($pid, bool $sync, bool $dryRun): void
    {
        if ($dryRun) {
            $this->line('pid '.$pid.': '.HomeTimelineService::count($pid).' cached entries');

            return;
        }

        if ($sync) {
            HomeTimelineService::warmCache($pid, true, 400);

            return;
        }

        FeedWarmCachePipeline::dispatch($pid)->onQueue('feed');
    }

    protected function rebuildPublic(bool $dryRun): void
    {
        if ($dryRun) {
            $this->line('local timeline: '.PublicTimelineService::count().' cached entries');

            return;
        }

        PublicTimelineService::warmCache(true, 400);
        $this->info('Rebuilt the local timeline.');
    }

    protected function rebuildNetwork(bool $dryRun): void
    {
        if ($dryRun) {
            $this->line('network timeline: '.NetworkTimelineService::count().' cached entries');

            return;
        }

        NetworkTimelineService::warmCache(true, config('instance.timeline.network.cache_dropoff'));
        $this->info('Rebuilt the network timeline.');
    }
}
