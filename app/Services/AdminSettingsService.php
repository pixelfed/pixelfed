<?php

namespace App\Services;

use App\Services\Internal\BeagleService;
use App\User;
use Illuminate\Support\Str;

class AdminSettingsService
{
    public static function getAll()
    {
        return [
            'features' => self::getFeatures(),
            'landing' => self::getLanding(),
            'branding' => self::getBranding(),
            'media' => self::getMedia(),
            'rules' => self::getRules(),
            'suggested_rules' => self::getSuggestedRules(),
            'users' => self::getUsers(),
            'posts' => self::getPosts(),
            'platform' => self::getPlatform(),
            'storage' => self::getStorage(),
        ];
    }

    public static function getFeatures()
    {
        $cloud_storage = (bool) config_cache('pixelfed.cloud_storage');
        $cloud_disk = config('filesystems.cloud');
        $cloud_ready = ! empty(config('filesystems.disks.'.$cloud_disk.'.key')) && ! empty(config('filesystems.disks.'.$cloud_disk.'.secret'));
        $openReg = (bool) config_cache('pixelfed.open_registration');
        $curOnboarding = (bool) config_cache('instance.curated_registration.enabled');
        $regState = $openReg ? 'open' : ($curOnboarding ? 'filtered' : 'closed');

        return [
            'registration_status' => $regState,
            'cloud_storage' => $cloud_ready && $cloud_storage,
            'activitypub_enabled' => (bool) config_cache('federation.activitypub.enabled'),
            'authorized_fetch' => (bool) config_cache('federation.activitypub.authorized_fetch'),
            'account_migration' => (bool) config_cache('federation.migration'),
            'mobile_apis' => (bool) config_cache('pixelfed.oauth_enabled'),
            'stories' => (bool) config_cache('instance.stories.enabled'),
            'instagram_import' => (bool) config_cache('pixelfed.import.instagram.enabled'),
            'autospam_enabled' => (bool) config_cache('pixelfed.bouncer.enabled'),
        ];
    }

    public static function getLanding()
    {
        $availableAdmins = User::whereIsAdmin(true)->get();
        $currentAdmin = config_cache('instance.admin.pid');

        return [
            'admins' => $availableAdmins,
            'current_admin' => $currentAdmin,
            'show_directory' => (bool) config_cache('instance.landing.show_directory'),
            'show_explore' => (bool) config_cache('instance.landing.show_explore'),
        ];
    }

    public static function getBranding()
    {
        return [
            'name' => config_cache('app.name'),
            'short_description' => config_cache('app.short_description'),
            'long_description' => config_cache('app.description'),
        ];
    }

    public static function getMedia()
    {
        return [
            'max_photo_size' => config_cache('pixelfed.max_photo_size'),
            'max_album_length' => config_cache('pixelfed.max_album_length'),
            'image_quality' => config_cache('pixelfed.image_quality'),
            'media_types' => config_cache('pixelfed.media_types'),
            'optimize_image' => (bool) config_cache('pixelfed.optimize_image'),
            'optimize_video' => (bool) config_cache('pixelfed.optimize_video'),
        ];
    }

    public static function getRules()
    {
        return config_cache('app.rules') ? json_decode(config_cache('app.rules'), true) : [];
    }

    public static function getSuggestedRules()
    {
        return BeagleService::getDefaultRules();
    }

    public static function getUsers()
    {
        $autoFollow = config_cache('account.autofollow_usernames');
        if (strlen($autoFollow) >= 2) {
            $autoFollow = explode(',', $autoFollow);
        } else {
            $autoFollow = [];
        }

        return [
            'require_email_verification' => (bool) config_cache('pixelfed.enforce_email_verification'),
            'enforce_account_limit' => (bool) config_cache('pixelfed.enforce_account_limit'),
            'max_account_size' => config_cache('pixelfed.max_account_size'),
            'admin_autofollow' => (bool) config_cache('account.autofollow'),
            'admin_autofollow_accounts' => $autoFollow,
            'max_user_blocks' => (int) config_cache('instance.user_filters.max_user_blocks'),
            'max_user_mutes' => (int) config_cache('instance.user_filters.max_user_mutes'),
            'max_domain_blocks' => (int) config_cache('instance.user_filters.max_domain_blocks'),
        ];
    }

    public static function getPosts()
    {
        return [
            'max_caption_length' => config_cache('pixelfed.max_caption_length'),
            'max_altext_length' => config_cache('pixelfed.max_altext_length'),
        ];
    }

    public static function getPlatform()
    {
        return [
            'allow_app_registration' => (bool) config_cache('pixelfed.allow_app_registration'),
            'app_registration_rate_limit_attempts' => config_cache('pixelfed.app_registration_rate_limit_attempts'),
            'app_registration_rate_limit_decay' => config_cache('pixelfed.app_registration_rate_limit_decay'),
            'app_registration_confirm_rate_limit_attempts' => config_cache('pixelfed.app_registration_confirm_rate_limit_attempts'),
            'app_registration_confirm_rate_limit_decay' => config_cache('pixelfed.app_registration_confirm_rate_limit_decay'),
            'allow_post_embeds' => (bool) config_cache('instance.embed.post'),
            'allow_profile_embeds' => (bool) config_cache('instance.embed.profile'),
            'captcha_enabled' => (bool) config_cache('captcha.enabled'),
            'captcha_on_login' => (bool) config_cache('captcha.active.login'),
            'captcha_on_register' => (bool) config_cache('captcha.active.register'),
            'captcha_secret' => Str::of(config_cache('captcha.secret'))->mask('*', 4, -4),
            'captcha_sitekey' => Str::of(config_cache('captcha.sitekey'))->mask('*', 4, -4),
            'custom_emoji_enabled' => (bool) config_cache('federation.custom_emoji.enabled'),
        ];
    }

    public static function getStorage()
    {
        $cloud_storage = (bool) config_cache('pixelfed.cloud_storage');
        $cloud_disk = config('filesystems.cloud');
        $cloud_ready = ! empty(config('filesystems.disks.'.$cloud_disk.'.key')) && ! empty(config('filesystems.disks.'.$cloud_disk.'.secret'));
        $primaryDisk = (bool) $cloud_ready && $cloud_storage;
        $pkey = 'filesystems.disks.'.$cloud_disk.'.';
        $disk = [
            'driver' => $cloud_disk,
            'key' => Str::of(config_cache($pkey.'key'))->mask('*', 0, -2),
            'secret' => Str::of(config_cache($pkey.'secret'))->mask('*', 0, -2),
            'region' => config_cache($pkey.'region'),
            'bucket' => config_cache($pkey.'bucket'),
            'visibility' => config_cache($pkey.'visibility'),
            'endpoint' => config_cache($pkey.'endpoint'),
            'url' => config_cache($pkey.'url'),
            'use_path_style_endpoint' => config_cache($pkey.'use_path_style_endpoint'),
        ];

        return [
            'primary_disk' => $primaryDisk ? 'cloud' : 'local',
            'cloud_ready' => (bool) $cloud_ready,
            'cloud_disk' => $cloud_disk,
            'disk_config' => $disk,
        ];
    }
}
