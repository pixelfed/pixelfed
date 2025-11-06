<?php

namespace App\Services;

use App\User;
use App\Util\Site\Nodeinfo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class LandingService
{
    public static function get($json = true)
    {
        $activeMonth = Nodeinfo::activeUsersMonthly();

        $totalUsers = Cache::remember('api:nodeinfo:users', 43200, function () {
            return User::count();
        });

        $postCount = InstanceService::totalLocalStatuses();

        $contactAccount = Cache::remember('api:v1:instance-data:contact', 604800, function () {
            if (config_cache('instance.admin.pid')) {
                return AccountService::getMastodon(config_cache('instance.admin.pid'), true);
            }
            $admin = User::whereIsAdmin(true)->first();

            return $admin && isset($admin->profile_id) ?
                AccountService::getMastodon($admin->profile_id, true) :
                null;
        });

        $rules = Cache::remember('api:v1:instance-data:rules', 604800, function () {
            return config_cache('app.rules') ?
                collect(json_decode(config_cache('app.rules'), true))
                    ->map(function ($rule, $key) {
                        $id = $key + 1;

                        return [
                            'id' => "{$id}",
                            'text' => $rule,
                        ];
                    })
                    ->toArray() : [];
        });

        $openReg = (bool) config_cache('pixelfed.open_registration');

        $res = [
            'name' => config_cache('app.name'),
            'url' => config_cache('app.url'),
            'domain' => config('pixelfed.domain.app'),
            'show_directory' => (bool) config_cache('instance.landing.show_directory'),
            'show_explore_feed' => (bool) config_cache('instance.landing.show_explore'),
            'show_legal_notice_link' => (bool) config('instance.has_legal_notice'),
            'open_registration' => (bool) $openReg,
            'curated_onboarding' => (bool) config_cache('instance.curated_registration.enabled'),
            'version' => config('pixelfed.version'),
            'about' => [
                'banner_image' => config_cache('app.banner_image') ?? url('/storage/headers/default.jpg'),
                'short_description' => config_cache('app.short_description'),
                'description' => config_cache('app.description'),
            ],
            'stats' => [
                'active_users' => (int) $activeMonth,
                'posts_count' => (int) $postCount,
                'total_users' => (int) $totalUsers,
            ],
            'contact' => [
                'account' => $contactAccount,
                'email' => config('instance.email'),
            ],
            'rules' => $rules,
            'uploader' => [
                'max_photo_size' => (int) (config_cache('pixelfed.max_photo_size') * 1024),
                'max_caption_length' => (int) config_cache('pixelfed.max_caption_length'),
                'max_altext_length' => (int) config_cache('pixelfed.max_altext_length'),
                'album_limit' => (int) config_cache('pixelfed.max_album_length'),
                'image_quality' => (int) config_cache('pixelfed.image_quality'),
                'max_collection_length' => (int) config('pixelfed.max_collection_length'),
                'optimize_image' => (bool) config_cache('pixelfed.optimize_image'),
                'optimize_video' => (bool) config_cache('pixelfed.optimize_video'),
                'media_types' => config_cache('pixelfed.media_types'),
            ],
            'features' => [
                'federation' => (bool) config_cache('federation.activitypub.enabled'),
                'timelines' => [
                    'local' => true,
                    'network' => (bool) config_cache('federation.network_timeline'),
                ],
                'mobile_apis' => (bool) config_cache('pixelfed.oauth_enabled'),
                'stories' => (bool) config_cache('instance.stories.enabled'),
                'video' => Str::contains(config_cache('pixelfed.media_types'), 'video/mp4'),
            ],
        ];

        if ($json) {
            return json_encode($res);
        }

        return $res;
    }
}
