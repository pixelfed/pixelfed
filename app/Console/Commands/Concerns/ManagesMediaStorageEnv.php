<?php

namespace App\Console\Commands\Concerns;

use App\Services\ConfigCacheService;
use Illuminate\Support\Facades\Storage;

/**
 * Shared helpers for the media storage migration commands.
 *
 * Handles reading/writing the .env storage flags atomically (reusing the
 * installer's vetted approach) and applying the change to the live runtime
 * so that new uploads route to the correct backend during a migration on a
 * hot (running) server, without requiring a restart.
 */
trait ManagesMediaStorageEnv
{
    /**
     * Read the raw current value of an .env key (unquoted), or null.
     */
    protected function readEnvValue(string $key): ?string
    {
        $envPath = app()->environmentFilePath();
        if (! is_file($envPath)) {
            return null;
        }
        $payload = file_get_contents($envPath);
        if ($payload === false) {
            return null;
        }
        if (! preg_match("/^{$key}=([^\r\n]*)/m", $payload, $m)) {
            return null;
        }

        return trim($m[1], " \t\"'");
    }

    /**
     * Set an .env key + the live runtime config + config-cache entry so the
     * change takes effect immediately on a running server.
     *
     * @param  string  $configKey  dotted config key kept in sync (e.g. 'pixelfed.cloud_storage')
     * @param  mixed  $configValue  the typed runtime value (e.g. true/false)
     */
    protected function setStorageEnv(string $envKey, string $envValue, string $configKey, $configValue): void
    {
        // 1. Persist to .env atomically (survives restarts).
        $this->updateEnvFile($envKey, $envValue);

        // 2. Update the live runtime config for the current process.
        config([$configKey => $configValue]);

        // 3. Update the DB-backed config cache so other workers/requests
        //    reading via config_cache() see the new value (hot server).
        try {
            ConfigCacheService::put($configKey, $configValue);
        } catch (\Throwable $e) {
            $this->warn('Could not update config cache for '.$configKey.': '.$e->getMessage());
        }
    }

    /**
     * The configured cloud disk host (used to sanity check cloud config).
     */
    protected function cloudHost(): ?string
    {
        try {
            $url = Storage::disk(config('filesystems.cloud'))->url('probe');
            $host = parse_url($url, PHP_URL_HOST);

            return $host ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    // ---- Atomic .env writer (adapted from Installer) ---------------------

    protected function updateEnvFile($key, $value): void
    {
        $envPath = app()->environmentFilePath();
        $payload = file_get_contents($envPath);

        $value = str_replace(['\\', '"', "\n", "\r"], ['\\\\', '\\"', '\\n', '\\r'], $value);

        if (($existing = $this->existingEnv($key, $payload)) !== false) {
            $payload = str_replace("{$key}={$existing}", "{$key}=\"{$value}\"", $payload);
        } else {
            $payload = $payload."\n{$key}=\"{$value}\"\n";
        }

        $this->storeEnv($payload);
    }

    protected function existingEnv($needle, $haystack): string|false
    {
        preg_match("/^{$needle}=[^\r\n]*/m", $haystack, $matches);
        if ($matches && count($matches)) {
            return substr($matches[0], strlen($needle) + 1);
        }

        return false;
    }

    protected function storeEnv($payload): void
    {
        $envPath = app()->environmentFilePath();
        $tempPath = $envPath.'.tmp';

        $file = fopen($tempPath, 'w');
        if ($file === false) {
            throw new \RuntimeException("Cannot write to {$tempPath}");
        }
        fwrite($file, $payload);
        fclose($file);

        if (! rename($tempPath, $envPath)) {
            @unlink($tempPath);
            throw new \RuntimeException('Cannot update .env file');
        }
    }
}
