<?php

namespace App\Console\Commands\Internal;

use App\Jobs\HomeFeedPipeline\FeedWarmCachePipeline;
use App\Models\User;
use App\Services\HomeTimelineService;
use App\Services\NetworkTimelineService;
use App\Services\PublicTimelineService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class TimelineCacheRebuild extends Command
{
    protected $signature = 'timeline:cache-rebuild
        {--feed=all : Which feed to rebuild: home|local|network|all}
        {--wipe : Only clear the cache(s); do not rebuild}
        {--pid= : Comma-separated profile ids to target for the home feed}
        {--all-home : Rebuild every local user home feed (heavy)}
        {--sync : Rebuild home feeds inline instead of dispatching to the queue}
        {--force : Skip the confirmation prompt}';

    protected $description = 'Wipe and rebuild the local, network, and home timeline caches';

    public function handle(): int
    {
        $feed = (string) $this->option('feed');

        if (! in_array($feed, ['home', 'local', 'network', 'all'], true)) {
            $this->error("Invalid --feed value '{$feed}'. Use home|local|network|all.");

            return self::FAILURE;
        }

        $wipe = (bool) $this->option('wipe');
        $verb = $wipe ? 'Wipe' : 'Rebuild';

        if (! $this->option('force') && ! $this->confirm("{$verb} the {$feed} timeline cache(s)?", true)) {
            $this->info('Aborted.');

            return self::SUCCESS;
        }

        if ($feed === 'local' || $feed === 'all') {
            $this->handleLocal($wipe);
        }

        if ($feed === 'network' || $feed === 'all') {
            $this->handleNetwork($wipe);
        }

        if ($feed === 'home' || $feed === 'all') {
            $this->handleHome($wipe);
        }

        return self::SUCCESS;
    }

    private function handleLocal(bool $wipe): void
    {
        Cache::forget('api:v1:timelines:public:cache_check');
        Redis::del(PublicTimelineService::CACHE_KEY);

        if ($wipe) {
            $this->line('local: cleared');

            return;
        }

        PublicTimelineService::warmCache(true, (int) config('instance.timeline.local.cache_size'));
        $this->line('local: rebuilt ('.PublicTimelineService::count().' posts)');
    }

    private function handleNetwork(bool $wipe): void
    {
        Cache::forget('api:v1:timelines:network:cache_check');
        Redis::del(NetworkTimelineService::CACHE_KEY);

        if ($wipe) {
            $this->line('network: cleared');

            return;
        }

        NetworkTimelineService::warmCache(true, (int) config('instance.timeline.network.cache_dropoff'));
        $this->line('network: rebuilt ('.NetworkTimelineService::count().' posts)');
    }

    private function handleHome(bool $wipe): void
    {
        $pids = $this->homeTargetProfileIds();

        if (empty($pids)) {
            $this->line('home: no profiles targeted (use --pid or --all-home)');

            return;
        }

        foreach ($pids as $pid) {
            Cache::forget('pf:services:apiv1:home:cached:coldbootcheck:'.$pid);
            Redis::del(HomeTimelineService::CACHE_KEY.$pid);
        }

        if ($wipe) {
            $this->line('home: cleared '.count($pids).' feed(s)');

            return;
        }

        if ($this->option('sync')) {
            $limit = (int) config('instance.timeline.home.cache_size');
            foreach ($pids as $pid) {
                HomeTimelineService::warmCache($pid, true, $limit, true);
            }
            $this->line('home: rebuilt '.count($pids).' feed(s) inline');

            return;
        }

        foreach ($pids as $pid) {
            FeedWarmCachePipeline::dispatch($pid)->onQueue('feed');
        }
        $this->line('home: dispatched '.count($pids).' warm job(s) to the feed queue');
    }

    /**
     * Resolve which home profile ids to act on.
     *
     * @return list<int>
     */
    private function homeTargetProfileIds(): array
    {
        if ($pidOption = $this->option('pid')) {
            return collect(explode(',', (string) $pidOption))
                ->map(fn ($v) => (int) trim($v))
                ->filter(fn ($v) => $v > 0)
                ->unique()
                ->values()
                ->all();
        }

        if ($this->option('all-home')) {
            // Most recently active users first, so their feeds warm before idle accounts.
            return User::whereNull('deleted_at')
                ->whereNotNull('profile_id')
                ->whereNull('status')
                ->orderByRaw('last_active_at IS NULL, last_active_at DESC')
                ->pluck('profile_id')
                ->map(fn ($v) => (int) $v)
                ->filter(fn ($v) => $v > 0)
                ->values()
                ->all();
        }

        return [];
    }
}
