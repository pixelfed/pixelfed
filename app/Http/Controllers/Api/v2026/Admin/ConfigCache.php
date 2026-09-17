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
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ConfigCache extends Controller
{
    // GET config — bulk read; ?keys[] filters, unknown keys reject with 422.
    public function index(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request, 'admin:read');

        $requested = $request->input('keys');

        if ($requested !== null) {
            if (! is_array($requested)) {
                return response()->json([
                    'message' => 'The keys parameter must be an array.',
                ], 422);
            }

            $unknown = array_values(array_filter(
                $requested,
                fn ($key) => ! is_string($key) || ! ConfigCacheService::isCached($key)
            ));

            if (! empty($unknown)) {
                return response()->json([
                    'message' => 'One or more requested keys are unknown or uncached.',
                    'unknown_keys' => $unknown,
                ], 422);
            }

            $keys = array_values(array_unique($requested));
        } else {
            $keys = ConfigCacheService::adminVisibleKeys();
        }

        return response()->json([
            'data' => array_map(fn ($key) => $this->itemFor($key), $keys),
        ]);
    }

    // GET config/{key} — single read; unknown key is 404.
    public function show(Request $request, $key): JsonResponse
    {
        $this->authorizeAdmin($request, 'admin:read');

        if (! ConfigCacheService::isCached($key)) {
            return response()->json([
                'message' => 'Unknown or uncached config key.',
                'key' => $key,
            ], 404);
        }

        return response()->json(['data' => $this->itemFor($key)]);
    }

    // POST config/{key} — single write from the `value` field.
    public function update(Request $request, $key): JsonResponse
    {
        $this->authorizeAdmin($request, 'admin:write');

        $errors = [];
        $permitted = $this->collectWritable([$key => $request->input('value')], $errors);

        if (! empty($errors)) {
            return response()->json([
                'message' => 'The submitted configuration is invalid.',
                'errors' => $errors,
            ], 422);
        }

        return response()->json([
            'changed' => array_values($this->persist($permitted)),
            'data' => $this->itemFor($key),
        ]);
    }

    // POST config — bulk write, all-or-nothing. Payload: { config: { key: value } }.
    public function store(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request, 'admin:write');

        $config = $request->input('config');

        if (! is_array($config) || empty($config)) {
            return response()->json([
                'message' => 'The config parameter must be a non-empty object of key/value pairs.',
            ], 422);
        }

        $errors = [];
        $permitted = $this->collectWritable($config, $errors);

        if (! empty($errors)) {
            return response()->json([
                'message' => 'The submitted configuration is invalid.',
                'errors' => $errors,
            ], 422);
        }

        return response()->json([
            'changed' => array_values($this->persist($permitted)),
        ]);
    }

    protected function authorizeAdmin(Request $request, string $ability): void
    {
        abort_if(! $request->user() || ! $request->user()->token(), 404);
        abort_unless($request->user()->is_admin == 1, 404);
        abort_unless($request->user()->tokenCan($ability), 404);
    }

    // Validate submitted key/values, collecting per-key errors. Unknown, locked,
    // or rule-failing keys error; masked/empty secrets are skipped. Returns the
    // writable pairs; the caller persists only when $errors is empty.
    protected function collectWritable(array $submitted, array &$errors): array
    {
        $permitted = [];

        foreach ($submitted as $key => $value) {
            $key = (string) $key;

            if (! ConfigCacheService::isCached($key)) {
                $errors[$key][] = 'Unknown or uncached config key.';

                continue;
            }

            if (ConfigCacheService::isLocked($key)) {
                $envVar = ConfigCacheService::envVarFor($key);
                $errors[$key][] = "This key is set by the {$envVar} environment variable. ".
                    'Change it in your .env file and restart to update this value.';

                continue;
            }

            if (ConfigCacheService::isProtected($key) && $this->isMaskedOrEmpty($value)) {
                continue;
            }

            $rule = ConfigCacheService::ruleFor($key);
            if ($rule !== null) {
                $validator = Validator::make(['value' => $value], ['value' => $rule]);

                if ($validator->fails()) {
                    foreach ($validator->errors()->get('value') as $message) {
                        $errors[$key][] = $message;
                    }

                    continue;
                }
            }

            $permitted[$key] = $value;
        }

        return $permitted;
    }

    // Persist pairs; return only the keys whose effective value changed.
    protected function persist(array $permitted): array
    {
        $changed = [];

        foreach ($permitted as $key => $value) {
            $before = ConfigCacheService::get($key);
            ConfigCacheService::put($key, $value);
            $after = ConfigCacheService::get($key);

            if ($before !== $after) {
                $changed[$key] = $this->itemFor($key);
            }
        }

        return $changed;
    }

    // A masked (contains '*') or empty value is not a real new secret.
    protected function isMaskedOrEmpty($value): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        return str_contains((string) $value, '*');
    }

    // Metadata for a key; protected values are masked.
    protected function itemFor(string $key): array
    {
        $protected = ConfigCacheService::isProtected($key);
        $value = ConfigCacheService::get($key);

        if ($protected) {
            $value = self::maskSecret($value);
        }

        return [
            'key' => $key,
            'value' => $value,
            'list' => ConfigCacheService::listOf($key),
            'locked' => ConfigCacheService::isLocked($key),
            'source' => $this->sourceFor($key),
            'protected' => $protected,
        ];
    }

    // 'env' (env wins), 'db' (row exists), or 'default' (config file value).
    protected function sourceFor(string $key): string
    {
        if (ConfigCacheService::envIsPresentAndValidForKey($key)) {
            return 'env';
        }

        if (ConfigCacheModel::where('k', $key)->exists()) {
            return 'db';
        }

        return 'default';
    }

    // Mask a secret: 4 chars visible each end, rest '*'; <8 chars fully masked.
    public static function maskSecret($value): ?string
    {
        if (empty($value)) {
            return $value === null ? null : (string) $value;
        }

        if (strlen((string) $value) < 8) {
            return str_repeat('*', strlen((string) $value));
        }

        return Str::mask((string) $value, '*', 4, -4);
    }

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

        $source = $this->sourceFor($key);

        $match = $source === 'db'
            ? $this->looseEquals($effective, $dbPlain)
            : $this->looseEquals($effective, $configVal);

        if ($protected) {
            $effectiveDisplay = self::maskSecret(is_scalar($effective) ? (string) $effective : null);
            $dbDisplay = $rawDb === null ? null : self::maskSecret($dbPlain !== null && is_scalar($dbPlain) ? (string) $dbPlain : (string) $rawDb);
            $configDisplay = self::maskSecret(is_scalar($configVal) ? (string) $configVal : null);
        } else {
            $effectiveDisplay = $this->scalarize($effective);
            $dbDisplay = $rawDb === null ? null : $this->scalarize($rawDb);
            $configDisplay = $this->scalarize($configVal);
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

    // Loose equality so a DB string ("5"/"0") matches a typed value (5/false).
    protected function looseEquals($a, $b): bool
    {
        if ($a === null || $b === null) {
            return $a === $b;
        }

        if (! is_scalar($a) || ! is_scalar($b)) {
            return $a === $b;
        }

        return $this->normalizeScalar($a) === $this->normalizeScalar($b);
    }

    // Canonicalize booleans and boolean-ish strings to "1"/"0" so false matches
    // a DB "0" (PHP casts false to "", not "0"). Others compare as strings.
    protected function normalizeScalar($value): string
    {
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (in_array($value, [0, 1, '0', '1', 'true', 'false', true, false], true)) {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
        }

        return (string) $value;
    }

    // Render a non-secret value: scalars to string, arrays to JSON.
    protected function scalarize($value): ?string
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
        $syncHash = Cache::get('config-cache:sync-hash');

        $lockHeld = null;
        try {
            $lock = Cache::lock('config-cache:sync', 1);
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
