<?php

namespace App\Services;

use App\Models\ConfigCache as ConfigCacheModel;
use App\Services\Config\EnvConfigValidator;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ConfigCacheService
{
    const CACHE_KEY = 'config_cache:_v0-key:';

    // Last-reconciled change-hash of governed env values.
    const MARKER_KEY = 'config-cache:sync-hash';

    // Lock preventing concurrent reconciles.
    const LOCK_KEY = 'config-cache:sync';

    const LOCK_TTL = 15;

    // Secret keys: encrypted at rest, masked when read back.
    const PROTECTED_KEYS = [
        'filesystems.disks.s3.secret',
        'filesystems.disks.spaces.secret',
        'captcha.hcaptcha.secret',
        'captcha.turnstile.secret',
        'captcha.cap.secret',
    ];


    // An `env` field makes a key ENVCONFIG (env-governed); its absence makes it ADMINONLY.
    const KEYS = [
        // filesystems.php — s3 disk
        'filesystems.disks.s3.key' => ['env' => 'AWS_ACCESS_KEY_ID', 'rule' => 'string'],
        'filesystems.disks.s3.secret' => ['env' => 'AWS_SECRET_ACCESS_KEY', 'rule' => 'string'],
        'filesystems.disks.s3.region' => ['env' => 'AWS_DEFAULT_REGION', 'rule' => 'string'],
        'filesystems.disks.s3.bucket' => ['env' => 'AWS_BUCKET', 'rule' => 'string'],
        'filesystems.disks.s3.visibility' => ['env' => 'AWS_VISIBILITY', 'rule' => 'in:public,private'],
        'filesystems.disks.s3.url' => ['env' => 'AWS_URL', 'rule' => 'url'],
        'filesystems.disks.s3.endpoint' => ['env' => 'AWS_ENDPOINT', 'rule' => 'url'],
        'filesystems.disks.s3.use_path_style_endpoint' => ['env' => 'AWS_USE_PATH_STYLE_ENDPOINT', 'rule' => 'boolean'],

        // filesystems.php — spaces disk
        'filesystems.disks.spaces.key' => ['env' => 'DO_SPACES_KEY', 'rule' => 'string'],
        'filesystems.disks.spaces.secret' => ['env' => 'DO_SPACES_SECRET', 'rule' => 'string'],
        'filesystems.disks.spaces.region' => ['env' => 'DO_SPACES_REGION', 'rule' => 'string'],
        'filesystems.disks.spaces.bucket' => ['env' => 'DO_SPACES_BUCKET', 'rule' => 'string'],
        'filesystems.disks.spaces.url' => ['env' => 'DO_SPACES_URL', 'rule' => 'url'],
        'filesystems.disks.spaces.endpoint' => ['env' => 'DO_SPACES_ENDPOINT', 'rule' => 'url'],
        'filesystems.disks.spaces.visibility' => ['env' => 'DO_SPACES_VISIBILITY', 'rule' => 'in:public,private'],
        'filesystems.disks.spaces.use_path_style_endpoint' => ['env' => 'DO_SPACES_USE_PATH_STYLE_ENDPOINT', 'rule' => 'boolean'],

        // app.php
        'app.name' => ['env' => 'APP_NAME', 'rule' => 'string'],
        'app.short_description' => ['env' => 'PF_SHORT_DESCRIPTION', 'rule' => 'string'],
        'app.description' => ['env' => 'PF_DESCRIPTION', 'rule' => 'string'],
        'app.rules' => ['env' => 'PF_RULES', 'rule' => 'json'],

        // pixelfed.php
        'pixelfed.max_photo_size' => ['env' => 'MAX_PHOTO_SIZE', 'rule' => 'integer|min:100'],
        'pixelfed.max_album_length' => ['env' => 'MAX_ALBUM_LENGTH', 'rule' => 'integer|min:1|max:20'],
        'pixelfed.image_quality' => ['env' => 'IMAGE_QUALITY', 'rule' => 'integer|min:1|max:100'],
        'pixelfed.media_types' => ['env' => 'MEDIA_TYPES', 'rule' => 'string'],
        'pixelfed.open_registration' => ['env' => 'OPEN_REGISTRATION', 'rule' => 'boolean'],
        'pixelfed.oauth_enabled' => ['env' => 'OAUTH_ENABLED', 'rule' => 'boolean'],
        'pixelfed.import.instagram.enabled' => ['env' => 'IMPORT_INSTAGRAM', 'rule' => 'boolean'],
        'pixelfed.bouncer.enabled' => ['env' => 'PF_BOUNCER_ENABLED', 'rule' => 'boolean'],
        'pixelfed.enforce_email_verification' => ['env' => 'ENFORCE_EMAIL_VERIFICATION', 'rule' => 'boolean'],
        'pixelfed.max_account_size' => ['env' => 'MAX_ACCOUNT_SIZE', 'rule' => 'integer|min:50000'],
        'pixelfed.enforce_account_limit' => ['env' => 'LIMIT_ACCOUNT_SIZE', 'rule' => 'boolean'],
        'pixelfed.cloud_storage' => ['env' => 'PF_ENABLE_CLOUD', 'rule' => 'boolean'],
        'pixelfed.max_caption_length' => ['env' => 'MAX_CAPTION_LENGTH', 'rule' => 'integer'],
        'pixelfed.max_bio_length' => ['env' => 'MAX_BIO_LENGTH', 'rule' => 'integer'],
        'pixelfed.max_name_length' => ['env' => 'MAX_NAME_LENGTH', 'rule' => 'integer'],
        'pixelfed.min_password_length' => ['env' => 'MIN_PASSWORD_LENGTH', 'rule' => 'integer|min:6'],
        'pixelfed.max_avatar_size' => ['env' => 'MAX_AVATAR_SIZE', 'rule' => 'integer'],
        'pixelfed.max_altext_length' => ['env' => 'PF_MEDIA_MAX_ALTTEXT_LENGTH', 'rule' => 'integer'],
        'pixelfed.allow_app_registration' => ['env' => 'PF_ALLOW_APP_REGISTRATION', 'rule' => 'boolean'],
        'pixelfed.app_registration_rate_limit_attempts' => ['env' => 'PF_IAR_RL_ATTEMPTS', 'rule' => 'integer'],
        'pixelfed.app_registration_rate_limit_decay' => ['env' => 'PF_IAR_RL_DECAY', 'rule' => 'integer'],
        'pixelfed.app_registration_confirm_rate_limit_attempts' => ['env' => 'PF_IARC_RL_ATTEMPTS', 'rule' => 'integer'],
        'pixelfed.app_registration_confirm_rate_limit_decay' => ['env' => 'PF_IARC_RL_DECAY', 'rule' => 'integer'],
        'pixelfed.optimize_image' => ['env' => 'PF_OPTIMIZE_IMAGES', 'rule' => 'boolean'],
        'pixelfed.optimize_video' => ['env' => 'PF_OPTIMIZE_VIDEOS', 'rule' => 'boolean'],
        'pixelfed.max_collection_length' => ['env' => 'PF_MAX_COLLECTION_LENGTH', 'rule' => 'integer'],

        // federation.php
        'federation.activitypub.enabled' => ['env' => 'ACTIVITY_PUB', 'rule' => 'boolean'],
        'federation.activitypub.authorized_fetch' => ['env' => 'AUTHORIZED_FETCH', 'rule' => 'boolean'],
        'federation.migration' => ['env' => 'PF_ACCT_MIGRATION_ENABLED', 'rule' => 'boolean'],
        'federation.custom_emoji.enabled' => ['env' => 'CUSTOM_EMOJI', 'rule' => 'boolean'],

        // instance.php
        'instance.stories.enabled' => ['env' => 'STORIES_ENABLED', 'rule' => 'boolean'],
        'instance.avatar.local_to_cloud' => ['env' => 'PF_LOCAL_AVATAR_TO_CLOUD', 'rule' => 'boolean'],
        'instance.has_legal_notice' => ['env' => 'INSTANCE_LEGAL_NOTICE', 'rule' => 'boolean'],
        'instance.landing.show_directory' => ['env' => 'INSTANCE_LANDING_SHOW_DIRECTORY', 'rule' => 'boolean'],
        'instance.landing.show_explore' => ['env' => 'INSTANCE_LANDING_SHOW_EXPLORE', 'rule' => 'boolean'],
        'instance.curated_registration.enabled' => ['env' => 'INSTANCE_CUR_REG', 'rule' => 'boolean'],
        'instance.embed.profile' => ['env' => 'INSTANCE_PROFILE_EMBEDS', 'rule' => 'boolean'],
        'instance.embed.post' => ['env' => 'INSTANCE_POST_EMBEDS', 'rule' => 'boolean'],
        'instance.user_filters.max_user_blocks' => ['env' => 'PF_MAX_USER_BLOCKS', 'rule' => 'integer|min:0|max:5000'],
        'instance.user_filters.max_user_mutes' => ['env' => 'PF_MAX_USER_MUTES', 'rule' => 'integer|min:0|max:5000'],
        'instance.user_filters.max_domain_blocks' => ['env' => 'PF_MAX_DOMAIN_BLOCKS', 'rule' => 'integer|min:0|max:5000'],
        'instance.admin.pid' => ['env' => 'PF_ADMIN_PID', 'rule' => 'integer'],
        'instance.banner.blurhash' => ['env' => 'INSTANCE_BANNER_BLURHASH', 'rule' => 'string'],

        // media.php
        'media.delete_local_after_cloud' => ['env' => 'MEDIA_DELETE_LOCAL_AFTER_CLOUD', 'rule' => 'boolean'],

        // captcha.php
        'captcha.enabled' => ['env' => 'CAPTCHA_ENABLED', 'rule' => 'boolean'],
        'captcha.driver' => ['env' => 'CAPTCHA_DRIVER', 'rule' => 'in:hcaptcha,turnstile,cap'],
        'captcha.hcaptcha.secret' => ['env' => 'CAPTCHA_H_SECRET', 'rule' => 'string'],
        'captcha.hcaptcha.sitekey' => ['env' => 'CAPTCHA_H_SITEKEY', 'rule' => 'string'],
        'captcha.turnstile.secret' => ['env' => 'CAPTCHA_TURNSTILE_SECRET', 'rule' => 'string'],
        'captcha.turnstile.sitekey' => ['env' => 'CAPTCHA_TURNSTILE_SITEKEY', 'rule' => 'string'],
        'captcha.cap.endpoint' => ['env' => 'CAPTCHA_CAP_ENDPOINT', 'rule' => 'url'],
        'captcha.cap.sitekey' => ['env' => 'CAPTCHA_CAP_SITEKEY', 'rule' => 'string'],
        'captcha.cap.secret' => ['env' => 'CAPTCHA_CAP_SECRET', 'rule' => 'string'],
        'captcha.active.login' => ['env' => 'CAPTCHA_ENABLED_ON_LOGIN', 'rule' => 'boolean'],
        'captcha.active.register' => ['env' => 'CAPTCHA_ENABLED_ON_REGISTER', 'rule' => 'boolean'],
        'captcha.active.forgot_password' => ['env' => 'CAPTCHA_ENABLED_ON_FORGOT_PASSWORD', 'rule' => 'boolean'],
        'captcha.active.password_reset' => ['env' => 'CAPTCHA_ENABLED_ON_PASSWORD_RESET', 'rule' => 'boolean'],
        'captcha.active.forgot_email' => ['env' => 'CAPTCHA_ENABLED_ON_FORGOT_EMAIL', 'rule' => 'boolean'],
        'captcha.active.curated_register' => ['env' => 'CAPTCHA_ENABLED_ON_CURATED_REGISTER', 'rule' => 'boolean'],

        // ADMINONLY (no env var)
        'app.banner_image' => ['rule' => 'string'],
        'about.title' => ['rule' => 'string'],
        'uikit.custom.css' => ['rule' => 'string'],
        'uikit.custom.js' => ['rule' => 'string'],
        'uikit.show_custom.css' => ['rule' => 'boolean'],
        'uikit.show_custom.js' => ['rule' => 'boolean'],
        'account.autofollow' => ['rule' => 'boolean'],
        'account.autofollow_usernames' => ['rule' => 'string'],
        'config.discover.features' => ['rule' => 'json'],
        'pixelfed.directory' => ['rule' => 'boolean'],
        'pixelfed.directory.submission-key' => ['rule' => 'string'],
        'pixelfed.directory.submission-ts' => ['rule' => 'string'],
        'pixelfed.directory.has_submitted' => ['rule' => 'boolean'],
        'pixelfed.directory.latest_response' => ['rule' => 'string'],
        'pixelfed.directory.is_synced' => ['rule' => 'boolean'],
        'pixelfed.directory.testimonials' => ['rule' => 'json'],
        'instance.stats.total_local_posts' => ['rule' => 'integer'],
        'autospam.nlp.enabled' => ['rule' => 'boolean'],
    ];

    public static function isCached(string $key): bool
    {
        return isset(self::KEYS[$key]);
    }

    // Derived from env binding: env var => ENVCONFIG, none => ADMINONLY.
    public static function listOf(string $key): ?string
    {
        if (! isset(self::KEYS[$key])) {
            return null;
        }

        return self::envVarFor($key) !== null ? 'ENVCONFIG' : 'ADMINONLY';
    }

    public static function envVarFor(string $key): ?string
    {
        return self::KEYS[$key]['env'] ?? null;
    }

    public static function isProtected(string $key): bool
    {
        return in_array($key, self::PROTECTED_KEYS, true);
    }

    public static function ruleFor(string $key): ?string
    {
        return self::KEYS[$key]['rule'] ?? null;
    }
    public static function keysInList(string $list): array
    {
        return array_values(array_filter(
            array_keys(self::KEYS),
            fn ($key) => self::listOf($key) === $list
        ));
    }

    public static function adminVisibleKeys(): array
    {
        return array_keys(self::KEYS);
    }

    // Env present and valid: env wins the read and admin edits are blocked.
    public static function isLocked(string $key): bool
    {
        $envVar = self::envVarFor($key);

        if ($envVar === null || ! self::envIsSet($envVar)) {
            return false;
        }

        return EnvConfigValidator::isValidEnvValue($key);
    }

    // An empty string counts as unset.
    public static function envIsSet(string $envVar): bool
    {
        $v = Env::get($envVar);

        return $v !== null && $v !== '';
    }

    public static function get($key)
    {
        if (! self::isCached($key) || self::isLocked($key)) {
            return config($key);
        }

        return self::readFromDb($key);
    }

    protected static function readFromDb($key)
    {
        try {
            return Cache::remember(self::CACHE_KEY.$key, now()->addHours(12), function () use ($key) {
                $protect = self::isProtected($key);
                $v = config($key);
                $row = ConfigCacheModel::where('k', $key)->first();

                if ($row) {
                    $stored = $protect ? decrypt($row->v) : $row->v;

                    return $stored !== null ? self::castFromDb($key, $stored) : config($key);
                }

                // Don't seed a row for an empty/null value.
                if ($v === null || $v === '') {
                    return $v;
                }

                self::putRaw($key, $v);

                return self::castFromDb($key, $v);
            });
        } catch (Exception|QueryException) {
            return config($key);
        }
    }

    // Write guard: unlisted or env-locked keys are not writable.
    public static function put($key, $val)
    {
        if (! self::isCached($key)) {
            return config($key);
        }

        if (self::isLocked($key)) {
            return self::get($key);
        }

        return self::putRaw($key, $val);
    }

    // Writes without the lock check (used by sync); app code calls put().
    public static function putRaw($key, $val)
    {
        $row = ConfigCacheModel::firstOrNew(['k' => $key]);
        $row->v = self::isProtected($key) ? encrypt($val) : $val;
        $row->save();

        Cache::forget(self::CACHE_KEY.$key);

        return self::get($key);
    }

    // The `v` column is text; cast back to the declared type.
    protected static function castFromDb($key, $value)
    {
        $type = explode('|', (string) self::ruleFor($key))[0];

        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'json' => is_string($value) ? (json_decode($value, true) ?? $value) : $value,
            default => $value,
        };
    }

    public static function forget($key): void
    {
        ConfigCacheModel::whereK($key)->delete();
        Cache::forget(self::CACHE_KEY.$key);
    }

    // Drops every key's cache entry (rows untouched); returns the count.
    public static function flushAll(): int
    {
        $keys = self::adminVisibleKeys();

        foreach ($keys as $key) {
            Cache::forget(self::CACHE_KEY.$key);
        }

        return count($keys);
    }

    public static function syncEnabled(): bool
    {
        return (bool) Env::get('PIXELFED_CONFIG_CACHE_SYNC', true);
    }

    // Reconcile .env into config_cache: hash-gated (unless $force) and single-flight.
    public static function sync(bool $force = false): void
    {
        $hash = self::configHash();

        if (! $force && Cache::get(self::MARKER_KEY) === $hash) {
            Log::info('config-cache sync skipped: hash unchanged '.$hash);

            return;
        }

        $lock = Cache::lock(self::LOCK_KEY, self::LOCK_TTL);

        if (! $lock->get()) {
            return;
        }

        try {
            self::refreshEnv();
            self::flushAll();
            Cache::forever(self::MARKER_KEY, $hash);
        } finally {
            $lock->release();
        }
    }

    // Reconcile each ENVCONFIG row: seed unlocked, prune empty locked, overwrite drift.
    public static function refreshEnv(): void
    {
        foreach (self::keysInList('ENVCONFIG') as $key) {
            $row = ConfigCacheModel::where('k', $key)->first();
            $value = config($key);
            $empty = $value === null || $value === '';

            if (! self::isLocked($key)) {
                if ($row === null && ! $empty) {
                    self::putRaw($key, $value);
                }

                continue;
            }

            if ($empty) {
                if ($row !== null) {
                    self::forget($key);
                }
                // != on purpose: text column "5" must loosely equal int 5.
            } elseif ($row === null || $row->v != $value) {
                self::putRaw($key, $value);
            }
        }
    }

    // Stable sha256 over every ENVCONFIG key's effective config value.
    public static function configHash(): string
    {
        $keys = self::keysInList('ENVCONFIG');
        sort($keys);

        $payload = [];
        foreach ($keys as $k) {
            $payload[$k] = config($k);
        }

        return hash('sha256', json_encode($payload));
    }
}
