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
 *
 * The .env write is best-effort: containerized/managed deployments often have
 * no writable .env file (env is injected externally), so a missing or
 * read-only file must not abort the migration. The runtime config + DB-backed
 * config cache are the load-bearing updates; .env persistence is only a
 * "survives a restart" convenience.
 */
trait ManagesMediaStorageEnv
{
    /**
     * Read the raw current value of an .env key (unquoted), or null.
     */
    protected function readEnvValue(string $key): ?string
    {
        $payload = $this->readEnvFile();
        if ($payload === null) {
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
        // 1. Persist to .env atomically (survives restarts). Best-effort:
        //    skips gracefully when there is no writable .env (e.g. containers
        //    that inject config via real environment variables).
        if (! $this->updateEnvFile($envKey, $envValue)) {
            $this->warn('Could not persist '.$envKey.' to .env (missing or read-only). '.'Applied to the live runtime and config cache only; set '.$envKey.'='.$envValue.' in your environment to make it survive a restart.');
        }

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

    /**
     * Read the .env file contents, or null when it is absent/unreadable.
     */
    protected function readEnvFile(): ?string
    {
        $envPath = app()->environmentFilePath();
        if (! is_file($envPath) || ! is_readable($envPath)) {
            return null;
        }
        $payload = file_get_contents($envPath);

        return $payload === false ? null : $payload;
    }

    /**
     * Persist a key/value to .env. Returns true on success, false when there
     * is no writable .env file to update (caller decides how to warn).
     */
    protected function updateEnvFile($key, $value): bool
    {
        $payload = $this->readEnvFile();
        if ($payload === null) {
            return false;
        }

        $value = str_replace(['\\', '"', "\n", "\r"], ['\\\\', '\\"', '\\n', '\\r'], $value);

        if (($existing = $this->existingEnv($key, $payload)) !== false) {
            $payload = str_replace("{$key}={$existing}", "{$key}=\"{$value}\"", $payload);
        } else {
            $payload = $payload."\n{$key}=\"{$value}\"\n";
        }

        return $this->storeEnv($payload);
    }

    protected function existingEnv($needle, $haystack)
    {
        preg_match("/^{$needle}=[^\r\n]*/m", $haystack, $matches);
        if ($matches && count($matches)) {
            return substr($matches[0], strlen($needle) + 1);
        }

        return false;
    }

    /**
     * Atomically write the .env payload. Returns true on success, false when
     * the file/directory is not writable (rather than throwing), so a hot
     * migration can continue with runtime + config-cache updates only.
     */
    protected function storeEnv($payload): bool
    {
        $envPath = app()->environmentFilePath();

        if (! is_writable(dirname($envPath)) || (is_file($envPath) && ! is_writable($envPath))) {
            return false;
        }

        $tempPath = $envPath.'.tmp';

        $file = @fopen($tempPath, 'w');
        if ($file === false) {
            return false;
        }
        fwrite($file, $payload);
        fclose($file);

        if (! @rename($tempPath, $envPath)) {
            @unlink($tempPath);

            return false;
        }

        return true;
    }
}
