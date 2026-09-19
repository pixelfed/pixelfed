<?php

namespace App\Console\Commands\Admin;

use App\Models\ConfigCache;
use App\Services\ConfigCacheService;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PixelfedConfigCacheSync extends Command
{
    protected $signature = 'admin:pixelfed-config-cache-sync
        {--no-sync : Skip the sync (mirrors PIXELFED_CONFIG_CACHE_SYNC=false)}
        {--force : Reconcile even when the change-hash is unchanged (cleans up stale/orphaned rows on demand)}';

    protected $description = 'Reconcile .env into config_cache for env-bound keys (hash-gated)';

    protected const MARKER_KEY = 'config-cache:sync-hash';

    protected const LOCK_KEY = 'config-cache:sync';

    protected const LOCK_TTL = 15;

    public static function syncEnabled(): bool
    {
        return (bool) Env::get('PIXELFED_CONFIG_CACHE_SYNC', true);
    }

    // Always exits 0: the sync is best-effort and must never fail a deploy.
    public function handle(): int
    {
        if (! self::syncEnabled() || $this->option('no-sync')) {
            return 0;
        }

        try {
            $this->sync((bool) $this->option('force'));
        } catch (QueryException $e) {
            // Database isn't ready yet, e.g. running before migrations.
            Log::info('config-cache sync skipped: '.$e->getMessage());
        }

        return 0;
    }

    protected function sync(bool $force): void
    {
        $hash = $this->configHash();

        if (! $force && Cache::get(self::MARKER_KEY) === $hash) {
            Log::info('config-cache sync skipped: hash unchanged '.$hash);

            return;
        }

        $lock = Cache::lock(self::LOCK_KEY, self::LOCK_TTL);

        if (! $lock->get()) {
            return;
        }

        try {
            $this->refreshEnv();
            ConfigCacheService::flushAll();
            Cache::forever(self::MARKER_KEY, $hash);
        } finally {
            $lock->release();
        }
    }

    protected function refreshEnv(): void
    {
        foreach (ConfigCacheService::keysInList('ENVCONFIG') as $key) {
            $row = ConfigCache::where('k', $key)->first();
            $value = config($key);
            $empty = $value === null || $value === '';

            if (! ConfigCacheService::isLocked($key)) {
                if ($row === null && ! $empty) {
                    ConfigCacheService::putRaw($key, $value);
                }

                continue;
            }

            if ($empty) {
                if ($row !== null) {
                    ConfigCacheService::forget($key);
                }
                // != on purpose: `v` is a text column, so a stored
                // "5" must count as equal to an int 5 due to loose typing.
            } elseif ($row === null || $row->v != $value) {
                ConfigCacheService::putRaw($key, $value);
            }
        }
    }

    protected function configHash(): string
    {
        $keys = ConfigCacheService::keysInList('ENVCONFIG');
        sort($keys);

        $payload = [];
        foreach ($keys as $k) {
            $payload[$k] = config($k);
        }

        return hash('sha256', json_encode($payload));
    }
}
