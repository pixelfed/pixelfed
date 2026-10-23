<?php

namespace App\Console\Commands\Internal;

use App\Services\ConfigCacheService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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
        // GLITCH MODIFIED: "real" post count (excludes replies/reblogs/remote), see instance.glitch.real_stat_count
        if (! $cache || ! isset($cache['count']) || ! isset($cache['realCount'])) {
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
        // GLITCH MODIFIED: reuse updateAndCache so realCount is populated on first run
        $this->updateAndCache();
    }

    protected function getCached()
    {
        return Storage::json('total_local_posts.json');
    }

    protected function updateAndCache()
    {
        $res = [
            'count' => $this->getTotalLocalPosts(),
            // GLITCH MODIFIED: "real" post count
            'realCount' => $this->getRealTotalLocalPosts(),
        ];
        Storage::put('total_local_posts.json', json_encode($res, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        ConfigCacheService::put('instance.stats.total_local_posts', $res['count']);
        // GLITCH MODIFIED: "real" post count
        ConfigCacheService::put('instance.stats.real_total_local_posts', $res['realCount']);
    }

    protected function getTotalLocalPosts()
    {
        if ((bool) config('instance.total_count_estimate') && db_is_mysql_maria()) {
            return DB::select("EXPLAIN SELECT COUNT(*) FROM statuses WHERE deleted_at IS NULL AND uri IS NULL and local = 1 AND type != 'share'")[0]->rows;
        }

        return DB::table('statuses')
            ->whereNull('deleted_at')
            ->where('local', true)
            ->whereNot('type', 'share')
            ->count();
    }

    // GLITCH MODIFIED START: "real" post count
    protected function getRealTotalLocalPosts()
    {
        return DB::table('statuses')
            ->whereNull(['url', 'in_reply_to_id', 'reblog_of_id', 'deleted_at'])
            ->where('local', true)
            ->whereNot('type', 'share')
            ->count();
    }
    // GLITCH MODIFIED END
}
