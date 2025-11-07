<?php

namespace App\Services;

use App\Models\ConfigCache as ConfigCacheModel;
use Cache;
use Exception;
use Illuminate\Database\QueryException;

class ConfigCacheService
{
    const CACHE_KEY = 'config_cache:_v0-key:';

    const PROTECTED_KEYS = [
        'filesystems.disks.s3.key',
        'filesystems.disks.s3.secret',
        'filesystems.disks.spaces.key',
        'filesystems.disks.spaces.secret',
        'captcha.secret',
        'captcha.sitekey',
    ];

    public static function get($key)
    {
        $cacheKey = self::CACHE_KEY.$key;
        $ttl = now()->addHours(12);
        if (! config('instance.enable_cc')) {
            return config($key);
        }

        try {
            return Cache::remember($cacheKey, $ttl, function () use ($key) {
                $allowed = [
                    'app.name',
                    'app.short_description',
                    'app.description',
                    'app.rules',

                    'pixelfed.max_photo_size',
                    'pixelfed.max_album_length',
                    'pixelfed.image_quality',
                    'pixelfed.media_types',

                    'pixelfed.open_registration',
                    'federation.activitypub.enabled',
                    'instance.stories.enabled',
                    'pixelfed.oauth_enabled',
                    'pixelfed.import.instagram.enabled',
                    'pixelfed.bouncer.enabled',
                    'federation.activitypub.authorized_fetch',

                    'pixelfed.enforce_email_verification',
                    'pixelfed.max_account_size',
                    'pixelfed.enforce_account_limit',

                    'uikit.custom.css',
                    'uikit.custom.js',
                    'uikit.show_custom.css',
                    'uikit.show_custom.js',
                    'about.title',

                    'pixelfed.cloud_storage',

                    'account.autofollow',
                    'account.autofollow_usernames',
                    'config.discover.features',

                    'instance.has_legal_notice',
                    'instance.avatar.local_to_cloud',

                    'pixelfed.directory',
                    'app.banner_image',
                    'pixelfed.directory.submission-key',
                    'pixelfed.directory.submission-ts',
                    'pixelfed.directory.has_submitted',
                    'pixelfed.directory.latest_response',
                    'pixelfed.directory.is_synced',
                    'pixelfed.directory.testimonials',

                    'instance.landing.show_directory',
                    'instance.landing.show_explore',
                    'instance.admin.pid',
                    'instance.banner.blurhash',

                    'autospam.nlp.enabled',

                    'instance.curated_registration.enabled',

                    'federation.migration',

                    'pixelfed.max_caption_length',
                    'pixelfed.max_bio_length',
                    'pixelfed.max_name_length',
                    'pixelfed.min_password_length',
                    'pixelfed.max_avatar_size',
                    'pixelfed.max_altext_length',
                    'pixelfed.allow_app_registration',
                    'pixelfed.app_registration_rate_limit_attempts',
                    'pixelfed.app_registration_rate_limit_decay',
                    'pixelfed.app_registration_confirm_rate_limit_attempts',
                    'pixelfed.app_registration_confirm_rate_limit_decay',
                    'instance.embed.profile',
                    'instance.embed.post',

                    'captcha.enabled',
                    'captcha.secret',
                    'captcha.sitekey',
                    'captcha.active.login',
                    'captcha.active.register',
                    'captcha.triggers.login.enabled',
                    'captcha.triggers.login.attempts',
                    'federation.custom_emoji.enabled',

                    'pixelfed.optimize_image',
                    'pixelfed.optimize_video',
                    'pixelfed.max_collection_length',
                    'media.delete_local_after_cloud',
                    'instance.user_filters.max_user_blocks',
                    'instance.user_filters.max_user_mutes',
                    'instance.user_filters.max_domain_blocks',

                    'filesystems.disks.s3.key',
                    'filesystems.disks.s3.secret',
                    'filesystems.disks.s3.region',
                    'filesystems.disks.s3.bucket',
                    'filesystems.disks.s3.visibility',
                    'filesystems.disks.s3.url',
                    'filesystems.disks.s3.endpoint',
                    'filesystems.disks.s3.use_path_style_endpoint',

                    'filesystems.disks.spaces.key',
                    'filesystems.disks.spaces.secret',
                    'filesystems.disks.spaces.region',
                    'filesystems.disks.spaces.bucket',
                    'filesystems.disks.spaces.visibility',
                    'filesystems.disks.spaces.url',
                    'filesystems.disks.spaces.endpoint',
                    'filesystems.disks.spaces.use_path_style_endpoint',

                    'instance.stats.total_local_posts',
                    // 'system.user_mode'
                ];

                if (! config('instance.enable_cc')) {
                    return config($key);
                }

                if (! in_array($key, $allowed)) {
                    return config($key);
                }

                $protect = false;
                $protected = null;
                if (in_array($key, self::PROTECTED_KEYS)) {
                    $protect = true;
                }

                $v = config($key);
                $c = ConfigCacheModel::where('k', $key)->first();

                if ($c) {
                    if ($protect) {
                        return decrypt($c->v) ?? config($key);
                    } else {
                        return $c->v ?? config($key);
                    }
                }

                if (! $v) {
                    return;
                }

                if ($protect && $v) {
                    $protected = encrypt($v);
                }

                $cc = new ConfigCacheModel;
                $cc->k = $key;
                $cc->v = $protect ? $protected : $v;
                $cc->save();

                return $v;
            });
        } catch (Exception|QueryException $e) {
            return config($key);
        }
    }

    public static function put($key, $val)
    {
        $exists = ConfigCacheModel::whereK($key)->first();

        $protect = false;
        $protected = null;
        if (in_array($key, self::PROTECTED_KEYS)) {
            $protect = true;
            $protected = encrypt($val);
        }

        if ($exists) {
            $exists->v = $protect ? $protected : $val;
            $exists->save();
            Cache::put(self::CACHE_KEY.$key, $val, now()->addHours(12));

            return self::get($key);
        }

        $cc = new ConfigCacheModel;
        $cc->k = $key;
        $cc->v = $protect ? $protected : $val;
        $cc->save();

        Cache::put(self::CACHE_KEY.$key, $val, now()->addHours(12));

        return self::get($key);
    }
}
