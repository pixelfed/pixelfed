<?php

namespace App\Http\Controllers\Api\v2026\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConfigCache as ConfigCacheModel;
use App\Services\ConfigCacheService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ConfigCacheController extends Controller
{
    // GET config — bulk read; requires an explicit ?keys[] filter. Unknown keys
    // reject with 422. There is no fetch-everything default.
    public function showBulk(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request, 'admin:read');

        $requested = $request->input('keys');

        if (! is_array($requested) || empty($requested)) {
            return response()->json([
                'message' => 'The keys parameter is required and must be a non-empty array.',
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

        return response()->json([
            'data' => array_map(fn ($key) => $this->itemMetadata($key), $keys),
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

        return response()->json(['data' => $this->itemMetadata($key)]);
    }

    // POST config/{key} — single write from the `value` field.
    public function update(Request $request, $key): JsonResponse
    {
        $this->authorizeAdmin($request, 'admin:write');

        $errors = [];
        $permitted = $this->validateSubmitted([$key => $request->input('value')], $errors);

        if (! empty($errors)) {
            return response()->json([
                'message' => 'The submitted configuration is invalid.',
                'errors' => $errors,
            ], 422);
        }

        return response()->json([
            'changed' => array_values($this->saveToDB($permitted)),
            'data' => $this->itemMetadata($key),
        ]);
    }

    // POST config — bulk write, partial success. Payload: { config: { key: value } }.
    // Valid keys are persisted even when others fail; per-key failures are
    // reported in `errors`. Returns 422 only when the payload is malformed or
    // no submitted key was writable.
    public function updateBulk(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request, 'admin:write');

        $config = $request->input('config');

        if (! is_array($config) || empty($config)) {
            return response()->json([
                'message' => 'The config parameter must be a non-empty object of key/value pairs.',
            ], 422);
        }

        $errors = [];
        $permitted = $this->validateSubmitted($config, $errors);

        if (empty($permitted) && ! empty($errors)) {
            return response()->json([
                'message' => 'The submitted configuration is invalid.',
                'errors' => $errors,
            ], 422);
        }

        return response()->json([
            'changed' => array_values($this->saveToDB($permitted)),
            'errors' => empty($errors) ? (object) [] : $errors,
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
    protected function validateSubmitted(array $submitted, array &$errors): array
    {
        $validated = [];

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

            // Resubmitting the exact masked placeholder means "no change" — skip.
            if (ConfigCacheService::isProtected($key) && $this->isMaskedPlaceholder($key, $value)) {
                continue;
            }

            // An empty value is a reset request: it clears the stored row so the
            // key falls back to its config/env default. Skip rule validation.
            if ($value === null || $value === '') {
                $validated[$key] = null;

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

            $validated[$key] = $value;
        }

        return $validated;
    }

    // Persist pairs; return only the keys whose effective value changed. A null
    // value is a reset — the stored row is cleared so the key reverts to its
    // config/env default.
    protected function saveToDB(array $validated): array
    {
        $changed = [];

        foreach ($validated as $key => $value) {
            $before = ConfigCacheService::get($key);

            if ($value === null) {
                ConfigCacheService::forget($key);
            } else {
                ConfigCacheService::put($key, $value);
            }

            $after = ConfigCacheService::get($key);

            if ($before !== $after) {
                $changed[$key] = $this->itemMetadata($key);
            }
        }

        return $changed;
    }

    protected function isMaskedPlaceholder(string $key, $value): bool
    {
        if (! is_string($value) || $value === '') {
            return false;
        }

        $current = ConfigCacheService::get($key);

        if ($current === null || $current === '') {
            return false;
        }

        return $value === self::maskProtectedConfig($current);
    }

    // Metadata for a key; protected values are masked.
    protected function itemMetadata(string $key): array
    {
        $protected = ConfigCacheService::isProtected($key);
        $value = ConfigCacheService::get($key);

        if ($protected) {
            $value = self::maskProtectedConfig($value);
        }

        return [
            'key' => $key,
            'value' => $value,
            'list' => ConfigCacheService::listOf($key),
            'locked' => ConfigCacheService::isLocked($key),
            'source' => $this->itemSource($key),
            'protected' => $protected,
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

    // Mask a secret: 4 chars visible each end, rest '*'; <8 chars fully masked.
    public static function maskProtectedConfig($value): ?string
    {
        if (empty($value)) {
            return $value === null ? null : (string) $value;
        }

        if (strlen((string) $value) < 8) {
            return str_repeat('*', strlen((string) $value));
        }

        return Str::mask((string) $value, '*', 4, -4);
    }
}
