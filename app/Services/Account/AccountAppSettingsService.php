<?php

namespace App\Services\Account;

class AccountAppSettingsService
{
    public static function default()
    {
        return [
            'timelines' => [
                // Show public timeline feed
                'show_public' => false,

                // Show network timeline feed
                'show_network' => false,

                // Hide likes and share counts
                'hide_likes_shares' => false,
            ],

            'media' => [
                // Hide media on Public/Network timelines behind CW
                'hide_public_behind_cw' => true,

                // Always show media with CW
                'always_show_cw' => false,

                // Show alt text if present below media
                'show_alt_text' => false,
            ],

            'appearance' => [
                // Use in-app browser when opening links
                'links_use_in_app_browser' => true,

                // App theme, can be 'light', 'dark' or 'system'
                'theme' => 'system',
            ],
        ];
    }
}
