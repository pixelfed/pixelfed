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
        $v = Env::get('PIXELFED_CONFIG_CACHE_SYNC', true);

        return filter_var($v, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true;
    }

    public function handle(): int
    {
        if (! self::syncEnabled() || $this->option('no-sync')) {
            return 0;
        }

        $force = (bool) $this->option('force');

        try {
            $hash = $this->refreshHash();

            if (! $force && Cache::get(self::MARKER_KEY) === $hash) {
                Log::info('admin:pixelfed-config-cache-sync skipped: change-hash has not changed: '.$hash);

                return 0;
            }

            $lock = Cache::lock(self::LOCK_KEY, self::LOCK_TTL);

            if (! $lock->get()) {
                return 0;
            }

            try {
                // Re-check in case another process finished while we waited.
                if (! $force && Cache::get(self::MARKER_KEY) === $hash) {
                    return 0;
                }

                $this->reconcile();

                ConfigCacheService::flushAll();

                Cache::forever(self::MARKER_KEY, $hash);
            } finally {
                $lock->release();
            }
        } catch (QueryException $e) {
            Log::info('admin:pixelfed-config-cache-sync skipped: '.$e->getMessage());

            return 0;
        }

        return 0;
    }

    protected function reconcile(): void
    {
        foreach (ConfigCacheService::keysInList('ENVCONFIG') as $key) {
            $row = ConfigCache::where('k', $key)->first();
            $value = config($key);
            $empty = $value === null || $value === '';

            if (! ConfigCacheService::envIsPresentAndValidForKey($key)) {
                if ($row === null && ! $empty) {
                    ConfigCacheService::putRaw($key, $value);
                }

                continue;
            }

            if ($empty) {
                if ($row !== null) {
                    ConfigCacheService::forget($key);
                }
            } elseif ($row === null || $row->v != $value) {
                ConfigCacheService::putRaw($key, $value);
            }
        }
    }

    protected function refreshHash(): string
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
