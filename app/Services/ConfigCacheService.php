<?php

namespace App\Services;

use App\Models\ConfigCache as ConfigCacheModel;
use App\Services\Config\EnvConfigValidator;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Cache;

class ConfigCacheService
{
    const CACHE_KEY = 'config_cache:_v0-key:';

    // Keys whose value is a secret and must be encrypted at rest and masked
    // when read back through the admin API / debug page.
    const PROTECTED_KEYS = [
        'filesystems.disks.s3.secret',
        'filesystems.disks.spaces.secret',
        'captcha.hcaptcha.secret',
        'captcha.turnstile.secret',
        'captcha.cap.secret',
    ];

    const KEYS = [
        // filesystems.php — s3 disk
        'filesystems.disks.s3.key' => ['list' => 'ENVCONFIG', 'env' => 'AWS_ACCESS_KEY_ID', 'rule' => 'string'],
        'filesystems.disks.s3.secret' => ['list' => 'ENVCONFIG', 'env' => 'AWS_SECRET_ACCESS_KEY', 'rule' => 'string'],
        'filesystems.disks.s3.region' => ['list' => 'ENVCONFIG', 'env' => 'AWS_DEFAULT_REGION', 'rule' => 'string'],
        'filesystems.disks.s3.bucket' => ['list' => 'ENVCONFIG', 'env' => 'AWS_BUCKET', 'rule' => 'string'],
        'filesystems.disks.s3.visibility' => ['list' => 'ENVCONFIG', 'env' => 'AWS_VISIBILITY', 'rule' => 'in:public,private'],
        'filesystems.disks.s3.url' => ['list' => 'ENVCONFIG', 'env' => 'AWS_URL', 'rule' => 'url'],
        'filesystems.disks.s3.endpoint' => ['list' => 'ENVCONFIG', 'env' => 'AWS_ENDPOINT', 'rule' => 'url'],
        'filesystems.disks.s3.use_path_style_endpoint' => ['list' => 'ENVCONFIG', 'env' => 'AWS_USE_PATH_STYLE_ENDPOINT', 'rule' => 'boolean'],

        // filesystems.php — spaces disk
        'filesystems.disks.spaces.key' => ['list' => 'ENVCONFIG', 'env' => 'DO_SPACES_KEY', 'rule' => 'string'],
        'filesystems.disks.spaces.secret' => ['list' => 'ENVCONFIG', 'env' => 'DO_SPACES_SECRET', 'rule' => 'string'],
        'filesystems.disks.spaces.region' => ['list' => 'ENVCONFIG', 'env' => 'DO_SPACES_REGION', 'rule' => 'string'],
        'filesystems.disks.spaces.bucket' => ['list' => 'ENVCONFIG', 'env' => 'DO_SPACES_BUCKET', 'rule' => 'string'],
        'filesystems.disks.spaces.url' => ['list' => 'ENVCONFIG', 'env' => 'DO_SPACES_URL', 'rule' => 'url'],
        'filesystems.disks.spaces.endpoint' => ['list' => 'ENVCONFIG', 'env' => 'DO_SPACES_ENDPOINT', 'rule' => 'url'],
        'filesystems.disks.spaces.visibility' => ['list' => 'ENVCONFIG', 'env' => 'DO_SPACES_VISIBILITY', 'rule' => 'in:public,private'],
        'filesystems.disks.spaces.use_path_style_endpoint' => ['list' => 'ENVCONFIG', 'env' => 'DO_SPACES_USE_PATH_STYLE_ENDPOINT', 'rule' => 'boolean'],

        // app.php
        'app.name' => ['list' => 'ENVCONFIG', 'env' => 'APP_NAME', 'rule' => 'string'],
        'app.short_description' => ['list' => 'ENVCONFIG', 'env' => 'PF_SHORT_DESCRIPTION', 'rule' => 'string'],
        'app.description' => ['list' => 'ENVCONFIG', 'env' => 'PF_DESCRIPTION', 'rule' => 'string'],
        'app.rules' => ['list' => 'ENVCONFIG', 'env' => 'PF_RULES', 'rule' => 'json'],

        // pixelfed.php
        'pixelfed.max_photo_size' => ['list' => 'ENVCONFIG', 'env' => 'MAX_PHOTO_SIZE', 'rule' => 'integer|min:100'],
        'pixelfed.max_album_length' => ['list' => 'ENVCONFIG', 'env' => 'MAX_ALBUM_LENGTH', 'rule' => 'integer|min:1|max:20'],
        'pixelfed.image_quality' => ['list' => 'ENVCONFIG', 'env' => 'IMAGE_QUALITY', 'rule' => 'integer|min:1|max:100'],
        'pixelfed.media_types' => ['list' => 'ENVCONFIG', 'env' => 'MEDIA_TYPES', 'rule' => 'string'],
        'pixelfed.open_registration' => ['list' => 'ENVCONFIG', 'env' => 'OPEN_REGISTRATION', 'rule' => 'boolean'],
        'pixelfed.oauth_enabled' => ['list' => 'ENVCONFIG', 'env' => 'OAUTH_ENABLED', 'rule' => 'boolean'],
        'pixelfed.import.instagram.enabled' => ['list' => 'ENVCONFIG', 'env' => 'IMPORT_INSTAGRAM', 'rule' => 'boolean'],
        'pixelfed.bouncer.enabled' => ['list' => 'ENVCONFIG', 'env' => 'PF_BOUNCER_ENABLED', 'rule' => 'boolean'],
        'pixelfed.enforce_email_verification' => ['list' => 'ENVCONFIG', 'env' => 'ENFORCE_EMAIL_VERIFICATION', 'rule' => 'boolean'],
        'pixelfed.max_account_size' => ['list' => 'ENVCONFIG', 'env' => 'MAX_ACCOUNT_SIZE', 'rule' => 'integer|min:50000'],
        'pixelfed.enforce_account_limit' => ['list' => 'ENVCONFIG', 'env' => 'LIMIT_ACCOUNT_SIZE', 'rule' => 'boolean'],
        'pixelfed.cloud_storage' => ['list' => 'ENVCONFIG', 'env' => 'PF_ENABLE_CLOUD', 'rule' => 'boolean'],
        'pixelfed.max_caption_length' => ['list' => 'ENVCONFIG', 'env' => 'MAX_CAPTION_LENGTH', 'rule' => 'integer'],
        'pixelfed.max_bio_length' => ['list' => 'ENVCONFIG', 'env' => 'MAX_BIO_LENGTH', 'rule' => 'integer'],
        'pixelfed.max_name_length' => ['list' => 'ENVCONFIG', 'env' => 'MAX_NAME_LENGTH', 'rule' => 'integer'],
        'pixelfed.min_password_length' => ['list' => 'ENVCONFIG', 'env' => 'MIN_PASSWORD_LENGTH', 'rule' => 'integer|min:6'],
        'pixelfed.max_avatar_size' => ['list' => 'ENVCONFIG', 'env' => 'MAX_AVATAR_SIZE', 'rule' => 'integer'],
        'pixelfed.max_altext_length' => ['list' => 'ENVCONFIG', 'env' => 'PF_MEDIA_MAX_ALTTEXT_LENGTH', 'rule' => 'integer'],
        'pixelfed.allow_app_registration' => ['list' => 'ENVCONFIG', 'env' => 'PF_ALLOW_APP_REGISTRATION', 'rule' => 'boolean'],
        'pixelfed.app_registration_rate_limit_attempts' => ['list' => 'ENVCONFIG', 'env' => 'PF_IAR_RL_ATTEMPTS', 'rule' => 'integer'],
        'pixelfed.app_registration_rate_limit_decay' => ['list' => 'ENVCONFIG', 'env' => 'PF_IAR_RL_DECAY', 'rule' => 'integer'],
        'pixelfed.app_registration_confirm_rate_limit_attempts' => ['list' => 'ENVCONFIG', 'env' => 'PF_IARC_RL_ATTEMPTS', 'rule' => 'integer'],
        'pixelfed.app_registration_confirm_rate_limit_decay' => ['list' => 'ENVCONFIG', 'env' => 'PF_IARC_RL_DECAY', 'rule' => 'integer'],
        'pixelfed.optimize_image' => ['list' => 'ENVCONFIG', 'env' => 'PF_OPTIMIZE_IMAGES', 'rule' => 'boolean'],
        'pixelfed.optimize_video' => ['list' => 'ENVCONFIG', 'env' => 'PF_OPTIMIZE_VIDEOS', 'rule' => 'boolean'],
        'pixelfed.max_collection_length' => ['list' => 'ENVCONFIG', 'env' => 'PF_MAX_COLLECTION_LENGTH', 'rule' => 'integer'],

        // federation.php
        'federation.activitypub.enabled' => ['list' => 'ENVCONFIG', 'env' => 'ACTIVITY_PUB', 'rule' => 'boolean'],
        'federation.activitypub.authorized_fetch' => ['list' => 'ENVCONFIG', 'env' => 'AUTHORIZED_FETCH', 'rule' => 'boolean'],
        'federation.migration' => ['list' => 'ENVCONFIG', 'env' => 'PF_ACCT_MIGRATION_ENABLED', 'rule' => 'boolean'],
        'federation.custom_emoji.enabled' => ['list' => 'ENVCONFIG', 'env' => 'CUSTOM_EMOJI', 'rule' => 'boolean'],

        // instance.php
        'instance.stories.enabled' => ['list' => 'ENVCONFIG', 'env' => 'STORIES_ENABLED', 'rule' => 'boolean'],
        'instance.avatar.local_to_cloud' => ['list' => 'ENVCONFIG', 'env' => 'PF_LOCAL_AVATAR_TO_CLOUD', 'rule' => 'boolean'],
        'instance.has_legal_notice' => ['list' => 'ENVCONFIG', 'env' => 'INSTANCE_LEGAL_NOTICE', 'rule' => 'boolean'],
        'instance.landing.show_directory' => ['list' => 'ENVCONFIG', 'env' => 'INSTANCE_LANDING_SHOW_DIRECTORY', 'rule' => 'boolean'],
        'instance.landing.show_explore' => ['list' => 'ENVCONFIG', 'env' => 'INSTANCE_LANDING_SHOW_EXPLORE', 'rule' => 'boolean'],
        'instance.curated_registration.enabled' => ['list' => 'ENVCONFIG', 'env' => 'INSTANCE_CUR_REG', 'rule' => 'boolean'],
        'instance.embed.profile' => ['list' => 'ENVCONFIG', 'env' => 'INSTANCE_PROFILE_EMBEDS', 'rule' => 'boolean'],
        'instance.embed.post' => ['list' => 'ENVCONFIG', 'env' => 'INSTANCE_POST_EMBEDS', 'rule' => 'boolean'],
        'instance.user_filters.max_user_blocks' => ['list' => 'ENVCONFIG', 'env' => 'PF_MAX_USER_BLOCKS', 'rule' => 'integer|min:0|max:5000'],
        'instance.user_filters.max_user_mutes' => ['list' => 'ENVCONFIG', 'env' => 'PF_MAX_USER_MUTES', 'rule' => 'integer|min:0|max:5000'],
        'instance.user_filters.max_domain_blocks' => ['list' => 'ENVCONFIG', 'env' => 'PF_MAX_DOMAIN_BLOCKS', 'rule' => 'integer|min:0|max:5000'],
        'instance.admin.pid' => ['list' => 'ENVCONFIG', 'env' => 'PF_ADMIN_PID', 'rule' => 'integer'],
        'instance.banner.blurhash' => ['list' => 'ENVCONFIG', 'env' => 'INSTANCE_BANNER_BLURHASH', 'rule' => 'string'],

        // media.php
        'media.delete_local_after_cloud' => ['list' => 'ENVCONFIG', 'env' => 'MEDIA_DELETE_LOCAL_AFTER_CLOUD', 'rule' => 'boolean'],

        // captcha.php
        'captcha.enabled' => ['list' => 'ENVCONFIG', 'env' => 'CAPTCHA_ENABLED', 'rule' => 'boolean'],
        'captcha.driver' => ['list' => 'ENVCONFIG', 'env' => 'CAPTCHA_DRIVER', 'rule' => 'in:hcaptcha,turnstile,cap'],
        'captcha.hcaptcha.secret' => ['list' => 'ENVCONFIG', 'env' => 'CAPTCHA_H_SECRET', 'rule' => 'string'],
        'captcha.hcaptcha.sitekey' => ['list' => 'ENVCONFIG', 'env' => 'CAPTCHA_H_SITEKEY', 'rule' => 'string'],
        'captcha.turnstile.secret' => ['list' => 'ENVCONFIG', 'env' => 'CAPTCHA_TURNSTILE_SECRET', 'rule' => 'string'],
        'captcha.turnstile.sitekey' => ['list' => 'ENVCONFIG', 'env' => 'CAPTCHA_TURNSTILE_SITEKEY', 'rule' => 'string'],
        'captcha.cap.endpoint' => ['list' => 'ENVCONFIG', 'env' => 'CAPTCHA_CAP_ENDPOINT', 'rule' => 'url'],
        'captcha.cap.sitekey' => ['list' => 'ENVCONFIG', 'env' => 'CAPTCHA_CAP_SITEKEY', 'rule' => 'string'],
        'captcha.cap.secret' => ['list' => 'ENVCONFIG', 'env' => 'CAPTCHA_CAP_SECRET', 'rule' => 'string'],
        'captcha.active.login' => ['list' => 'ENVCONFIG', 'env' => 'CAPTCHA_ENABLED_ON_LOGIN', 'rule' => 'boolean'],
        'captcha.active.register' => ['list' => 'ENVCONFIG', 'env' => 'CAPTCHA_ENABLED_ON_REGISTER', 'rule' => 'boolean'],
        'captcha.active.forgot_password' => ['list' => 'ENVCONFIG', 'env' => 'CAPTCHA_ENABLED_ON_FORGOT_PASSWORD', 'rule' => 'boolean'],
        'captcha.active.password_reset' => ['list' => 'ENVCONFIG', 'env' => 'CAPTCHA_ENABLED_ON_PASSWORD_RESET', 'rule' => 'boolean'],
        'captcha.active.forgot_email' => ['list' => 'ENVCONFIG', 'env' => 'CAPTCHA_ENABLED_ON_FORGOT_EMAIL', 'rule' => 'boolean'],
        'captcha.active.curated_register' => ['list' => 'ENVCONFIG', 'env' => 'CAPTCHA_ENABLED_ON_CURATED_REGISTER', 'rule' => 'boolean'],

        // ADMINONLY (no env var)
        'app.banner_image' => ['list' => 'ADMINONLY', 'rule' => 'string'],
        'about.title' => ['list' => 'ADMINONLY', 'rule' => 'string'],
        'uikit.custom.css' => ['list' => 'ADMINONLY', 'rule' => 'string'],
        'uikit.custom.js' => ['list' => 'ADMINONLY', 'rule' => 'string'],
        'uikit.show_custom.css' => ['list' => 'ADMINONLY', 'rule' => 'boolean'],
        'uikit.show_custom.js' => ['list' => 'ADMINONLY', 'rule' => 'boolean'],
        'account.autofollow' => ['list' => 'ADMINONLY', 'rule' => 'boolean'],
        'account.autofollow_usernames' => ['list' => 'ADMINONLY', 'rule' => 'string'],
        'config.discover.features' => ['list' => 'ADMINONLY', 'rule' => 'json'],
        'pixelfed.directory' => ['list' => 'ADMINONLY', 'rule' => 'boolean'],
        'pixelfed.directory.submission-key' => ['list' => 'ADMINONLY', 'rule' => 'string'],
        'pixelfed.directory.submission-ts' => ['list' => 'ADMINONLY', 'rule' => 'string'],
        'pixelfed.directory.has_submitted' => ['list' => 'ADMINONLY', 'rule' => 'boolean'],
        'pixelfed.directory.latest_response' => ['list' => 'ADMINONLY', 'rule' => 'string'],
        'pixelfed.directory.is_synced' => ['list' => 'ADMINONLY', 'rule' => 'boolean'],
        'pixelfed.directory.testimonials' => ['list' => 'ADMINONLY', 'rule' => 'json'],
        'instance.stats.total_local_posts' => ['list' => 'ADMINONLY', 'rule' => 'integer'],
        'autospam.nlp.enabled' => ['list' => 'ADMINONLY', 'rule' => 'boolean'],
    ];

    public static function isCached(string $key): bool
    {
        return isset(self::KEYS[$key]);
    }

    public static function listOf(string $key): ?string
    {
        return self::KEYS[$key]['list'] ?? null;
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
        return array_keys(array_filter(self::KEYS, fn ($m) => ($m['list'] ?? null) === $list));
    }

    public static function adminVisibleKeys(): array
    {
        return array_keys(self::KEYS);
    }

    // True while the key's env var is present and valid: env wins the read and
    // the key is locked from admin edits.
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

                return $v;
            });
        } catch (Exception|QueryException) {
            return config($key);
        }
    }

    // Low-level write guard. Env-locked keys are not writable (env wins), so the
    // write is skipped and the current value returned. This is a safety net;
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

    // Writes without the lock check. The sync uses this; app code calls put().
    public static function putRaw($key, $val)
    {
        $row = ConfigCacheModel::firstOrNew(['k' => $key]);
        $row->v = self::isProtected($key) ? encrypt($val) : $val;
        $row->save();

        Cache::put(self::CACHE_KEY.$key, $val, now()->addHours(12));

        return self::get($key);
    }

    // The `v` column is text, so cast the value back to its declared type.
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

    // Deletes the row and its cache entry.
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
}
