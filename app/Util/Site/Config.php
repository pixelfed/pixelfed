<?php

namespace App\Util\Site;

use Cache;
use Illuminate\Support\Str;

class Config
{

    public static function get()
    {
        return Cache::remember('api:site:configuration', now()->addMinutes(30), function () {
            return [
                'open_registration' => config('pixelfed.open_registration'),
                'uploader' => [
                    'max_photo_size' => config('pixelfed.max_photo_size'),
                    'max_caption_length' => config('pixelfed.max_caption_length'),
                    'album_limit' => config('pixelfed.max_album_length'),
                    'image_quality' => config('pixelfed.image_quality'),

                    'optimize_image' => config('pixelfed.optimize_image'),
                    'optimize_video' => config('pixelfed.optimize_video'),

                    'media_types' => config('pixelfed.media_types'),
                    'enforce_account_limit' => config('pixelfed.enforce_account_limit')
                ],

                'activitypub' => [
                    'enabled' => config('federation.activitypub.enabled'),
                    'remote_follow' => config('federation.activitypub.remoteFollow')
                ],

                'ab' => [
                    'lc' => config('exp.lc'),
                    'rec' => config('exp.rec'),
                    'loops' => config('exp.loops')
                ],

                'site' => [
                    'name' => config('app.name', 'pixelfed'),
                    'domain' => config('pixelfed.domain.app'),
                    'url'    => config('app.url'),
                    'description' => config('instance.description')
                ],

                'username' => [
                    'remote' => [
                        'formats' => config('instance.username.remote.formats'),
                        'format' => config('instance.username.remote.format'),
                        'custom' => config('instance.username.remote.custom')
                    ]
                ],

                'features' => [
                    'mobile_apis' => config('pixelfed.oauth_enabled'),
                    'circles' => false,
                    'stories' => config('instance.stories.enabled'),
                    'video' => Str::contains(config('pixelfed.media_types'), 'video/mp4'),
                    'import' => [
                        'instagram' => config('pixelfed.import.instagram.enabled'),
                        'mastodon' => false,
                        'pixelfed' => false
                    ]
                ]
            ];
        });
    }

    public static function json()
    {
        return json_encode(self::get(), JSON_FORCE_OBJECT);
    }
}
