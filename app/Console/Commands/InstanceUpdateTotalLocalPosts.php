<?php

namespace App\Console\Commands;

use App\Services\ConfigCacheService;
use Cache;
use DB;
use Illuminate\Console\Command;
use Storage;

class InstanceUpdateTotalLocalPosts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:instance-update-total-local-posts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the total number of local statuses/post count';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $cached = $this->checkForCache();
        if (! $cached) {
            $this->initCache();

            return;
        }
        $cache = $this->getCached();
        if (! $cache || ! isset($cache['count'])) {
            $this->error('Problem fetching cache');

            return;
        }
        $this->updateAndCache();
        Cache::forget('api:nodeinfo');

    }

    protected function checkForCache()
    {
        return Storage::exists('total_local_posts.json');
    }

    protected function initCache()
    {
        $res = [
            'count' => $this->getTotalLocalPosts(),
        ];
        Storage::put('total_local_posts.json', json_encode($res, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        ConfigCacheService::put('instance.stats.total_local_posts', $res['count']);
    }

    protected function getCached()
    {
        return Storage::json('total_local_posts.json');
    }

    protected function updateAndCache()
    {
        $res = [
            'count' => $this->getTotalLocalPosts(),
        ];
        Storage::put('total_local_posts.json', json_encode($res, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        ConfigCacheService::put('instance.stats.total_local_posts', $res['count']);

    }

    protected function getTotalLocalPosts()
    {
        if ((bool) config('instance.total_count_estimate') && config('database.default') === 'mysql') {
            return DB::select("EXPLAIN SELECT COUNT(*) FROM statuses WHERE deleted_at IS NULL AND uri IS NULL and local = 1 AND type != 'share'")[0]->rows;
        }

        return DB::table('statuses')
            ->whereNull('deleted_at')
            ->where('local', true)
            ->whereNot('type', 'share')
            ->count();
    }
}
