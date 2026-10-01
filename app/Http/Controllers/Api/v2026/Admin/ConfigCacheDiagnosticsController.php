<?php

namespace App\Http\Controllers\Api\v2026\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConfigCache as ConfigCacheModel;
use App\Services\ConfigCacheService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

class ConfigCacheDiagnosticsController extends Controller
{
    // Thin shell; the page fetches its data from the diagnostics JSON API.
    public function debugPage(Request $request): View
    {
        return view('admin.config-cache.home');
    }

    // JSON debug data: per-key rows + sync health for the admin page.
    public function debug(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request, 'admin:read');

        $rows = collect(ConfigCacheService::adminVisibleKeys())
            ->map(fn ($key) => $this->debugRow($key))
            ->values()
            ->all();

        return response()->json([
            'rows' => $rows,
            'sync' => $this->syncHealth(),
        ]);
    }

    // Force a full reconcile + cache flush so the server matches .env/config.
    public function clearCacheApi(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request, 'admin:write');

        Artisan::call('admin:pixelfed-config-cache-sync', ['--force' => true]);

        return response()->json(['message' => 'Config cache reconciled and cleared.']);
    }

    // Session-authed first-party admins get a Passport TransientToken (GrantFirstPartyToken) whose can() is always true, so this same check works for both bearer-token and cookie callers.
    protected function authorizeAdmin(Request $request, string $ability): void
    {
        abort_if(! $request->user() || ! $request->user()->token(), 404);
        abort_unless($request->user()->is_admin == 1, 404);
        abort_unless($request->user()->tokenCan($ability), 404);
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

        $source = ConfigCacheService::sourceOf($key);

        $match = $source === 'db'
            ? $this->looseEquals($effective, $dbPlain)
            : $this->looseEquals($effective, $configVal);

        // For a protected row, mask the decrypted value when usable, else the raw ciphertext.
        $dbRaw = $protected ? (($dbPlain !== null && is_scalar($dbPlain)) ? $dbPlain : $rawDb) : $rawDb;

        return [
            'key' => $key,
            'env' => ConfigCacheService::envVarFor($key),
            'list' => ConfigCacheService::listOf($key),
            'source' => $source,
            'locked' => ConfigCacheService::isLocked($key),
            'protected' => $protected,
            'effective' => $this->displayValue($key, $effective, $protected),
            'db' => $rawDb === null ? null : $this->displayValue($key, $dbRaw, $protected),
            'config' => $this->displayValue($key, $configVal, $protected),
            'match' => $match,
        ];
    }

    // Display string for one value: masked when protected, else rendered by type.
    protected function displayValue(string $key, $raw, bool $protected): ?string
    {
        if ($protected) {
            return ConfigCacheController::maskProtectedConfig(is_scalar($raw) ? (string) $raw : null);
        }

        if ($raw === null) {
            return null;
        }

        if (is_bool($raw)) {
            return $raw ? 'true' : 'false';
        }

        if (is_scalar($raw)) {
            return (string) $raw;
        }

        return json_encode($raw);
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

    // Canonicalize boolean-ish values to "1"/"0" (PHP casts false to "", not "0").
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
