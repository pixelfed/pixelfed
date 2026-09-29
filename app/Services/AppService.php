<?php

namespace App\Services;

use Illuminate\Support\Str;

class AppService
{
    public function getConfig()
    {
        return [
            'version' => config('pixelfed.version'),
            'oauth' => (bool) config_cache('pixelfed.oauth_enabled'),
            'signup' => [
                'curated' => (bool) config_cache('instance.curated_registration.enabled'),
                'mobile_registration' => (bool) config_cache('pixelfed.open_registration') && config('auth.in_app_registration'),
            ],
            'features' => [
                'stories' => (bool) config_cache('instance.stories.enabled'),
                'video' => Str::contains(config_cache('pixelfed.media_types'), 'video/mp4'),
                'timelines' => [
                    'local' => true,
                    'network' => (bool) config('federation.network_timeline'),
                ],
            ],
            'limits' => [
                'account' => [
                    'max_avatar_size' => (int) config_cache('pixelfed.max_avatar_size'),
                    'max_bio_length' => (int) config('pixelfed.max_bio_length'),
                    'max_name_length' => (int) config('pixelfed.max_name_length'),
                    'min_password_length' => (int) config('pixelfed.min_password_length'),
                ],
                'collections' => [
                    'max_collection_length' => (int) config_cache('pixelfed.max_collection_length'),
                ],
                'direct_messages' => [
                    'max_characters' => (int) config('dm.max_message_length'),
                    'max_media_attachments' => (int) config('dm.max_media'),
                    'group_chats' => [
                        'enabled' => (bool) config('dm.groups.enabled'),
                        'max_participants' => (int) config('dm.groups.max_participants'),
                    ],
                ],
                'media' => [
                    'image_size_limit' => (int) (config_cache('pixelfed.max_photo_size') * 1024),
                    'max_album_limit' => (int) config_cache('pixelfed.max_album_length'),
                    'max_alt_text_length' => (int) config_cache('pixelfed.max_altext_length'),
                    'supported_mime_types' => explode(',', config_cache('pixelfed.media_types')),
                    'video_size_limit' => (int) (config_cache('pixelfed.max_photo_size') * 1024),
                ],
                'statuses' => [
                    'max_characters' => (int) config_cache('pixelfed.max_caption_length'),
                ],
            ],
        ];
    }
}
