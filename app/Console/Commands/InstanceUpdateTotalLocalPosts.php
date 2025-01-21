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
        $count = DB::table('statuses')->whereNull(['url', 'deleted_at'])->count();
        $res = [
            'count' => $count,
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
        $count = DB::table('statuses')->whereNull(['url', 'deleted_at'])->count();
        $res = [
            'count' => $count,
        ];
        Storage::put('total_local_posts.json', json_encode($res, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        ConfigCacheService::put('instance.stats.total_local_posts', $res['count']);

    }
}
