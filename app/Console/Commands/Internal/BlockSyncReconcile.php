<?php

namespace App\Console\Commands\Internal;

use App\Jobs\Federation\BlockSyncPipeline;
use App\Services\BlockSyncService;
use App\Services\DeliveryHostService;
use App\Services\FollowersSyncService;
use App\Services\InstanceService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

#[Signature('federation:block-sync-reconcile {--limit= : Maximum peers to reconcile} {--spread=3600 : Seconds to spread the jobs over}')]
#[Description('FEP-070c: reconcile blocks received from peers with their synchronization endpoints')]
class BlockSyncReconcile extends Command
{
    const CURSOR_KEY = 'pf:services:block-sync:reconcile-cursor';

    public function handle(): int
    {
        if (! BlockSyncService::receiving()) {
            $this->info('Block synchronization is not enabled.');

            return self::SUCCESS;
        }

        $limit = max(1, (int) ($this->option('limit') ?? config('federation.activitypub.block_sync.reconcile_limit', 1000)));
        $spread = max(0, (int) $this->option('spread'));
        $banned = (array) InstanceService::getBannedDomains();
        $cursor = (int) Cache::get(self::CURSOR_KEY, 0);
        $lastId = $cursor;
        $dispatched = 0;

        $peers = fn () => DB::table('instances')
            ->whereNotNull('block_sync_url')
            ->where('banned', false)
            ->select('id', 'domain', 'block_sync_url');

        $process = function ($instances) use (&$dispatched, &$lastId, $limit, $spread, $banned) {
            foreach ($instances as $instance) {
                if ($dispatched >= $limit) {
                    return false;
                }

                $lastId = (int) $instance->id;

                $domain = strtolower((string) $instance->domain);
                $url = (string) $instance->block_sync_url;
                $authority = FollowersSyncService::authority($url);

                if (
                    ! $authority
                    || strtolower((string) parse_url($authority, PHP_URL_HOST)) !== $domain
                    || in_array($domain, $banned, true)
                    || DeliveryHostService::isUnavailable($domain)
                ) {
                    continue;
                }

                BlockSyncPipeline::dispatch($authority, $url, true)
                    ->onQueue('low')
                    ->delay(now()->addSeconds($spread > 0 ? random_int(0, $spread) : 0));

                $dispatched++;
            }

            return true;
        };

        $peers()->where('id', '>', $cursor)->chunkById(500, $process);

        if ($dispatched < $limit && $cursor > 0) {
            $peers()->where('id', '<=', $cursor)->chunkById(500, $process);
        }

        Cache::forever(self::CURSOR_KEY, $dispatched >= $limit ? $lastId : 0);

        $this->info("Queued {$dispatched} block synchronizations.");

        return self::SUCCESS;
    }
}
