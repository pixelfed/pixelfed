<?php

namespace App\Console\Commands\Internal;

use App\Services\HomeTimelineService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class TimelineHomeTtlBackfill extends Command
{
    protected $signature = 'timeline:home-ttl-backfill';

    protected $description = 'Stamp an idle TTL on pre-existing home timeline cache keys that have no expiry';

    public function handle(): int
    {
        $idle = (int) config('instance.timeline.home.ttl_idle');

        if ($idle <= 0) {
            $this->error('instance.timeline.home.ttl_idle is non-positive; nothing to backfill.');

            return self::FAILURE;
        }

        $prefix = (string) config('database.redis.options.prefix', '');
        $pattern = $prefix.HomeTimelineService::CACHE_KEY.'*';
        $builtPrefix = HomeTimelineService::BUILT_KEY;
        $idleSeconds = $idle * 3600;

        $updated = 0;
        $skipped = 0;
        $cursor = '0';

        do {
            [$cursor, $keys] = Redis::scan($cursor, ['match' => $pattern, 'count' => 500]);

            if (! $keys) {
                continue;
            }

            foreach ($keys as $key) {
                $bare = $prefix !== '' && str_starts_with($key, $prefix)
                    ? substr($key, strlen($prefix))
                    : $key;

                if (str_starts_with($bare, $builtPrefix)) {
                    continue;
                }

                $ttl = Redis::ttl($bare);

                if ($ttl === -1) {
                    Redis::expire($bare, $idleSeconds);
                    $updated++;
                } else {
                    $skipped++;
                }
            }
        } while ($cursor !== '0' && $cursor !== 0);

        $this->line("home ttl backfill: {$updated} updated, {$skipped} skipped");

        return self::SUCCESS;
    }
}
