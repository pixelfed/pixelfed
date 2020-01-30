<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Domains
    |--------------------------------------------------------------------------
    |
    | Application domains used for routing
    |
    */
    'domain' => [
      'admin' => env('ADMIN_DOMAIN'),
      'app'   => env('APP_DOMAIN'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Pixelfed Version
    |--------------------------------------------------------------------------
    |
    | This value is the version of your Pixelfed instance.
    |
    */
    'version' => '0.10.8',

    /*
    |--------------------------------------------------------------------------
    | NodeInfo Route Path
    |--------------------------------------------------------------------------
    |
    | Do not change this value unless you know what you are doing.
    |
    */
    'nodeinfo' => [
      'url' => config('app.url').'/'.'api/nodeinfo/2.0.json',
    ],

    /*
    |--------------------------------------------------------------------------
    | PHP/ImageMagic/GD Memory Limit
    |--------------------------------------------------------------------------
    |
    | This memory_limit value is only used for image processing. The
    | default memory_limit php.ini is used for the rest of the app.
    |
    */
    'memory_limit' => env('MEMORY_LIMIT', '1024M'),

    /*
    |--------------------------------------------------------------------------
    | Allow New Registrations
    |--------------------------------------------------------------------------
    |
    | Enable/disable new local account registrations.
    |
    */
    'open_registration' => env('OPEN_REGISTRATION', true),

    /*
    |--------------------------------------------------------------------------
    | ActivityPub
    |--------------------------------------------------------------------------
    |
    */
    'activitypub_enabled' => env('ACTIVITY_PUB', false),
    'ap_delivery_timeout' => env('ACTIVITYPUB_DELIVERY_TIMEOUT', 2.0),
    'ap_delivery_concurrency' => env('ACTIVITYPUB_DELIVERY_CONCURRENCY', 10),
    'remote_follow_enabled' => false,

    /*
    |--------------------------------------------------------------------------
    | Account file size limit
    |--------------------------------------------------------------------------
    |
    | Update the max account size, the per user limit of files in KB.
    |
    |
    */
    'max_account_size' => env('MAX_ACCOUNT_SIZE', 1000000),

    /*
    |--------------------------------------------------------------------------
    | Photo file size limit
    |--------------------------------------------------------------------------
    |
    | Update the max photo size, in KB.
    |
    */
    'max_photo_size' => env('MAX_PHOTO_SIZE', 15000),

    /*
    |--------------------------------------------------------------------------
    | Avatar file size limit
    |--------------------------------------------------------------------------
    |
    | Update the max avatar size, in KB.
    |
    */
    'max_avatar_size' => (int) env('MAX_AVATAR_SIZE', 2000),

    /*
    |--------------------------------------------------------------------------
    | Caption limit
    |--------------------------------------------------------------------------
    |
    | Change the caption length limit for new local posts.
    |
    */
    'max_caption_length' => env('MAX_CAPTION_LENGTH', 500),

    /*
    |--------------------------------------------------------------------------
    | Bio length limit
    |--------------------------------------------------------------------------
    |
    | Change the bio length limit for user profiles.
    |
    */
    'max_bio_length' => env('MAX_BIO_LENGTH', 125),

    /*
    |--------------------------------------------------------------------------
    | User name length limit
    |--------------------------------------------------------------------------
    |
    | Change the length limit for user names.
    |
    */
    'max_name_length' => env('MAX_NAME_LENGTH', 30),

    /*
    |--------------------------------------------------------------------------
    | Album size limit
    |--------------------------------------------------------------------------
    |
    | The max number of photos allowed per post.
    |
    */
    'max_album_length'  => env('MAX_ALBUM_LENGTH', 4),

    /*
    |--------------------------------------------------------------------------
    | Email Verification
    |--------------------------------------------------------------------------
    |
    | Require email verification before a new user can do anything.
    |
    */
    'enforce_email_verification'  => env('ENFORCE_EMAIL_VERIFICATION', true),

    /*
    |--------------------------------------------------------------------------
    | Image Quality
    |--------------------------------------------------------------------------
    |
    | Set the image optimization quality, must be a value between 1-100.
    |
    */
    'image_quality'  => (int) env('IMAGE_QUALITY', 80),

    /*
    |--------------------------------------------------------------------------
    | Account deletion
    |--------------------------------------------------------------------------
    |
    | Enable account deletion.
    |
    */
    'account_deletion' => env('ACCOUNT_DELETION', true),

    /*
    |--------------------------------------------------------------------------
    | Account deletion after X days
    |--------------------------------------------------------------------------
    |
    | Set account deletion queue after X days, set to false to delete accounts
    | immediately.
    |
    */
    'account_delete_after' => env('ACCOUNT_DELETE_AFTER', false),

    /*
    |--------------------------------------------------------------------------
    | Enable Cloud Storage
    |--------------------------------------------------------------------------
    |
    | Store media on object storage like S3, Digital Ocean Spaces, Rackspace
    |
    */
    'cloud_storage' => env('PF_ENABLE_CLOUD', false),

    /*
    |--------------------------------------------------------------------------
    | Max User Limit
    |--------------------------------------------------------------------------
    |
    | Allow a maximum number of user accounts. Default: off
    |
    */
    'max_users' => env('PF_MAX_USERS', false),

    /*
    |--------------------------------------------------------------------------
    | Optimize Images
    |--------------------------------------------------------------------------
    |
    | Resize and optimize image uploads. Default: on
    |
    */
    'optimize_image' => env('PF_OPTIMIZE_IMAGES', true),

    /*
    |--------------------------------------------------------------------------
    | Optimize Videos
    |--------------------------------------------------------------------------
    |
    | Resize and optimize video uploads. Default: on
    |
    */
    'optimize_video' => env('PF_OPTIMIZE_VIDEOS', true),

    /*
    |--------------------------------------------------------------------------
    | User invites
    |--------------------------------------------------------------------------
    |
    | Allow users to invite others via email. 
    | Will respect max user limit and prevent invites after the
    | limit is reached. Default: off
    |
    */ 
    'user_invites' => [
        'enabled' => false, //env('PF_USER_INVITES', false),
        'limit' => [
            'total' => (int) env('PF_USER_INVITES_TOTAL_LIMIT', 0),
            'daily' => (int) env('PF_USER_INVITES_DAILY_LIMIT', 0),
            'monthly' => (int) env('PF_USER_INVITES_MONTHLY_LIMIT', 0),
        ]
    ],


    'media_types' => env('MEDIA_TYPES', 'image/jpeg,image/png,image/gif'),

    'enforce_account_limit' => env('LIMIT_ACCOUNT_SIZE', true),

    'import' => [
        'instagram' => [
            'enabled' => false,
            'limits' => [
                'posts' => (int) env('IMPORT_INSTAGRAM_POST_LIMIT', 100),
                'size' => (int) env('IMPORT_INSTAGRAM_SIZE_LIMIT', 250)
            ]
        ]
    ],

    'oauth_enabled' => env('OAUTH_ENABLED', false),

    'sanitizer' => [
        'restrict_html_types' => env('RESTRICT_HTML_TYPES', true)
    ],

    'admin' => [
        'env_editor' => env('ADMIN_ENV_EDITOR', false)
    ],

    'links_per_post' => env('MAX_LINKS_PER_POST', 0) 
];
