<?php

namespace App\Services;

use App\User;
use Illuminate\Support\Facades\Cache;

/**
 * Single source of truth for the cache keys that back the landing page,
 * /api/v1/instance, /api/v2/instance, and nodeinfo.
 *
 * These keys are otherwise duplicated across several controllers and a
 * background job; gathering them here makes it impossible to add a new
 * read site without also updating the bust paths.
 */
class LandingCacheService
{
    public const INSTANCE_RESPONSE_V1 = 'api:v1:instance-data-response-v1';

    public const INSTANCE_RESPONSE_V2 = 'api:v2:instance-data-response-v2';

    public const INSTANCE_CONTACT = 'api:v1:instance-data:contact';

    public const INSTANCE_RULES = 'api:v1:instance-data:rules';

    public const INSTANCE_STATS_V0 = 'api:v1:instance-data:stats:v0';

    public const NODEINFO = 'api:nodeinfo';

    public const NODEINFO_USERS = 'api:nodeinfo:users';

    public const NODEINFO_ACTIVE_MONTHLY = 'api:nodeinfo:active-users-monthly';

    public const NODEINFO_ACTIVE_HALFYEAR = 'api:nodeinfo:active-users-half-year';

    public const INSTANCE_TOTAL_POSTS = 'pf:services:instances:self:total-posts';

    public const INSTANCE_BANNER_BLURHASH = 'pf:services:instance:header-blurhash:v1';

    /**
     * Forget every key that backs the landing page and /api/v(1|2)/instance.
     *
     * Use this for explicit admin "clear cache" actions; prefer the more
     * targeted invalidateContact / invalidateRules / invalidateBanner /
     * invalidateStats methods when you know which subset changed.
     */
    public static function invalidate(): void
    {
        self::invalidateContact();
        self::invalidateRules();
        self::invalidateBanner();
        self::invalidateStats();
    }

    /**
     * Forget the contact-account-derived cache entries.
     *
     * Call this when the admin profile, avatar, bio, or display name changes.
     */
    public static function invalidateContact(): void
    {
        Cache::forget(self::INSTANCE_CONTACT);
        Cache::forget(self::INSTANCE_RESPONSE_V1);
        Cache::forget(self::INSTANCE_RESPONSE_V2);
    }

    /**
     * Forget the instance-rules-derived cache entries.
     *
     * Call this when app.rules changes.
     */
    public static function invalidateRules(): void
    {
        Cache::forget(self::INSTANCE_RULES);
        Cache::forget(self::INSTANCE_RESPONSE_V1);
        Cache::forget(self::INSTANCE_RESPONSE_V2);
    }

    /**
     * Forget the banner-image-derived cache entries.
     *
     * The header blurhash is cached with rememberForever AND short-circuited
     * by a config_cache value, so both layers must be cleared together —
     * otherwise the next render will return the stale blurhash from the
     * config_cache fallback path inside InstanceService::headerBlurhash().
     */
    public static function invalidateBanner(): void
    {
        Cache::forget(self::INSTANCE_BANNER_BLURHASH);
        Cache::forget(self::INSTANCE_RESPONSE_V1);
        Cache::forget(self::INSTANCE_RESPONSE_V2);
        ConfigCacheService::put('instance.banner.blurhash', '');
    }

    /**
     * Forget the counts/stats cache entries.
     *
     * These are time-driven (12h / 1h) under normal operation; explicit
     * invalidation is rarely necessary, but exposed here for the admin
     * "clear cache" action.
     */
    public static function invalidateStats(): void
    {
        Cache::forget(self::INSTANCE_STATS_V0);
        Cache::forget(self::NODEINFO);
        Cache::forget(self::NODEINFO_USERS);
        Cache::forget(self::NODEINFO_ACTIVE_MONTHLY);
        Cache::forget(self::NODEINFO_ACTIVE_HALFYEAR);
        Cache::forget(self::INSTANCE_TOTAL_POSTS);
        Cache::forget(self::INSTANCE_RESPONSE_V1);
        Cache::forget(self::INSTANCE_RESPONSE_V2);
    }

    /**
     * Does this profile back the configured contact account?
     *
     * Mirrors the resolution logic in LandingService::get() / the V1+V2
     * instance endpoints: prefer the explicitly-configured admin profile
     * id, otherwise fall back to the first is_admin user.
     */
    public static function profileBacksContact(int $profileId): bool
    {
        $configured = config_cache('instance.admin.pid');
        if ($configured) {
            return (int) $configured === $profileId;
        }

        $admin = User::whereIsAdmin(true)->first();

        return $admin && (int) $admin->profile_id === $profileId;
    }

    /**
     * @return string[]
     */
    public static function all(): array
    {
        return [
            self::INSTANCE_RESPONSE_V1,
            self::INSTANCE_RESPONSE_V2,
            self::INSTANCE_CONTACT,
            self::INSTANCE_RULES,
            self::INSTANCE_STATS_V0,
            self::NODEINFO,
            self::NODEINFO_USERS,
            self::NODEINFO_ACTIVE_MONTHLY,
            self::NODEINFO_ACTIVE_HALFYEAR,
            self::INSTANCE_TOTAL_POSTS,
            self::INSTANCE_BANNER_BLURHASH,
        ];
    }
}
