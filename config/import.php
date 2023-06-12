<?php

return [
    /*
     *  Import from Instagram
     *
     *  Allow users to import posts from Instagram
     *
     */
    'instagram' => [
        'enabled' => env('PF_IMPORT_FROM_INSTAGRAM', true),

        'limits' => [
            // Limit max number of posts allowed to import
            'max_posts' => env('PF_IMPORT_IG_MAX_POSTS', 1000),

            // Limit max import attempts allowed, set to -1 for unlimited
            'max_attempts' => env('PF_IMPORT_IG_MAX_ATTEMPTS', -1),
        ],

        // Allow archived posts that will be archived upon import
        'allow_archived_posts' => false,

        // Allow video posts to be imported
        'allow_video_posts' => env('PF_IMPORT_IG_ALLOW_VIDEO_POSTS', true),

        'permissions' => [
            // Limit to admin accounts only
            'admins_only' => env('PF_IMPORT_IG_PERM_ADMIN_ONLY', false),

            // Limit to admin accounts and local accounts they follow only
            'admin_follows_only' => env('PF_IMPORT_IG_PERM_ADMIN_FOLLOWS_ONLY', false),

            // Limit to accounts older than X in days
            'min_account_age' => env('PF_IMPORT_IG_PERM_MIN_ACCOUNT_AGE', 1),

            // Limit to accounts with a min follower count of X
            'min_follower_count' => env('PF_IMPORT_IG_PERM_MIN_FOLLOWER_COUNT', 0),

            // Limit to specific user ids, in comma separated format
            'user_ids' => env('PF_IMPORT_IG_PERM_ONLY_USER_IDS', null),
        ]
    ]
];



