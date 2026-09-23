<?php

/**
 * Schema metadata overlay for config:schema.
 *
 * Keys are Laravel dot-notation config keys.  Each entry may contain any of:
 *
 *   description  string        Human-readable explanation of the option.
 *   group        string        x-group category for UI generation.  Usually
 *                              auto-inferred from the key prefix; only set this
 *                              when the inference is wrong.
 *   type         string|array  JSON Schema type override.  Only needed for
 *                              DB-only cached keys (no env() call) whose type
 *                              cannot be inferred from a default value and would
 *                              otherwise fall back to "string".
 *   enum         array         Allowed values.
 *   minimum      int|float     Lower bound for numeric values.
 *   maximum      int|float     Upper bound for numeric values.
 *   format       string        JSON Schema format hint, e.g. "uri" or "email".
 *   env          string        Environment variable that sets this key.  Only
 *                              needed when the config file transforms the
 *                              `env()` result, as `explode()` and a ternary do,
 *                              because the extractor then cannot see which key
 *                              the variable belongs to.
 *   default      mixed         Default value.  Overrules the default that the
 *                              config file gives, which a `type` override can
 *                              make invalid.
 *   cast         string        PHP cast that goes with an `env` override: one of
 *                              "int", "bool", "float" or "string".
 *   sensitive    bool          Whether the value is a secret.  Overrules
 *                              `PROTECTED_KEYS` and the name patterns, for a
 *                              key that they do not classify correctly.
 *
 * Structural data (env var name, default value, x-config-cache, x-sensitive)
 * is derived automatically from the config PHP files and ConfigCacheService.
 * You do not need an entry here for a new option to appear in the schema.
 *
 * `config:schema` fails when a config file reads an environment variable that
 * no schema key covers, so an option cannot silently go missing.
 *
 * See CONFIGURATION_SCHEMA.md for the full workflow.
 */

return [

    // Application

    'app.previous_keys' => [
        'description' => 'Comma-separated list of previous `APP_KEY` values, for decryption after a key rotation.',
        'group' => 'application',
        'env' => 'APP_PREVIOUS_KEYS',
        'default' => '',
        'type' => 'string',
    ],

    'app.name' => [
        'description' => 'The name of your Pixelfed instance.',
        'group' => 'branding',
    ],
    'app.short_description' => [
        'description' => 'A short one-line description of the instance shown on the landing page.',
        'group' => 'branding',
    ],
    'app.description' => [
        'description' => 'A longer description of the instance.',
        'group' => 'branding',
    ],
    'app.url' => [
        'description' => 'The canonical HTTPS URL of this instance (no trailing slash).',
        'group' => 'application',
        'format' => 'uri',
    ],
    'app.env' => [
        'description' => 'Application environment.',
        'group' => 'application',
        'enum' => ['production', 'local', 'testing'],
    ],
    'app.debug' => [
        'description' => 'Enable detailed error pages. Must be false in production.',
        'group' => 'application',
    ],
    'app.banner_image' => [
        'description' => 'Path to the instance banner image shown on the landing page.',
        'group' => 'branding',
    ],
    'app.rules' => [
        'description' => 'JSON-encoded array of instance rules shown during registration.',
        'group' => 'branding',
    ],

    // Registration

    'pixelfed.open_registration' => [
        'description' => 'Allow new users to register an account.',
        'group' => 'registration',
    ],
    'pixelfed.enforce_email_verification' => [
        'description' => 'Require new users to verify their email address before using the site.',
        'group' => 'registration',
    ],
    'instance.curated_registration.enabled' => [
        'description' => 'Enable curated (filtered) registration: users apply and admins approve.',
        'group' => 'registration',
    ],
    'instance.curated_registration.resend_confirmation_limit' => [
        'description' => 'Maximum times an applicant may resend their confirmation email.',
        'group' => 'registration',
        'minimum' => 1,
    ],
    'instance.curated_registration.captcha_enabled' => [
        'description' => 'Require captcha during curated registration flow.',
        'group' => 'registration',
    ],

    // Media

    'pixelfed.max_photo_size' => [
        'description' => 'Maximum allowed size for a single photo upload, in kilobytes.',
        'group' => 'media',
        'minimum' => 1,
    ],
    'pixelfed.max_album_length' => [
        'description' => 'Maximum number of photos allowed in a single album post.',
        'group' => 'media',
        'minimum' => 1,
        'maximum' => 100,
    ],
    'pixelfed.image_quality' => [
        'description' => 'JPEG/WebP compression quality applied when optimizing images (1-100).',
        'group' => 'media',
        'minimum' => 1,
        'maximum' => 100,
    ],
    'pixelfed.media_types' => [
        'description' => 'Comma-separated list of allowed upload MIME types.',
        'group' => 'media',
    ],
    'pixelfed.optimize_image' => [
        'description' => 'Resize and compress image uploads on ingest.',
        'group' => 'media',
    ],
    'pixelfed.optimize_video' => [
        'description' => 'Transcode and optimize video uploads on ingest.',
        'group' => 'media',
    ],
    'pixelfed.max_avatar_size' => [
        'description' => 'Maximum allowed size for a profile avatar upload, in kilobytes.',
        'group' => 'media',
        'minimum' => 1,
    ],
    'pixelfed.max_altext_length' => [
        'description' => 'Maximum character length for image alt text.',
        'group' => 'media',
        'minimum' => 1,
    ],
    'pixelfed.max_collection_length' => [
        'description' => 'Maximum number of posts in a user collection.',
        'group' => 'media',
        'minimum' => 1,
    ],
    'pixelfed.memory_limit' => [
        'description' => 'PHP memory_limit used exclusively during image processing jobs.',
        'group' => 'media',
    ],
    'media.delete_local_after_cloud' => [
        'description' => 'Delete local copies of media files after successfully uploading them to cloud storage.',
        'group' => 'media',
    ],
    'media.hls.enabled' => [
        'description' => 'Enable HLS streaming support for videos (requires FFmpeg).',
        'group' => 'media',
    ],
    'media.hls.p2p' => [
        'description' => 'Enable WebTorrent P2P video delivery (requires HLS).',
        'group' => 'media',
    ],

    // Content limits

    'purify.configs.default.HTML.Allowed' => [
        'description' => 'Restrict the HTML tags that are allowed in remote content.',
        'group' => 'content',
        'env' => 'RESTRICT_HTML_TYPES',
        'default' => true,
        'type' => 'boolean',
    ],

    'pixelfed.max_caption_length' => [
        'description' => 'Maximum character length for post captions.',
        'group' => 'content',
        'minimum' => 1,
    ],
    'pixelfed.max_bio_length' => [
        'description' => 'Maximum character length for user profile bios.',
        'group' => 'content',
        'minimum' => 1,
    ],
    'pixelfed.max_name_length' => [
        'description' => 'Maximum character length for display names.',
        'group' => 'content',
        'minimum' => 1,
    ],
    'pixelfed.min_password_length' => [
        'description' => 'Minimum character length for user passwords.',
        'group' => 'content',
        'minimum' => 6,
    ],

    // Accounts

    'instance.username.remote.format' => [
        'description' => 'How to show the domain of a remote account: `@` for `@user@domain`, `from` for `user from domain`, or `custom` for the format of the theme.',
        'env' => 'USERNAME_REMOTE_FORMAT',
        'default' => '@',
        'type' => 'string',
        'enum' => ['@', 'from', 'custom'],
    ],

    'pixelfed.max_account_size' => [
        'description' => 'Per-user storage quota in kilobytes.',
        'group' => 'accounts',
        'minimum' => 1,
    ],
    'pixelfed.enforce_account_limit' => [
        'description' => 'Enforce the per-user storage quota.',
        'group' => 'accounts',
    ],
    'pixelfed.max_users' => [
        'description' => 'Maximum number of local user accounts allowed on this instance.',
        'group' => 'accounts',
        'minimum' => 1,
    ],
    'pixelfed.enforce_max_users' => [
        'description' => 'Stop accepting registrations once the user limit is reached.',
        'group' => 'accounts',
    ],
    'account.autofollow' => [
        // DB-only: no env() call; type cannot be inferred from a default, so override it.
        'description' => 'Automatically follow a set of accounts when a new user registers.',
        'group' => 'accounts',
        'type' => 'boolean',
    ],
    'account.autofollow_usernames' => [
        'description' => 'Comma-separated list of local usernames that new users will automatically follow.',
        'group' => 'accounts',
    ],
    'instance.user_filters.max_user_blocks' => [
        'description' => 'Maximum number of accounts a user can block.',
        'group' => 'accounts',
        'minimum' => 0,
    ],
    'instance.user_filters.max_user_mutes' => [
        'description' => 'Maximum number of accounts a user can mute.',
        'group' => 'accounts',
        'minimum' => 0,
    ],
    'instance.user_filters.max_domain_blocks' => [
        'description' => 'Maximum number of domains a user can block.',
        'group' => 'accounts',
        'minimum' => 0,
    ],

    // API / OAuth

    'sanctum.stateful' => [
        'description' => 'Comma-separated domains that get stateful API authentication cookies.',
        'group' => 'api',
        'env' => 'SANCTUM_STATEFUL_DOMAINS',
        'type' => 'string',
    ],

    'cors.allowed_origins' => [
        'description' => 'Comma-separated origins that can make cross-origin requests. Each origin can use a wildcard, as `*.example.org` does.',
        'group' => 'api',
        'env' => 'PF_CORS_ALLOWED_ORIGINS',
        'default' => '',
        'type' => 'string',
    ],

    'pixelfed.oauth_enabled' => [
        'description' => 'Enable OAuth2 / Mastodon-compatible mobile APIs.',
        'group' => 'api',
    ],
    'pixelfed.allow_app_registration' => [
        'description' => 'Allow third-party apps to register OAuth clients via the API.',
        'group' => 'api',
    ],
    'pixelfed.app_registration_rate_limit_attempts' => [
        'description' => 'Maximum app-registration attempts before rate-limiting kicks in.',
        'group' => 'api',
        'minimum' => 1,
    ],
    'pixelfed.app_registration_rate_limit_decay' => [
        'description' => 'Rate-limit decay window for app registrations, in seconds.',
        'group' => 'api',
        'minimum' => 1,
    ],
    'pixelfed.app_registration_confirm_rate_limit_attempts' => [
        'description' => 'Maximum confirmation attempts before rate-limiting kicks in.',
        'group' => 'api',
        'minimum' => 1,
    ],
    'pixelfed.app_registration_confirm_rate_limit_decay' => [
        'description' => 'Rate-limit decay window for app registration confirmations, in seconds.',
        'group' => 'api',
        'minimum' => 1,
    ],
    'instance.oauth.token_expiration' => [
        'description' => 'OAuth access token lifetime in days.',
        'group' => 'api',
        'minimum' => 1,
    ],
    'instance.oauth.refresh_expiration' => [
        'description' => 'OAuth refresh token lifetime in days.',
        'group' => 'api',
        'minimum' => 1,
    ],

    // Features

    'instance.stories.enabled' => [
        'description' => 'Enable ephemeral Stories feature.',
        'group' => 'features',
    ],
    'pixelfed.import.instagram.enabled' => [
        'description' => 'Allow users to import their Instagram archive.',
        'group' => 'features',
    ],
    'pixelfed.bouncer.enabled' => [
        'description' => 'Enable the Bouncer spam/abuse detection system.',
        'group' => 'features',
    ],
    'autospam.nlp.enabled' => [
        // DB-only: hardcoded false in config/autospam.php, no env() call.
        'description' => 'Enable NLP-based autospam detection.',
        'group' => 'features',
        'type' => 'boolean',
    ],
    'instance.embed.profile' => [
        'description' => 'Allow profile pages to be embedded in external sites.',
        'group' => 'features',
    ],
    'instance.embed.post' => [
        'description' => 'Allow individual posts to be embedded in external sites.',
        'group' => 'features',
    ],
    'instance.has_legal_notice' => [
        'description' => 'Display a legal notice link in the footer.',
        'group' => 'features',
    ],
    'instance.avatar.local_to_cloud' => [
        'description' => 'Store local user avatars on cloud/S3 storage.',
        'group' => 'features',
    ],
    'instance.restricted.enabled' => [
        'description' => 'Run as a restricted (private) instance where content is not publicly visible.',
        'group' => 'features',
    ],

    // Discovery

    'instance.landing.show_directory' => [
        'description' => 'Show the user directory section on the landing page.',
        'group' => 'discovery',
    ],
    'instance.landing.show_explore' => [
        'description' => 'Show the explore/trending section on the landing page.',
        'group' => 'discovery',
    ],
    'instance.discover.public' => [
        'description' => 'Make the Discover page publicly accessible to logged-out visitors.',
        'group' => 'discovery',
    ],
    'instance.timeline.local.is_public' => [
        'description' => 'Make the local timeline publicly accessible to logged-out visitors.',
        'group' => 'discovery',
    ],
    'instance.show_peers' => [
        'description' => 'Expose the list of federated peer instances via the API.',
        'group' => 'discovery',
    ],

    // Branding

    'instance.admin.pid' => [
        // type override: DB-only integer that can also be null (unset).
        'description' => 'Profile ID of the primary admin account shown on the landing page.',
        'group' => 'branding',
        'type' => ['integer', 'null'],
    ],
    'instance.banner.blurhash' => [
        'description' => 'BlurHash placeholder string for the instance banner image.',
        'group' => 'branding',
    ],
    'uikit.custom.css' => [
        'description' => 'Custom CSS injected into every page.',
        'group' => 'branding',
    ],
    'uikit.custom.js' => [
        'description' => 'Custom JavaScript injected into every page.',
        'group' => 'branding',
    ],
    'uikit.show_custom.css' => [
        // DB-only boolean; no env() call so type cannot be inferred.
        'description' => 'Enable injection of the custom CSS snippet.',
        'group' => 'branding',
        'type' => 'boolean',
    ],
    'uikit.show_custom.js' => [
        // DB-only boolean; no env() call so type cannot be inferred.
        'description' => 'Enable injection of the custom JavaScript snippet.',
        'group' => 'branding',
        'type' => 'boolean',
    ],

    // Federation

    'costar.domain.block' => [
        'description' => 'Comma-separated domains to block. Content from these domains is rejected.',
        'group' => 'federation',
        'env' => 'CS_BLOCKED_DOMAINS',
        'type' => 'string',
    ],

    'costar.domain.cw' => [
        'description' => 'Comma-separated domains whose content gets a content warning.',
        'group' => 'federation',
        'env' => 'CS_CW_DOMAINS',
        'type' => 'string',
    ],

    'costar.domain.unlisted' => [
        'description' => 'Comma-separated domains whose content is removed from public timelines.',
        'group' => 'federation',
        'env' => 'CS_UNLISTED_DOMAINS',
        'type' => 'string',
    ],

    'costar.keyword.block' => [
        'description' => 'Comma-separated keywords to block. Content with these keywords is rejected.',
        'group' => 'federation',
        'env' => 'CS_BLOCKED_KEYWORDS',
        'type' => 'string',
    ],

    'costar.keyword.cw' => [
        'description' => 'Comma-separated keywords that give content a content warning.',
        'group' => 'federation',
        'env' => 'CS_CW_KEYWORDS',
        'type' => 'string',
    ],

    'costar.keyword.unlisted' => [
        'description' => 'Comma-separated keywords that remove content from public timelines.',
        'group' => 'federation',
        'env' => 'CS_UNLISTED_KEYWORDS',
        'type' => 'string',
    ],

    'costar.actor.block' => [
        'description' => 'Comma-separated remote actor URIs to block. Content from these actors is rejected.',
        'group' => 'federation',
        'env' => 'CS_BLOCKED_ACTOR',
        'type' => 'string',
    ],

    'costar.actor.cw' => [
        'description' => 'Comma-separated remote actor URIs whose content gets a content warning.',
        'group' => 'federation',
        'env' => 'CS_CW_ACTOR',
        'type' => 'string',
    ],

    'costar.actor.unlisted' => [
        'description' => 'Comma-separated remote actor URIs whose content is removed from public timelines.',
        'group' => 'federation',
        'env' => 'CS_UNLISTED_ACTOR',
        'type' => 'string',
    ],

    'federation.activitypub.enabled' => [
        'description' => 'Enable ActivityPub federation with other Fediverse instances.',
        'group' => 'federation',
    ],
    'federation.activitypub.authorized_fetch' => [
        'description' => 'Require HTTP Signatures on all incoming ActivityPub fetch requests (ghost mode).',
        'group' => 'federation',
    ],
    'federation.migration' => [
        'description' => 'Allow users to migrate their account to/from other ActivityPub instances.',
        'group' => 'federation',
    ],
    'federation.custom_emoji.enabled' => [
        'description' => 'Enable support for custom emoji from remote instances.',
        'group' => 'federation',
    ],
    'federation.network_timeline' => [
        'description' => 'Enable the federated network timeline.',
        'group' => 'federation',
    ],
    'federation.activitypub.remoteFollow' => [
        'description' => 'Enable the remote-follow / interact dialog for federated users.',
        'group' => 'federation',
    ],
    'federation.activitypub.delivery.timeout' => [
        'description' => 'HTTP timeout in seconds for ActivityPub delivery requests.',
        'group' => 'federation',
        'minimum' => 1,
    ],
    'federation.activitypub.delivery.concurrency' => [
        'description' => 'Number of concurrent ActivityPub delivery workers.',
        'group' => 'federation',
        'minimum' => 1,
    ],

    // Captcha

    'captcha.enabled' => [
        'description' => 'Enable hCaptcha globally.',
        'group' => 'captcha',
    ],
    'captcha.sitekey' => [
        'description' => 'hCaptcha site key (public, used in the browser widget).',
        'group' => 'captcha',
        // The browser widget gets the site key, so the value is not a secret.
        // It is in `PROTECTED_KEYS` only because the admin panel encrypts it
        // together with the secret.
        'sensitive' => false,
    ],
    'captcha.secret' => [
        'description' => 'hCaptcha secret key (private, used for server-side verification).',
        'group' => 'captcha',
    ],
    'captcha.active.login' => [
        'description' => 'Show captcha on the login form.',
        'group' => 'captcha',
    ],
    'captcha.active.register' => [
        'description' => 'Show captcha on the registration form.',
        'group' => 'captcha',
    ],
    'captcha.triggers.login.enabled' => [
        'description' => 'Automatically show captcha on login after N failed attempts.',
        'group' => 'captcha',
    ],
    'captcha.triggers.login.attempts' => [
        'description' => 'Number of failed login attempts that trigger the captcha.',
        'group' => 'captcha',
        'minimum' => 1,
    ],

    // Storage

    'pixelfed.cloud_storage' => [
        'description' => 'Store uploaded media on cloud/S3-compatible object storage instead of locally.',
        'group' => 'storage',
    ],
    'filesystems.cloud' => [
        'description' => 'Which cloud filesystem driver to use when cloud storage is enabled.',
        'group' => 'storage',
        'enum' => ['s3', 'spaces'],
    ],
    'filesystems.disks.s3.key' => [
        'description' => 'AWS / S3-compatible access key ID.',
        'group' => 'storage',
    ],
    'filesystems.disks.s3.secret' => [
        'description' => 'AWS / S3-compatible secret access key.',
        'group' => 'storage',
    ],
    'filesystems.disks.s3.region' => [
        'description' => 'AWS region (e.g. us-east-1).',
        'group' => 'storage',
    ],
    'filesystems.disks.s3.bucket' => [
        'description' => 'S3 bucket name.',
        'group' => 'storage',
    ],
    'filesystems.disks.s3.visibility' => [
        'description' => 'Default object visibility in S3.',
        'group' => 'storage',
        'enum' => ['public', 'private'],
    ],
    'filesystems.disks.s3.url' => [
        'description' => 'Custom public URL prefix for S3 objects (CDN or path rewrite).',
        'group' => 'storage',
    ],
    'filesystems.disks.s3.endpoint' => [
        'description' => 'Custom S3-compatible API endpoint (e.g. for MinIO or Backblaze B2).',
        'group' => 'storage',
    ],
    'filesystems.disks.s3.use_path_style_endpoint' => [
        'description' => 'Use path-style S3 URLs instead of subdomain-style (required for MinIO).',
        'group' => 'storage',
    ],
    'filesystems.disks.spaces.key' => [
        'description' => 'DigitalOcean Spaces access key.',
        'group' => 'storage',
    ],
    'filesystems.disks.spaces.secret' => [
        'description' => 'DigitalOcean Spaces secret key.',
        'group' => 'storage',
    ],
    'filesystems.disks.spaces.region' => [
        'description' => 'DigitalOcean Spaces region.',
        'group' => 'storage',
    ],
    'filesystems.disks.spaces.bucket' => [
        'description' => 'DigitalOcean Spaces bucket name.',
        'group' => 'storage',
    ],
    'filesystems.disks.spaces.endpoint' => [
        'description' => 'DigitalOcean Spaces endpoint URL.',
        'group' => 'storage',
    ],
    'filesystems.disks.spaces.url' => [
        'description' => 'Public CDN URL prefix for DigitalOcean Spaces objects.',
        'group' => 'storage',
    ],
    'filesystems.disks.spaces.visibility' => [
        // DB-only: hardcoded 'public' in config/filesystems.php, no env() call.
        'description' => 'Default object visibility for DigitalOcean Spaces.',
        'group' => 'storage',
        'type' => 'string',
        'enum' => ['public', 'private'],
    ],
    'filesystems.disks.spaces.use_path_style_endpoint' => [
        // DB-only: not present in config/filesystems.php; registered in ConfigCacheService.
        'description' => 'Use path-style URLs for DigitalOcean Spaces.',
        'group' => 'storage',
        'type' => 'boolean',
    ],

    // Notifications

    'instance.contact.enabled' => [
        'description' => 'Enable the public contact form.',
        'group' => 'notifications',
    ],
    'instance.email' => [
        'description' => 'Public contact email address shown in the instance metadata.',
        'group' => 'notifications',
        'format' => 'email',
    ],
    'instance.reports.email.enabled' => [
        'description' => 'Send email notifications to admins when a new report is filed.',
        'group' => 'notifications',
    ],
    'instance.reports.email.to' => [
        'description' => 'Comma-separated email addresses that receive report notifications.',
        'group' => 'notifications',
    ],

    // Internal / stats

    'instance.stats.total_local_posts' => [
        // DB-only integer maintained by the application; not user-configurable.
        'description' => 'Cached count of total local posts (managed internally).',
        'group' => 'application',
        'type' => 'integer',
    ],

    'instance.notifications.nag.api_key' => [
        // The default in `config/instance.php` is `false`, so the type must be
        // set here for the key to read as a credential.
        'description' => 'API key for the Pixelfed push gateway.',
        'group' => 'notifications',
        'type' => 'string',
        'default' => '',
    ],

    // Infrastructure

    // The `cache` and `default` connections give `REDIS_PORT` a string
    // default, and the other connections that read it give an integer. One
    // environment variable must have one type.
    'database.redis.cache.port' => [
        'type' => 'integer',
        'default' => 6379,
    ],
    'database.redis.default.port' => [
        'type' => 'integer',
        'default' => 6379,
    ],

    'logging.channels.stack.channels' => [
        'description' => 'Comma-separated log channels that the `stack` channel writes to.',
        'group' => 'application',
        'env' => 'LOG_STACK',
        'default' => 'daily',
        'type' => 'string',
    ],

    'pulse.recorders.Laravel\Pulse\Recorders\Servers.directories' => [
        'description' => 'Colon-separated directories that the server recorder measures the disk use of.',
        'group' => 'application',
        'env' => 'PULSE_SERVER_DIRECTORIES',
        'default' => '/',
        'type' => 'string',
    ],

];
