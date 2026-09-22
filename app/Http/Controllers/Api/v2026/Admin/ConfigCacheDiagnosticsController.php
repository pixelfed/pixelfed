<?php

namespace App\Http\Controllers\Api\v2026\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConfigCache as ConfigCacheModel;
use App\Services\ConfigCacheService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

class ConfigCacheDiagnosticsController extends Controller
{
    // Read-only debug page showing effective/DB/config values per key.
    public function debugPage(Request $request): View
    {
        $rows = collect(ConfigCacheService::adminVisibleKeys())
            ->map(fn ($key) => $this->debugRow($key))
            ->values()
            ->all();

        return view('admin.config-cache.home', [
            'rows' => $rows,
            'sync' => $this->syncHealth(),
        ]);
    }

    // Force a full reconcile + cache flush so the server matches .env/config.
    public function clearCache(Request $request)
    {
        Artisan::call('admin:pixelfed-config-cache-sync', ['--force' => true]);

        return redirect()
            ->route('admin.config-cache')
            ->with('status', 'Config cache reconciled and cleared. The server now reflects the current .env and config.');
    }

    // A debug-page row; secrets are decrypted only to compute match, then masked.
    protected function debugRow(string $key): array
    {
        $protected = ConfigCacheService::isProtected($key);
        $effective = ConfigCacheService::get($key);
        $configVal = config($key);

        $rawDb = ConfigCacheModel::where('k', $key)->value('v');
        $dbPlain = $rawDb;
        if ($protected && $rawDb !== null && $rawDb !== '') {
            try {
                $dbPlain = decrypt($rawDb);
            } catch (\Throwable $e) {
                $dbPlain = null;
            }
        }

        $source = $this->itemSource($key);

        $match = $source === 'db'
            ? $this->looseEquals($effective, $dbPlain)
            : $this->looseEquals($effective, $configVal);

        if ($protected) {
            $effectiveDisplay = ConfigCacheController::maskProtectedConfig(is_scalar($effective) ? (string) $effective : null);
            $dbDisplay = $rawDb === null ? null : ConfigCacheController::maskProtectedConfig($dbPlain !== null && is_scalar($dbPlain) ? (string) $dbPlain : (string) $rawDb);
            $configDisplay = ConfigCacheController::maskProtectedConfig(is_scalar($configVal) ? (string) $configVal : null);
        } else {
            $effectiveDisplay = $this->returnType($effective);
            $dbDisplay = $rawDb === null ? null : $this->returnType($rawDb);
            $configDisplay = $this->returnType($configVal);
        }

        return [
            'key' => $key,
            'env' => ConfigCacheService::envVarFor($key),
            'list' => ConfigCacheService::listOf($key),
            'source' => $source,
            'locked' => ConfigCacheService::isLocked($key),
            'protected' => $protected,
            'effective' => $effectiveDisplay,
            'db' => $dbDisplay,
            'config' => $configDisplay,
            'match' => $match,
        ];
    }

    // 'env' (env wins), 'db' (row exists), or 'default' (config file value).
    protected function itemSource(string $key): string
    {
        if (ConfigCacheService::isLocked($key)) {
            return 'env';
        }

        if (ConfigCacheModel::where('k', $key)->exists()) {
            return 'db';
        }

        return 'default';
    }

    // Loose equality so a DB string ("5"/"0") matches a typed value (5/false).
    protected function looseEquals($a, $b): bool
    {
        if ($a === null || $b === null) {
            return $a === $b;
        }

        if (! is_scalar($a) || ! is_scalar($b)) {
            return $a === $b;
        }

        return $this->normalizeBoolean($a) === $this->normalizeBoolean($b);
    }

    // Canonicalize booleans and boolean-ish strings to "1"/"0" so false matches
    // a DB "0" (PHP casts false to "", not "0"). Others compare as strings.
    protected function normalizeBoolean($value): string
    {
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (in_array($value, [0, 1, '0', '1', 'true', 'false', true, false], true)) {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
        }

        return (string) $value;
    }

    // Render a non-secret value: booleans to string, arrays to JSON.
    protected function returnType($value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return json_encode($value);
    }

    // Sync-health panel: stored change-hash and best-effort lock state.
    protected function syncHealth(): array
    {
        $syncHash = Cache::get(ConfigCacheService::MARKER_KEY);

        $lockHeld = null;
        try {
            $lock = Cache::lock(ConfigCacheService::LOCK_KEY, 1);
            if ($lock->get()) {
                $lock->release();
                $lockHeld = false;
            } else {
                $lockHeld = true;
            }
        } catch (\Throwable $e) {
            $lockHeld = null;
        }

        return [
            'sync_hash' => $syncHash,
            'lock_held' => $lockHeld,
        ];
    }
}
