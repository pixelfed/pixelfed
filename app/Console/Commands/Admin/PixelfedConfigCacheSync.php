<?php

namespace App\Console\Commands\Admin;

use App\Services\ConfigCacheService;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

class PixelfedConfigCacheSync extends Command
{
    protected $signature = 'admin:pixelfed-config-cache-sync
        {--no-sync : Skip the sync}
        {--force : Update even when the hash is unchanged (cleans up stale/orphaned rows on demand)}';

    protected $description = 'Sync updated .env variables into DB backed config_cache table and redis cache';

    public function handle(): int
    {
        if (! ConfigCacheService::syncEnabled() || $this->option('no-sync')) {
            return 0;
        }

        try {
            ConfigCacheService::sync((bool) $this->option('force'));
        } catch (QueryException $e) {
            Log::info('config-cache sync skipped: '.$e->getMessage());
        }

        // Always exits 0: the sync is best-effort and must never fail a deploy.
        return 0;
    }
}
