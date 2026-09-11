# Release Notes

## [Unreleased](https://github.com/pixelfed/pixelfed/compare/v0.12.11...dev)

## [v0.12.10 (2026-09-11)](https://github.com/pixelfed/pixelfed/compare/v0.12.10...dev)

-   Change the collation for the hashtags table ([53e5692cf](https://github.com/pixelfed/pixelfed/commit/53e5692cfb5de282d9c541f03868f458e7ce2a6b))
    -   Changes the collation for the hashtags table to utf8mb4_unicode_520_ci
    -   when the driver is MySQL or MariaDB. Otherwise the default collation of
    -   utf8mb4_unicode_ci is used which conflates all characters outside of the
    -   basic multilingual plane. This meant that any hashtag that is using a
    -   script not in the BMP ends up matching with any other hashtag with a
    -   script outside the BMP if it has the same length.
-   French translation of the site pages ([ca273cff0](https://github.com/pixelfed/pixelfed/commit/ca273cff017565eabe0d04b6449bf2ce0452f10d))
-   New translations web.php (Chinese Simplified) \[ci skip\] ([f88d0db45](https://github.com/pixelfed/pixelfed/commit/f88d0db4563c772b13ea3d551bdd8033c8e15d7b))
-   New translations web.php (Occitan) \[ci skip\] ([52a0e4120](https://github.com/pixelfed/pixelfed/commit/52a0e41201a3f589320e8ab101039b4d557b47e9))
-   New translations web.php (Occitan) \[ci skip\] ([6aee03c9a](https://github.com/pixelfed/pixelfed/commit/6aee03c9a51dbc113fc1854360d1135130495d20))
-   fix: apply EXIF orientation before resizing portrait images ([4f13ebfe3](https://github.com/pixelfed/pixelfed/commit/4f13ebfe3f34ad17742abd0f27dfe2202a3395c8))
    -   Smartphone photos stored in landscape orientation with an EXIF rotation
    -   tag were being saved to S3 in the wrong orientation. Image.php read the
    -   raw pixel dimensions without first applying the EXIF tag, so portrait
    -   photos (e.g. 4032×3024 with Orientation=6) were classified and resized
    -   as landscape (1920×1080).
    -   Calling orient() immediately after read() physically rotates the image
    -   to match its EXIF orientation tag before any dimension checks or
    -   scaling. This ensures portrait photos remain portrait after processing.
    -   Intervention Image v3 reference:
    -   https://image.intervention.io/v3/modifying/orientation
-   New translations web.php (Portuguese, Brazilian) ([b7ff17d3d](https://github.com/pixelfed/pixelfed/commit/b7ff17d3dc596f2d72601e42ba70cae7c9bd8ff0))
    -   \[ci skip\]
    -   \[ci skip\]
-   feat: Spanish translation ([7afc700f6](https://github.com/pixelfed/pixelfed/commit/7afc700f64934f6650e901f8877cabbd5d4f374c))
-   feat: Spanish translation ([354afc79c](https://github.com/pixelfed/pixelfed/commit/354afc79cba3330bd41bf5cf04e5584ee6c6bcf1))
-   Fix videos never reaching cloud storage by downscaling in Blurhash ([ef56880a7](https://github.com/pixelfed/pixelfed/commit/ef56880a74a87cc5717f74971bfb52b6e010723b))
    -   Blurhash::generate() allocates one PHP array per pixel of the source. At
    -   roughly 255 bytes per pixel (measured: 224 MB peak for a 720x1280 frame) a
    -   1920x1080 frame approaches half a gigabyte.
    -   Image thumbnails survive this because they are capped at 640x640 in
    -   Image::\_\_construct() _and_ run under that constructor's
    -   ini_set('memory_limit', '1024M'). Video thumbnails get neither: FFmpeg saves
    -   them at the source video's resolution, and VideoThumbnail never raises the
    -   limit. So a video whose frame is 1080p or larger exhausts memory_limit.
    -   That is a PHP fatal, not an \\Exception, which has three consequences:
    -   the catch block in VideoThumbnail::handle() does not catch it
    -   the job never lands in failed_jobs, so nothing reports a problem
    -   MediaStoragePipeline::dispatch() on the last line of handle() never runs
    -   The video therefore stays on local disk permanently while images beside it
    -   replicate normally. Reported in #2652 (2021-02-13) and diagnosed correctly in
    -   that thread on 2021-11-04.
    -   Two changes:
    -   1. Blurhash::generate() downscales to 128px on the long edge before sampling.
    -   The result is a 4x4-component DCT, so full-resolution sampling adds
    -   essentially nothing: measured against the full-resolution hash, mean
    -   per-channel deviation of the decoded 24x24 preview is ~7.5/255 at a 32px
    -   sample, ~4.5/255 at 64px, ~2.5/255 at 128px, and no better at 256px. Peak
    -   memory for the frame above drops from 224 MB to 6 MB.
    -   This removes the ceiling for every caller rather than moving it, which is
    -   all that raising memory_limit would have done. Existing stored hashes are
    -   not recomputed, so nothing already published changes appearance.
    -   2. VideoThumbnail wraps the blurhash in its own try/catch, so a decorative
    -   step can no longer skip the replication dispatch. Change 1 covers the
    -   fatal; this covers any ordinary exception.
    -   Verified on a live instance with S3 cloud storage: a 1920x1080 video that
    -   previously stranded now generates a blurhash, uploads original and thumbnail
    -   to the bucket, sets cdn_url/thumbnail_url/replicated_at, and removes the local
    -   copies. Existing images re-hash to visually identical previews.
-   Update Kernel.php ([3800612fd](https://github.com/pixelfed/pixelfed/commit/3800612fdfc9a48a4327c0421facb2589a83617e))
-   Update allowed routes for restricted access middleware ([f6f9d5368](https://github.com/pixelfed/pixelfed/commit/f6f9d5368cb30e5117d4f1ff4eb6126de0595b47))
-   Create RestrictedAccessMiddlewareTest.php ([508b57337](https://github.com/pixelfed/pixelfed/commit/508b5733715e5a54100f287c45c3b1cd75af39ae))
-   Refactor admin notification logic in pipeline ([ceba5af03](https://github.com/pixelfed/pixelfed/commit/ceba5af03f6befb31a5e896b2c69935bff4dc62f))
-   Update CuratedOnboardingNotifyAdminNewApplicationPipeline.php ([a8bbbcd0e](https://github.com/pixelfed/pixelfed/commit/a8bbbcd0ee28c8e82a8b6af6e1d303254921800a))
-   Create CuratedOnboardingNotifyAdminTest.php ([da0805c45](https://github.com/pixelfed/pixelfed/commit/da0805c455dcb5b9bbe7fe611432d28969794497))
-   Update auth.php ([d9e55199f](https://github.com/pixelfed/pixelfed/commit/d9e55199f51d4af5c8aeee1e668afbf58f3b6cff))
-   Replace jenssegers/agent with matomo/device-detector ([651f0de74](https://github.com/pixelfed/pixelfed/commit/651f0de74faf414f56c6d3297e10636e82479cd0))
    -   Remove unmaintained jenssegers/agent package (no releases since 2021)
    -   Add matomo/device-detector v6.5 as actively maintained replacement
    -   Create App\\Services\\UserAgentService wrapper for drop-in compatibility
    -   Update UserDevice model and ApiV1Dot1Controller to use new service
-   Fix PSR-4 autoload: rename Webfinger.php to WebFinger.php ([47e280b90](https://github.com/pixelfed/pixelfed/commit/47e280b90dd2b02846aa88d209b5e5cb6b65d29b))
    -   The class is App\\Rules\\WebFinger but the file was named Webfinger.php,
    -   causing a PSR-4 compliance warning during autoload generation.
-   Apply Pint formatting to tests/ ([de3375a9f](https://github.com/pixelfed/pixelfed/commit/de3375a9f9f7c334c7c81bba20522733d5c6566c))
-   Apply Pint formatting to resources/ ([e8d6a48cd](https://github.com/pixelfed/pixelfed/commit/e8d6a48cddea439c8c664d4308d3c4843552f46d))
-   Apply Pint formatting to bootstrap/ ([3b0fd708c](https://github.com/pixelfed/pixelfed/commit/3b0fd708c8814dc848abafd17f46d12800c001c5))
-   Apply Pint formatting to public/ ([de965d410](https://github.com/pixelfed/pixelfed/commit/de965d41065faf1774974b72b8c5dd83e29f2823))
-   Adopt short array syntax ([4c7780944](https://github.com/pixelfed/pixelfed/commit/4c77809444db6e6330db07e664aa49510b9ea250))
    -   Since PHP 5.4 the short array syntax `\[\]` may be used instead of `array()`.
-   Convert string references to `::class` ([19880c2ff](https://github.com/pixelfed/pixelfed/commit/19880c2ffb54899f90d000a91a85b393ebb23da7))
    -   PHP 5.5.9 adds the new static `class` property which provides the fully qualified class name. This is preferred over using strings for class names since the `class` property references are checked by PHP.
-   Create pint.json ([3950ace9a](https://github.com/pixelfed/pixelfed/commit/3950ace9acc7a640baf0ae7730708ceef3af3a51))
-   Add linting scripts to composer.json ([64eb52596](https://github.com/pixelfed/pixelfed/commit/64eb52596bb80396556ced54ee60bc7e3b550ecf))
-   Fix first follower/following record excluded from API responses ([396cf2d86](https://github.com/pixelfed/pixelfed/commit/396cf2d86164406059372840c6bc14ad306b35dd))
    -   Fixes #6695
    -   When no pagination params are provided, the default min_id was set to 1
    -   and the query used 'id > 1', which excluded the very first follower row
    -   (id=1) on fresh instances.
    -   Changed default min_id from 1 to 0 and switched the direction check from
    -   truthy evaluation to !== null, so the query becomes 'id > 0' which
    -   correctly includes all records.
-   Show detailed upload error messages instead of generic error ([5eda13081](https://github.com/pixelfed/pixelfed/commit/5eda1308171368e3ef642e78c9e6e2fd6c6e0c4c))
    -   Fixes #6657
    -   When media uploads fail with a 422 validation error (e.g. file too large),
    -   the error dialog now shows the actual validation message including the
    -   filename, instead of the generic 'An unexpected error occurred.'
    -   Example: 'DSCF0273.JPG: The file may not be greater than 15000 kilobytes'
    -   Also improved the default error case to surface server-provided messages
    -   when available. Applied to both ComposeModal and ComposeClassic components.
-   Fix OAuth scope bypass on remove_from_followers endpoint ([822e9c98c](https://github.com/pixelfed/pixelfed/commit/822e9c98cb67be107ade2cfa4fd214ab2bf298a3))
    -   Fixes #6643
    -   The POST /api/v1/accounts/{id}/remove_from_followers endpoint was missing
    -   the token existence check (! $request->user()->token()). While the
    -   tokenCan('follow') scope check was already present, the missing token
    -   guard meant unauthenticated token-less requests could potentially bypass
    -   the scope enforcement.
    -   Added the standard guard pattern consistent with accountFollowById and
    -   accountUnfollowById endpoints.
    -   Also adds tests verifying:
    -   Read-only tokens are denied (403)
    -   Follow-scoped tokens succeed (200)
    -   Unauthenticated requests are denied (403)
-   Delete tests/Feature/Api/RemoveFollowerScopeTest.php ([906e3514c](https://github.com/pixelfed/pixelfed/commit/906e3514c69e955289b3b5e7ab34649cf852fdcc))
-   Fix OAuth client secret not displayed after creation ([655d71ba5](https://github.com/pixelfed/pixelfed/commit/655d71ba5ce1dcc5a94bd03f345515eef1f68040))
    -   Fixes #6630 (partial — client secret issue)
    -   In Passport v13, client secrets are hashed at the model level and only
    -   available as plain_secret on the response from the creation endpoint.
    -   The previous code immediately re-fetched the client list after creation,
    -   losing the plain secret since it's not stored or returned on GET.
    -   Changes:
    -   Capture plain_secret from the POST response
    -   Show a dedicated modal with the client ID and secret after creation
    -   Warn users to copy the secret immediately (it won't be shown again)
    -   Add a Copy button for convenience
    -   Show 'Hidden (only shown at creation)' in the table for existing clients
-   Handle PAT creation gracefully when not configured ([1ab677a52](https://github.com/pixelfed/pixelfed/commit/1ab677a526eafb65c4569e5d9a27245f27780293))
    -   Fixes #6630 (partial — PAT 500 error)
    -   Previously, POST /oauth/personal-access-tokens would throw an unhandled
    -   RuntimeException (HTTP 500) when:
    -   OAUTH_PAT_ENABLED is false (the default), or
    -   No personal access client exists in the database
    -   Now the endpoint:
    -   1. Returns 403 with a clear message if PAT is disabled in config
    -   2. Catches RuntimeException from the token factory and returns 500
    -   with an actionable error message instead of a stack trace
-   Prevent deletion of personal access OAuth client ([53759e3ad](https://github.com/pixelfed/pixelfed/commit/53759e3ad64d2492893e503ae604b8d44592dd92))
    -   Fixes #6630 (partial — deletion causing broken PAT)
    -   If a user deletes the OAuth client that serves as the personal access
    -   client, all PAT creation breaks for the entire instance with a 500 error.
    -   Changes:
    -   Add custom OAuthClientController@destroy that checks if the client
    -   has the personal_access grant type before allowing deletion
    -   Returns 403 with a clear error message if deletion is blocked
    -   Add confirmation dialog before client deletion in the frontend
    -   Add error handling to show server error messages to the user
    -   This prevents accidental destruction of the PAT infrastructure.
-   Install Larastan for static analysis ([9a9726af7](https://github.com/pixelfed/pixelfed/commit/9a9726af7c6edd745df6d86cd95c5d20afc7b86b))
    -   Add larastan/larastan v3.10 (dev dependency)
    -   Configure phpstan.neon at level 0 with Laravel extension
    -   Generate baseline for existing errors (711 items)
    -   Exclude files with missing class references
    -   Fix one non-ignorable return type error in BearerTokenResponse
    -   Add composer analyse script
    -   Usage: composer analyse
-   Create php-larastan.yml ([c840d6bd7](https://github.com/pixelfed/pixelfed/commit/c840d6bd713e36fa04703240e16e5449d7337232))
-   polish ([0ce89e9f9](https://github.com/pixelfed/pixelfed/commit/0ce89e9f958e1686741145eaac4fce7f5e8a825b))
-   Delete app/Comment.php ([f1ca0340e](https://github.com/pixelfed/pixelfed/commit/f1ca0340e7cf13c3a466bde1abc4fa17c9eb8a7d))
-   Use Mastodon username convention for OIDC ([9d0b5949e](https://github.com/pixelfed/pixelfed/commit/9d0b5949e969911fa8bd84baeb7e01a41443ec10))
-   Update Inbox, fixes #6784 ([ce64d0961](https://github.com/pixelfed/pixelfed/commit/ce64d0961dbe7c75f7a6c2f42c38a6c73be2afb5))
-   Fix validateUrl() ([30085e870](https://github.com/pixelfed/pixelfed/commit/30085e8708c7ebfc64354595f61ce330a1fa7ad2))
-   Add GitHub Actions workflow for Docker image build ([f6baba9b1](https://github.com/pixelfed/pixelfed/commit/f6baba9b132c1e15924627b1ed9d1954ccf214ea))
-   Add GitHub Actions workflow for Docker tagged release ([8cecf38cf](https://github.com/pixelfed/pixelfed/commit/8cecf38cfe77b97786a70f273341a6a8bed1a165))
-   Update docker-push.yml ([aa72592ac](https://github.com/pixelfed/pixelfed/commit/aa72592ac0873e240a2fae9dd66cf064b8d8355d))
-   Add GitHub Actions workflow for PHP Pint linting ([e8b18f669](https://github.com/pixelfed/pixelfed/commit/e8b18f66901b1762f2018b980b7ec7dc716262ef))
-   Add 'unstable' branch to workflow and update PHP version ([699b8af2d](https://github.com/pixelfed/pixelfed/commit/699b8af2d5d534f7665bcdec4fae13f8fecaa3d8))
-   Fix ApiV1Controller, ensure follow notifications have an account ([e1235dfd7](https://github.com/pixelfed/pixelfed/commit/e1235dfd75f1eb4e853fa751fe178d7c3330c5e3))
-   Lint ([91645faee](https://github.com/pixelfed/pixelfed/commit/91645faeee78ce401f5bc24bc7f4baa8f507b4f4))
-   Update changelog ([8a728fc0d](https://github.com/pixelfed/pixelfed/commit/8a728fc0dde1875132c5e31619872268cbfc5f86))
-   Update Docker workflow to include unstable branch ([e53917f04](https://github.com/pixelfed/pixelfed/commit/e53917f047b8fe1de72180d87e977b64b934aa56))
-   Enhance Docker workflow with concurrency and platforms ([8e3a37553](https://github.com/pixelfed/pixelfed/commit/8e3a375534cc15cf1f227cdbacd8058fef51fbd4))
-   Create docker-ghcr-cleanup.yml ([d4d74a96f](https://github.com/pixelfed/pixelfed/commit/d4d74a96f386ac2d7ba29ead817b4cdd9cd9740b))
-   Rename workflow for GHCR container image cleanup ([7bf4e64d9](https://github.com/pixelfed/pixelfed/commit/7bf4e64d957b5cda455fd9a8a564974535f4e449))
-   Upgrade images to v4 ([552a55c2d](https://github.com/pixelfed/pixelfed/commit/552a55c2d2322d3dd8377c921a2ae29a774afcfa))
-   Update .gitignore ([6798175a8](https://github.com/pixelfed/pixelfed/commit/6798175a80d5cd824c919135738ec1ce66d346e7))
-   Add IMAGE_DRIVER option to .env.example ([ec2b758bc](https://github.com/pixelfed/pixelfed/commit/ec2b758bc5e0dbb9dab27f5f147d352235fdb30c))
-   Update .env.docker.example ([8b9d1c12a](https://github.com/pixelfed/pixelfed/commit/8b9d1c12a5bda68c230652e02d8cea1d81732d4d))
-   Update .env.testing ([6eec737b7](https://github.com/pixelfed/pixelfed/commit/6eec737b78b03669ee8963164b658df4c3c801cc))
-   Apply pint formatting to resources/ ([78b2bc323](https://github.com/pixelfed/pixelfed/commit/78b2bc3235a62d4f1f0ea2062d20c168e75b8b08))
-   Update and rename laravel.yml to php-laravel-tests.yml ([580042f36](https://github.com/pixelfed/pixelfed/commit/580042f36d57415c827a610ad3f8d7c2d4d56afe))
-   Update CHANGELOG.md ([0d4269017](https://github.com/pixelfed/pixelfed/commit/0d426901792cd80dbf440fa7773b1b6299f3625a))
-   Create SeedDevUsers.php ([16b5ffff1](https://github.com/pixelfed/pixelfed/commit/16b5ffff102a0a1c53ee32ed4f4f85ceddfef4df))
-   Update compiled assets ([6235594cf](https://github.com/pixelfed/pixelfed/commit/6235594cf526c43f8bc928fe88c859b7fea34abd))
-   Update CHANGELOG.md ([b7f70cae7](https://github.com/pixelfed/pixelfed/commit/b7f70cae7ee6e5735c60564f7ed624a1a6537383))
-   Update Clients.vue ([80271c900](https://github.com/pixelfed/pixelfed/commit/80271c9002a24d7026df7b389680c69239422751))
-   Add entry for fixing oauth client deletion ([63d28a486](https://github.com/pixelfed/pixelfed/commit/63d28a4865afe47c3e1a1c714a5ace731cad5e4c))
-   Update compiled assets ([004ce3225](https://github.com/pixelfed/pixelfed/commit/004ce3225e3cf4274f13908aef6ff672acc43f91))
-   fix: add larastan/larastan to composer.lock to fix docker build ([2dbee8599](https://github.com/pixelfed/pixelfed/commit/2dbee8599da2844b0348dc9b630882f0dbc6c1aa))
-   Update composer ([e4033b05b](https://github.com/pixelfed/pixelfed/commit/e4033b05bd6bdc31879cac56321f5dbfbad042bf))
-   Update AccountService ([59f57b110](https://github.com/pixelfed/pixelfed/commit/59f57b11072a06689b4d2f6373c22b433de92594))
-   Update CHANGELOG.md ([e69910e2b](https://github.com/pixelfed/pixelfed/commit/e69910e2b4ded537e56d312ab84eeca636eca85a))
-   Update ApiV1Controller, add show_atom support to update_credentials endpoint ([4e2e49f84](https://github.com/pixelfed/pixelfed/commit/4e2e49f8435c143e79566a1185775a76f29bcada))
-   Update CHANGELOG.md ([0a2b97fb9](https://github.com/pixelfed/pixelfed/commit/0a2b97fb987e164d80137e77c509a8832f2f433a))
-   Update ApiV1Controller, add is_suggestable to update_credentials endpoint ([7937d91c3](https://github.com/pixelfed/pixelfed/commit/7937d91c3761ed3b0c14ff124723ba25ea557bf1))
-   refactor: replace deprecated CheckForMaintenanceMode with PreventRequestsDuringMaintenance ([f363715ad](https://github.com/pixelfed/pixelfed/commit/f363715ad8e317ff118c9e65d45f039cfc7dd299))
    -   CheckForMaintenanceMode was deprecated in Laravel 8 and will be removed in
    -   Laravel 13. PreventRequestsDuringMaintenance is the modern replacement with
    -   support for secret bypass tokens and pre-rendered maintenance views.
-   Update changelog ([bb33696cc](https://github.com/pixelfed/pixelfed/commit/bb33696cc102951ceeec05d14656625201cee6da))
-   refactor: rename $routeMiddleware to $middlewareAliases ([d2bd73c27](https://github.com/pixelfed/pixelfed/commit/d2bd73c27e9ac194769da574c805187a60e05a00))
    -   The $routeMiddleware property was renamed to $middlewareAliases in Laravel 11.
    -   The old name still works in 12 via backwards compatibility but is on the
    -   deprecation path for removal in Laravel 13.
-   Fix typo ([8f1e47540](https://github.com/pixelfed/pixelfed/commit/8f1e47540766e444dc639d89cdddc9de895995d8))
-   refactor: convert string-based routes to ::class array syntax ([28927f6f6](https://github.com/pixelfed/pixelfed/commit/28927f6f661f17040d08b741ad4fec5a3ad0f43f))
    -   Replace all 'Controller@method' string references with
    -   \[Controller::class, 'method'\] array syntax across all route files.
    -   Remove the $namespace property and ->namespace() calls from
    -   RouteServiceProvider.
    -   This is required for Laravel 13 compatibility where string-based
    -   controller routing and automatic namespace prefixing will be removed.
    -   742 route references converted across 5 route files.
-   refactor: replace deprecated laravel/helpers with native alternatives ([edb4368b0](https://github.com/pixelfed/pixelfed/commit/edb4368b08008c977ea618c1dff96c0e61a61ceb))
    -   Replace all deprecated helper function calls:
    -   str_slug() → Str::slug()
    -   starts_with() → str_starts_with()
    -   ends_with() → str_ends_with()
    -   array_first() → Arr::first()
    -   array_last() → Arr::last()
    -   array_flatten() → Arr::flatten()
    -   Remove laravel/helpers package from composer.json as it is no longer
    -   needed and will not be maintained for Laravel 13.
-   refactor: use ::class syntax in EventServiceProvider ([741bc995c](https://github.com/pixelfed/pixelfed/commit/741bc995ca1133bfdf287b15a62fda56c9b36a5c))
    -   Replace string-based event class references with proper ::class imports
    -   for better IDE support and static analysis compatibility.
-   fix: resolve PDO::MYSQL_ATTR_SSL_CA deprecation on PHP 8.5 ([5041e1805](https://github.com/pixelfed/pixelfed/commit/5041e180500c8566da4ea86fb5c6a30a7e6c3555))
    -   Use Pdo\\Mysql::ATTR_SSL_CA when available (PHP 8.5+), falling back to
    -   the legacy PDO::MYSQL_ATTR_SSL_CA constant for older PHP versions.
    -   This eliminates the deprecation warning during Docker builds and runtime.
-   Update CHANGELOG.md ([3bf3e1274](https://github.com/pixelfed/pixelfed/commit/3bf3e1274835e68352a834a4d2c8550e1b5454bf))
-   refactor: replace deprecated laravel/helpers with native alternatives ([1bb05fafa](https://github.com/pixelfed/pixelfed/commit/1bb05fafa9f2027994de7552ebe3ecb7c2cac41b))
    -   Replace all deprecated helper function calls:
    -   str_slug() → Str::slug()
    -   starts_with() → str_starts_with()
    -   ends_with() → str_ends_with()
    -   array_first() → Arr::first()
    -   array_last() → Arr::last()
    -   array_flatten() → Arr::flatten()
    -   Remove laravel/helpers package from composer.json as it is no longer
    -   needed and will not be maintained for Laravel 13.
-   refactor: replace deprecated str_random() with Str::random() ([98267eb26](https://github.com/pixelfed/pixelfed/commit/98267eb26f738b5cd81879a7cb5bf80981391a25))
    -   str_random() is a deprecated helper from laravel/helpers that was
    -   missed in the initial helpers removal. Replace all 18 call sites
    -   with the modern Str::random() equivalent.
-   refactor: replace str_limit() with Str::limit() ([faa216b32](https://github.com/pixelfed/pixelfed/commit/faa216b32903ef070fffab8a2aca9852958ee5cf))
    -   Replace remaining 3 deprecated str_limit() calls with Str::limit().
    -   No other deprecated str\_\* helpers remain in the codebase.
-   Update ContextMenu, restore Edit button ([b761107c7](https://github.com/pixelfed/pixelfed/commit/b761107c71bcab41a8f0964392c4833f74c1b85e))
-   fix: replace str_random/str_limit/str_slug in Blade templates and tests ([30db57448](https://github.com/pixelfed/pixelfed/commit/30db57448f96c764d1a0cacb776b4ce003ee62d8))
    -   These deprecated helpers will throw 'undefined function' errors at
    -   runtime since laravel/helpers was removed. Replace with Str::random(),
    -   Str::limit(), and Str::slug() respectively.
-   fix: remove bootstrap/cache bind mount from docker-compose ([f5d166a93](https://github.com/pixelfed/pixelfed/commit/f5d166a933731a728060d02dd3e3bc8277dc75ca))
    -   The bootstrap/cache volume mount causes stale service provider references
    -   to persist across rebuilds. When a package is removed, the host's cached
    -   packages.php/services.php still reference the old provider, causing
    -   'Class not found' errors at container startup.
    -   The AUTORUN*LARAVEL*\*\_CACHE env vars already handle cache regeneration
    -   on each container start, making the bind mount unnecessary.
-   refactor: migrate to modern bootstrap/app.php architecture ([8e41f6fdf](https://github.com/pixelfed/pixelfed/commit/8e41f6fdf8538f805a1c54c29783cbf0bc3b7adb))
    -   Consolidate the legacy Laravel 5-era kernel/handler architecture into
    -   the modern Application::configure() pattern introduced in Laravel 11:
    -   HTTP middleware stack → bootstrap/app.php withMiddleware()
    -   Console schedule → bootstrap/app.php withSchedule()
    -   Exception handling → bootstrap/app.php withExceptions()
    -   Route registration → bootstrap/app.php withRouting()
    -   Service providers → bootstrap/providers.php
    -   Deleted files:
    -   app/Http/Kernel.php
    -   app/Console/Kernel.php
    -   app/Exceptions/Handler.php
    -   app/Providers/RouteServiceProvider.php
    -   app/Providers/BroadcastServiceProvider.php
    -   Removed framework providers from config/app.php (auto-registered by
    -   Application::configure). Package providers use auto-discovery.
    -   All 107 tests pass. Schedule, routes, and middleware verified working.
-   Update AccountService.php ([443f29acc](https://github.com/pixelfed/pixelfed/commit/443f29acc9277b65e732af6b30278fff456b2005))
-   refactor: remove redundant aliases from config/app.php ([5693b1581](https://github.com/pixelfed/pixelfed/commit/5693b1581f253f2afdc2ea0d33c5e64faac5b8bb))
    -   Remove auto-discovered package aliases (Purify, FFMpeg, Captcha) and
    -   the unused Eloquent alias. These are registered automatically via
    -   package auto-discovery.
    -   Framework facade aliases must remain until all 344+ short-import
    -   usages (e.g. 'use Cache;') are migrated to fully-qualified imports.
-   refactor: remove thin middleware wrappers, use framework classes directly ([ee7d7124d](https://github.com/pixelfed/pixelfed/commit/ee7d7124d049bd2b9b86a26e6e61e29645aa564b))
    -   Delete 4 middleware wrapper classes that added no custom logic:
    -   EncryptCookies (empty $except)
    -   TrimStrings ($except matches framework default)
    -   VerifyCsrfToken (exceptions moved to validateCsrfTokens() in bootstrap)
    -   TrustProxies (headers matched framework default)
    -   CSRF exceptions (/api/v1/\*, oauth/token) are now configured via
    -   $middleware->validateCsrfTokens(except: \[...\]) in bootstrap/app.php.
    -   All 107 tests pass.
-   Add view_oidc_callback_ensure_valid_username unit test ([22ff6810b](https://github.com/pixelfed/pixelfed/commit/22ff6810b9d33507c53451f28b95135b9a50eb0e))
-   refactor: replace short facade aliases with fully-qualified imports ([c807a8524](https://github.com/pixelfed/pixelfed/commit/c807a8524c22f53731d0c45ebcfde88041663c4e))
    -   Convert all 273 short facade alias imports (e.g. 'use Cache;') to their
    -   fully-qualified class names (e.g. 'use Illuminate\\Support\\Facades\\Cache;')
    -   across 193 files.
    -   This resolves 643 PHPStan 'class.notFound' errors caused by the static
    -   analyzer being unable to resolve global aliases, and aligns with modern
    -   Laravel conventions. It also unblocks removing the aliases array from
    -   config/app.php in a future change.
    -   All 107 tests pass.
-   Update Extractor.php ([899e360b4](https://github.com/pixelfed/pixelfed/commit/899e360b480ddeecd0fe7ef7ef65709cb382637f))
-   Delete tests/database.sqlite ([e8a13300e](https://github.com/pixelfed/pixelfed/commit/e8a13300e43fb90a7a56f8323a2959efb5a838a6))
-   Delete tests/database.sqlite ([e3279b428](https://github.com/pixelfed/pixelfed/commit/e3279b4286d1afa88a429a697161f3c499ebb8f6))
-   Update CHANGELOG.md ([3170c82a7](https://github.com/pixelfed/pixelfed/commit/3170c82a76fbe9bf39e9383528f9c9a1e7dc2658))
-   Update docker-tag.yml ([de656d12d](https://github.com/pixelfed/pixelfed/commit/de656d12dcd27941f5a31c62f1f5ffccee05c37a))
-   Update docker-push.yml ([28a56d047](https://github.com/pixelfed/pixelfed/commit/28a56d047e34bea0a54971e71222d680c8300329))
-   Delete phpstan-baseline.neon ([412ac5fd9](https://github.com/pixelfed/pixelfed/commit/412ac5fd9782613a505bebc286ceb5c3c22a8496))
-   Update phpstan.neon ([afbbd9ea0](https://github.com/pixelfed/pixelfed/commit/afbbd9ea01ede7acaa5425736286a528f17d6b00))
-   refactor: update phpstan.neon with Larastan 3.x best practices ([890dc5534](https://github.com/pixelfed/pixelfed/commit/890dc55342785206d077aaeaaf7bdc4c87c8dd90))
    -   Add databaseMigrationsPath for model property type inference
    -   Add configDirectories for config key validation
    -   Add parseModelCastsMethod to read casts() methods
    -   Add enableMigrationCache for faster repeated analysis
    -   Ignore intentional 'new static()' pattern (Autolink has subclass)
    -   Remove stale baseline reference and outdated comments
-   fix: add missing FeedUnfollowPipeline import ([58efefb87](https://github.com/pixelfed/pixelfed/commit/58efefb8783d2699fc613008130610c5629f924f))
    -   Add missing use statement for FeedUnfollowPipeline in PrivacySettings
    -   and FollowerObserver. These caused PHPStan internal errors blocking
    -   full analysis.
-   Update phpstan.neon ([d8a122a9c](https://github.com/pixelfed/pixelfed/commit/d8a122a9cd5680cb844db241179c52a1c6f54e1a))
-   Update CHANGELOG.md ([eeda06cee](https://github.com/pixelfed/pixelfed/commit/eeda06cee84f8b6fd4d0e1564a700473700108b3))
    -   Updated changelog with recent refactor details and fixes.
-   fix: add missing property declarations (phpstan property.notFound) ([43040a227](https://github.com/pixelfed/pixelfed/commit/43040a227547d5d548deda864cbcd04ec8f16baf))
    -   Add $fractal property and initialization to NewPublicPost event
    -   Add $mastodon and $pleroma property declarations to AudienceScopeTest
-   fix: resolve undefined variable bugs (phpstan variable.undefined) ([ccd75dd90](https://github.com/pixelfed/pixelfed/commit/ccd75dd9031a0208b46926c8e61d4185a4b8bc9a))
    -   AdminReportController: fix closure param name and remove reference to
    -   undefined $meta variable
    -   GroupsPostController: replace $status with $gp (the actual GroupPost
    -   variable in scope)
    -   PortfolioController: replace undefined $metadata with null
    -   DeleteWorker: remove Cache::set() call with undefined $key
-   fix: use query methods instead of collection methods (phpstan noUnnecessaryCollectionCall) ([49b85e9f2](https://github.com/pixelfed/pixelfed/commit/49b85e9f2250c25caeeaab6591097631a79da275))
    -   PollService: pluck()->first() → value()
    -   StoryService: groupBy()->pluck()->count() → distinct()->count()
    -   Inbox: find($objects)->count() → whereIn('id', $objects)->count()
-   fix: remove call to non-existent PollService::storyPoll() ([7bde84b23](https://github.com/pixelfed/pixelfed/commit/7bde84b23c841bd6386eae49d9211002fc37d5d8))
    -   The storyPoll() method was never implemented on PollService.
    -   Replace with null to fix phpstan staticMethod.notFound.
    -   Note: Passport::personalAccessClientId() is also flagged but deferred
    -   to a separate PAT refactoring effort.
-   fix: add missing use imports to resolve phpstan class.notFound errors ([e7ba43e2e](https://github.com/pixelfed/pixelfed/commit/e7ba43e2e1ea56243ecf2d4dfffce2d64f2b2317))
    -   Add missing imports for Log, Cache, DB, FollowerService, StatusService,
    -   LikeService, ReblogService, UserFilterService, AdminProfile, OauthClient,
    -   and fix StatusTimelineTransformer reference (class didn't exist, replaced
    -   with StatusTransformer).
-   Update StoryService.php ([942e15c65](https://github.com/pixelfed/pixelfed/commit/942e15c652b8b5c6f71e98b4514374566d19ca28))
-   Update TimelineController.php ([58a34056c](https://github.com/pixelfed/pixelfed/commit/58a34056ca60d765df0cda1fc482338540664ab9))
-   Update StoryService.php ([4bb9edcb2](https://github.com/pixelfed/pixelfed/commit/4bb9edcb2283df18088f9a3031f1bb14a2b603d3))
-   fix: replace backslash-prefixed facade calls with imported references ([7c964f3b4](https://github.com/pixelfed/pixelfed/commit/7c964f3b4f4a0c18266041848a0d75adf161f771))
    -   Replace \\Cache::, \\Log::, \\DB:: calls with their imported facade
    -   equivalents. The backslash-prefix relies on global aliases which
    -   PHPStan cannot resolve, causing class.notFound errors.
-   fix: resolve undefined $status variable in GroupsPostController::deletePost ([e7ef58969](https://github.com/pixelfed/pixelfed/commit/e7ef58969cd138346843b390d1010d7843086089))
    -   Replace all references to non-existent $status with $gp (the GroupPost
    -   instance already in scope). This was a bug where the closure variable
    -   name was changed but references inside the method body were not updated.
-   fix: add return type declarations to Eloquent relation methods ([f2159197e](https://github.com/pixelfed/pixelfed/commit/f2159197e8313d4575f4a82607d170c64677eeb2))
    -   Larastan 3.x requires explicit return types on relation methods to
    -   verify relation existence when using with(), has(), etc. This adds
    -   the appropriate return type declarations to all relation methods
    -   flagged by the larastan.relationExistence rule.
    -   Models fixed:
    -   Profile (avatar, statuses)
    -   User (profile)
    -   Status (profile, media, hashtags)
    -   DirectMessage (status, author, recipient)
    -   Report (reporter, status, reportedUser)
    -   Like (actor, status)
    -   Media (status)
    -   Notification (item)
    -   HashtagFollow (hashtag)
    -   OauthClient (user)
    -   Story (profile)
    -   StatusHashtag (status, hashtag, profile, media)
    -   AccountInterstitial (user)
    -   Hashtag (posts)
    -   CustomFilter (keywords)
    -   CustomFilterKeyword (customFilter)
    -   AdminShadowFilter (profile)
    -   ImportPost (status)
-   fix: replace Auth facade with $request->user() in request-scoped classes ([0939f495b](https://github.com/pixelfed/pixelfed/commit/0939f495bb0dee5122c16205b49a277da22cdd2a))
    -   Replace Auth::user() with $request->user() and Auth::check() with
    -   $request->user() !== null (or ! $request->user()) across all
    -   controllers and middleware that have access to the request object.
    -   This resolves 99 larastan.noAuthFacadeInRequestScope errors and
    -   improves Octane compatibility.
    -   For protected helper methods without $request in scope, uses the
    -   request() helper instead.
    -   Methods that previously lacked a Request parameter but used Auth
    -   facade now accept Request $request via Laravel's auto-injection.
-   Revert "Merge pull request #6851 from pixelfed/fix/phpstan-auth-request-scope-2" ([161773490](https://github.com/pixelfed/pixelfed/commit/1617734907c38dd5db60f8526bc3d8a341ee4e62))
    -   This reverts commit ce4baf69958cc36d6b6ee3f710849b6e06223661, reversing
    -   changes made to 9235cb979affb6e9fd7ddbb74ace945da1ea2589.
-   fix: replace Auth facade with $request->user() in request-scoped classes ([458150e06](https://github.com/pixelfed/pixelfed/commit/458150e06b024a78ca322fd21e43c0e5cca45a90))
    -   Replace Auth::user() with $request->user() and Auth::check() with
    -   $request->user() !== null (or ! $request->user()) across all
    -   controllers and middleware that have access to the request object.
    -   This resolves 99 larastan.noAuthFacadeInRequestScope errors and
    -   improves Octane compatibility.
    -   For protected helper methods without $request in scope, uses the
    -   request() helper instead.
    -   Methods that previously lacked a Request parameter but used Auth
    -   facade now accept Request $request via Laravel's auto-injection.
-   fix: use request() helper for methods without Request parameter ([88e0d92ac](https://github.com/pixelfed/pixelfed/commit/88e0d92ac2b8fef2290775133d169cf52301b61f))
    -   Methods that are registered as route actions without a Request type-hint
    -   (settings views, export actions) cannot accept Request $request without
    -   breaking Laravel's route signature reflection. Use the request() helper
    -   instead to avoid ReflectionFunction TypeError.
-   fix: convert OAuth routes from legacy array syntax to modern fluent syntax ([76d187edd](https://github.com/pixelfed/pixelfed/commit/76d187edd886469d2b176241d00c3777e654fbc2))
    -   The old 'uses' => \[Controller::class, 'method'\] array format causes a
    -   ReflectionFunction TypeError in Laravel 12 when Livewire's
    -   SupportPageComponents tries to resolve route bindings. The framework's
    -   RouteSignatureParameters::fromAction() expects a Closure or string,
    -   not an array.
    -   Convert all OAuth/Passport routes to the modern fluent syntax:
    -   Route::post('/path', \[Controller::class, 'method'\])->name('name')
-   feat: add critical path test suite and fix auth/config issues ([8a2649b3f](https://github.com/pixelfed/pixelfed/commit/8a2649b3ff89c8ee44053425727c82140c60c069))
    -   Test Infrastructure:
    -   Modernize phpunit.xml (bootstrap, source block, Laravel 12 env vars)
    -   Configure tests/Pest.php with pest()->extend(TestCase::class)->in('Feature')
    -   Add docker-compose.test.yml (Redis for test suite)
    -   Add composer test/test:quick scripts
    -   Rename CACHE_DRIVER to CACHE_STORE across config (backwards compatible)
    -   Update .env.testing for in-memory SQLite + Docker Redis
    -   Test Coverage (190 tests):
    -   CriticalRoutes: public routes, auth routes, API endpoints, middleware, schedule
    -   Auth/LoginTest: login, logout, rate limiting, redirect behavior
    -   Auth/RegisterTest: registration flow, validation, disabled registration
    -   Auth/PasswordResetTest: reset request, token validation, password update
    -   Auth/TwoFactorTest: 2FA checkpoint, setup behind password confirmation
    -   Auth/PasswordConfirmationTest: sudo mode flow via Laravel password.confirm
    -   Api/ScopeTest: scope enforcement, public endpoints, admin access
    -   Bugs Fixed:
    -   Fix unauthenticated API returning 500 instead of 401 (AuthenticationException
    -   not handled in custom exception renderer in bootstrap/app.php)
    -   Replace custom DangerZone middleware with Laravel password.confirm
    -   Add HasFactory trait to Profile model for test factories
    -   Bugs Documented (known-bugs group):
    -   Registration crashes with str_ends_with TypeError (RegisterController:82)
    -   OAuth routes use legacy array syntax causing ReflectionFunction TypeError
-   fix: resolve str_ends_with TypeError in RegisterController ([97929f087](https://github.com/pixelfed/pixelfed/commit/97929f0876706b3e2a1130427878735dae4a58d2))
    -   PHP's str_ends_with() only accepts a string needle, not an array.
    -   The username validation was passing an array of extensions which
    -   caused a TypeError on every registration attempt.
    -   Replace with a loop over a configurable array of disallowed extensions,
    -   making it easy to add new entries.
    -   Also updates RegisterTest to properly test the registration flow
    -   including the RT anti-bot token and age verification fields.
-   test: expect oauth endpoints to return 200 (will pass after route syntax fix merge) ([ec8a39302](https://github.com/pixelfed/pixelfed/commit/ec8a39302029ea6e7af4dc3d98847be4d18af0a6))
-   feat: add framework integration tests for Laravel 12→13 upgrade readiness ([4ce28d914](https://github.com/pixelfed/pixelfed/commit/4ce28d91449bdad24f25b12bd4511ab8d037d465))
    -   Framework tests verify core Laravel integration points:
    -   ServiceProviderTest: app boot, guard resolution, route loading, config_cache
    -   RoutingTest: named routes, duplicates, api/oauth prefixes, middleware groups
    -   EloquentTest: User/Profile/Status factories, relationships, casts, soft deletes
    -   QueueTest: job dispatch, serialization, middleware, unique IDs
    -   ConfigTest: config loading, env overrides, auth/cache/queue settings
    -   MiddlewarePipelineTest: CSRF, auth, throttle, password confirm, 2FA, admin
    -   Also:
    -   Add HasFactory trait to Status model
    -   Fix StatusFactory: remove non-existent 'place' column, add 'rendered' field
    -   Add Api/AccountTest for account endpoint coverage (254 total tests)
-   chore: add TODO to replace custom FrameGuard with Laravel built-in security headers ([18c288f88](https://github.com/pixelfed/pixelfed/commit/18c288f88f57be858d8c5adb001bdf0b1c2e5caa))
-   ci: refactor GitHub Actions with Redis service and best practices ([c7473cd1a](https://github.com/pixelfed/pixelfed/commit/c7473cd1a838a98b545f549d105dc6316dd92886))
    -   Tests workflow: add Redis service, cache composer deps, test PHP 8.4+8.5,
    -   generate Passport keys, use vendor/bin/pest directly
    -   Larastan workflow: cache deps, consistent checkout@v4, memory limit
    -   Pint workflow: use project's installed Pint (not global), cache deps
    -   Standardize Redis port to 6379 across CI and local docker-compose
    -   Remove 'unstable' branch from triggers (unused)
    -   Remove 'main' branch from static analysis (doesn't exist)
-   ci: fix action versions (checkout@v7, cache@v6) and add unstable branch ([0ed3e6192](https://github.com/pixelfed/pixelfed/commit/0ed3e6192a735c525f5a32bd00f4b369fb1f07a6))
-   Update php-larastan.yml ([b2af98788](https://github.com/pixelfed/pixelfed/commit/b2af98788857306ef18b5aba189de08fecdc913e))
-   Update php-laravel-tests.yml ([2adbb6507](https://github.com/pixelfed/pixelfed/commit/2adbb65079189f3970f3d22bb0172eca5b3eec99))
-   Rename workflow to PHP - Pint ([0f6ed0d91](https://github.com/pixelfed/pixelfed/commit/0f6ed0d91864a00764c7b9995c481ac802c247ae))
-   Update CHANGELOG.md ([f6a3df88d](https://github.com/pixelfed/pixelfed/commit/f6a3df88d18cdd6ed6e40c5ccbb0fd1c90c546f5))
    -   Updated changelog to reflect recent refactors and testing improvements.
-   Revise CHANGELOG.md for recent updates ([df9943368](https://github.com/pixelfed/pixelfed/commit/df99433689a521f4bb77b7dcd5fb48f92e1f4dd0))
    -   Updated changelog to reflect recent changes and fixes.
-   test: add settings, mute/block, and follow tests (278 total) ([891e28280](https://github.com/pixelfed/pixelfed/commit/891e282808975beb3215504b2c773849d7707b26))
    -   Settings/ProfileUpdateTest: profile name, bio, website validation,
    -   password change flow with Mail::fake assertion
    -   Account/MuteBlockTest: mute/unmute, block/unblock, self-protection,
    -   admin block protection, validation
    -   Account/FollowTest: follow/unfollow via API, self-follow rejection,
    -   followers/following list endpoints
-   test: add status, timeline, federation, and privacy tests (309 total) ([e1f883a41](https://github.com/pixelfed/pixelfed/commit/e1f883a41b9eafbae081c15350a0b8fea761e2c2))
    -   Api/StatusTest: get/delete statuses, favourite/unfavourite, bookmark,
    -   status creation validation, ownership checks
    -   Api/TimelineTest: public/home/hashtag timelines, private exclusion,
    -   pagination support
    -   Federation/NodeInfoTest: nodeinfo, webfinger, host-meta endpoints
    -   Account/PrivacyTest: private profile visibility, blocked user access,
    -   privacy settings toggle
-   test: add notification, search, compose, report, and collection tests (332 total) ([0f3820e7b](https://github.com/pixelfed/pixelfed/commit/0f3820e7bfa988091a28874bcb2cb598402d1966))
    -   Api/NotificationTest: notification isolation, correct user filtering
    -   Api/SearchTest: v2 search auth, structure, account lookup
    -   Api/CollectionTest: self/user collections, auth requirement
    -   Compose/ComposeTest: page access, settings, media validation, autocomplete
    -   Account/ReportTest: report creation, type validation, auth requirement
-   test: add admin access and API scope security tests (360 total) ([579a581de](https://github.com/pixelfed/pixelfed/commit/579a581de878c91631b566d9483d623985ab00b5))
    -   Security/AdminAccessTest: verifies non-admin users are blocked from
    -   all admin web routes (dashboard, users, reports, settings, instances,
    -   curated onboarding) and admin API endpoints.
    -   Security/ApiScopeSecurityTest: verifies read-only tokens cannot write
    -   (follow, favourite, delete, mute, block), write tokens can read+write,
    -   cross-user access is denied, and private statuses are protected.
    -   BUG FOUND: CheckForAnyScope middleware (referenced in v1/admin routes)
    -   was removed in Passport 13. All /api/v1/admin/\* routes throw
    -   BindingResolutionException. 6 tests skipped pending fix.
-   fix: replace removed Passport scope middleware with current classes ([7a96cd2e9](https://github.com/pixelfed/pixelfed/commit/7a96cd2e915290226de9bdbaee874c85bec32c71))
    -   Laravel Passport 13 renamed:
    -   CheckScopes → CheckToken (verifies ALL listed scopes)
    -   CheckForAnyScope → CheckTokenForAnyScope (verifies ANY listed scope)
    -   The old class names no longer exist, causing BindingResolutionException
    -   on all /api/v1/admin/\* routes that use the 'scope' or 'scopes' middleware
    -   aliases.
-   fix: replace removed Passport scope middleware with current classes ([0eae871e4](https://github.com/pixelfed/pixelfed/commit/0eae871e40a0d1a15fad83c2850752a2cbe174a8))
    -   Laravel Passport 13 renamed:
    -   CheckScopes → CheckToken (verifies ALL listed scopes)
    -   CheckForAnyScope → CheckTokenForAnyScope (verifies ANY listed scope)
    -   The old class names no longer exist, causing BindingResolutionException
    -   on all /api/v1/admin/\* routes that use the 'scope' or 'scopes' middleware
    -   aliases.
-   test: un-skip Passport scope tests now that middleware is fixed ([9f81a5b42](https://github.com/pixelfed/pixelfed/commit/9f81a5b4259497b49ad54d64beea890229f49d9c))
    -   All v1 admin route security tests now pass with proper assertions
    -   after the CheckForAnyScope → CheckTokenForAnyScope fix.
-   test: add auth scope migration verification tests (390 total, all green) ([918e49136](https://github.com/pixelfed/pixelfed/commit/918e49136a6ec472b6243afe1c5cfe97e2e0368b))
    -   AuthScope/RequestUserTest: exercises every controller and middleware
    -   that was refactored from Auth::user()/Auth::check() to $request->user().
    -   Covers web routes (follow requests, compose, collections, discover,
    -   profile, status, timeline, newsroom), API routes (verify_credentials,
    -   timelines, notifications, blocks, mutes, favourites, bookmarks), and
    -   middleware (admin, password.confirm, account interstitial).
    -   All 390 tests pass with the auth-scope-3 and passport middleware fixes
    -   applied together.
-   test: add auth scope migration tests and update CI action versions ([a486f509a](https://github.com/pixelfed/pixelfed/commit/a486f509a52c43abdbfbe90e321ab43c382e1185))
    -   AuthScope/RequestUserTest: 22 tests verifying all controllers and
    -   middleware that were migrated from Auth::user() to $request->user().
    -   CI: update to checkout@v7, cache@v6
-   revert: restore original GitHub Actions workflow names ([47d98bfb5](https://github.com/pixelfed/pixelfed/commit/47d98bfb55d25b11d417b921759b3c706eeb792d))
-   fix: replace Auth facade with $request->user() in request-scoped classes ([ffcef3eb2](https://github.com/pixelfed/pixelfed/commit/ffcef3eb2ddf1f4629efeec7bdf0fa25e5b7d554))
    -   Replace Auth::user() with $request->user() and Auth::check() with
    -   $request->user() !== null (or ! $request->user()) across all
    -   controllers and middleware that have access to the request object.
    -   This resolves 99 larastan.noAuthFacadeInRequestScope errors and
    -   improves Octane compatibility.
    -   For protected helper methods without $request in scope, uses the
    -   request() helper instead.
    -   Methods that previously lacked a Request parameter but used Auth
    -   facade now accept Request $request via Laravel's auto-injection.
-   Update AccountController.php ([3c6280111](https://github.com/pixelfed/pixelfed/commit/3c6280111e4c76c1017a390169759d2612c60f4a))
-   fix: remove deprecated Passport::personalAccessClientId() and enableImplicitGrant() ([5a364be58](https://github.com/pixelfed/pixelfed/commit/5a364be58b9a72cd1da694b076ac511f495b25e4))
    -   Remove Passport::personalAccessClientId() (removed in Passport v13, auto-discovers now)
    -   Remove Passport::enableImplicitGrant() (legacy grant, no clients use it)
    -   Flatten config instance.oauth.pat to pat_enabled (remove dead pat.id key)
    -   Add OAUTH_PAT_ENABLED=false to .env.example and .env.docker.example
    -   Show swal alert when PATs disabled instead of hidden API error
    -   Improve store() error handling to surface 403 messages in the UI
    -   Remove OAUTH_PAT_ID row from admin diagnostics blade
-   fix: remove dead RemoteFollowPipeline (references uninstalled HttpSignatures package) ([7042ea536](https://github.com/pixelfed/pixelfed/commit/7042ea536731ad35e81138a7fac4199fc3a792ea))
    -   Delete app/Jobs/RemoteFollowPipeline/RemoteFollowPipeline.php
    -   Delete app/Jobs/RemoteFollowPipeline/RemoteFollowImportRecent.php
    -   Neither job is dispatched anywhere in the codebase
    -   Remote follow is handled by ActivityPub Inbox and FollowPipeline
-   fix: remove dead publicApi/homeApi methods from TimelineController ([8cf532156](https://github.com/pixelfed/pixelfed/commit/8cf5321566cabc40e7ead05f354c6a02ce926f06))
    -   publicApi referenced non-existent StatusTimelineTransformer class
    -   Neither method is routed anywhere
    -   Removes unused imports (Fractal, Cache, Status, Profile, UserFilter)
-   Revert "fix: remove dead publicApi/homeApi methods from TimelineController" ([ea2d054a4](https://github.com/pixelfed/pixelfed/commit/ea2d054a405d6d6f09c383394e940a5c48c85973))
    -   This reverts commit 8cf5321566cabc40e7ead05f354c6a02ce926f06.
-   comment dead code ([f54e6280b](https://github.com/pixelfed/pixelfed/commit/f54e6280bcae0a20c921148fb96ca4553be18d23))
-   Update DOCKER_COMPOSE_SETUP.md ([e2c6162b3](https://github.com/pixelfed/pixelfed/commit/e2c6162b35064d091c85fe79cbb9939bd3047c44))
-   Fix duplicate command in Docker Compose setup ([4086f0778](https://github.com/pixelfed/pixelfed/commit/4086f0778311c2e3c8e0a4561dff78aca415890f))
-   polish ([c891f34df](https://github.com/pixelfed/pixelfed/commit/c891f34df64ae1ecbb33854cf1c00126085d8b8a))
-   refactor: replace $fillable with $guarded = \[\] across all models ([570a30d03](https://github.com/pixelfed/pixelfed/commit/570a30d037a74346ec672e2b0bcf07de5dac58fa))
    -   Aligns all models with the project convention (see .ai/rules/models.md).
    -   Model::shouldBeStrict() in non-production will catch any issues early.
-   fix: replace deprecated starts_with() with str_starts_with() ([26b8a0a6b](https://github.com/pixelfed/pixelfed/commit/26b8a0a6b0d0f230e406424b29e161db1ebe50ae))
    -   The starts_with() helper was removed in Laravel 6. Use PHP 8's native
    -   str_starts_with() instead.
-   feat: add throttle:api middleware to the api route group ([ed90e619f](https://github.com/pixelfed/pixelfed/commit/ed90e619fbe68d93977612273d0ecb8f0b5099b4))
    -   Adds a global rate limiter (240 req/min per user or IP) to all API
    -   routes. Previously rate limiting was only applied ad-hoc on individual
    -   routes, leaving some endpoints unprotected.
-   Update AppServiceProvider.php ([0837968fa](https://github.com/pixelfed/pixelfed/commit/0837968fad405d70aeab69c71d2f2eccdc72b1b5))
-   fix: unpin symfony/http-foundation to allow patch updates ([0c849ca4e](https://github.com/pixelfed/pixelfed/commit/0c849ca4e7e88528a2b13f70841b914a71e3ff61))
    -   Changes constraint from exact '7.4.13' to '^7.4.13'. The pin was
    -   introduced for CVE-2026-48736 but is overly restrictive — any 7.4.x
    -   release >= 7.4.13 includes the fix. This allows future security
    -   patches to install via composer update.
    -   Note: Symfony 8.x is blocked by laravel/framework ^12 which requires
    -   symfony/http-foundation ^7.2.0. Symfony 8 support arrives with Laravel 13.
-   chore: remove unused direct dependencies ([1696dfaca](https://github.com/pixelfed/pixelfed/commit/1696dfacaa86c4b98b5ff629b75c9936db6ec621))
    -   Remove endroid/qr-code: never imported in app code; only
    -   bacon/bacon-qr-code is used directly (for 2FA QR generation).
    -   Remove nesbot/carbon: already pulled in transitively by
    -   laravel/framework, laravel/horizon, and laravel/pulse.
-   fix: enable MySQL strict mode and remove defaultStringLength(191) ([1b64c59be](https://github.com/pixelfed/pixelfed/commit/1b64c59bebc440af8b78546f7ce3173404aaedf5))
    -   Enable strict mode for MySQL connection to prevent silent data
    -   truncation, zero-date insertion, and division-by-zero errors.
    -   Remove Schema::defaultStringLength(191) which was a MySQL 5.7
    -   workaround no longer needed on MySQL 8.0+ / MariaDB 10.3+.
-   fix: replace deprecated $request->get() with $request->input() ([4320231c1](https://github.com/pixelfed/pixelfed/commit/4320231c1f621ba2d40e270afa0719d4d7599515))
    -   Symfony 8.0 removes Request::get(). Laravel 13 will support Symfony 8,
    -   so these 11 usages would break on upgrade. Using $request->input()
    -   which checks both query string and request body (same behavior as the
    -   old get() method).
-   refactor: rename VerifyCsrfToken to PreventRequestForgery ([9958b095d](https://github.com/pixelfed/pixelfed/commit/9958b095dd9c8d3ddbdee3f1262f81a70892ed55))
    -   Prepares for Laravel 13 where VerifyCsrfToken is deprecated in favor
    -   of PreventRequestForgery. The old class remains as an alias in v13 but
    -   will be removed in a future version.
-   feat: add serializable_classes to cache config for Laravel 13 prep ([ba90d1bd2](https://github.com/pixelfed/pixelfed/commit/ba90d1bd20bd714e021405cdc8d80f0209967957))
    -   Laravel 13 defaults serializable_classes to false, blocking arbitrary
    -   PHP object unserialization from cache. This project caches CustomFilter
    -   model instances (in getCachedFiltersForAccount), so it must be
    -   explicitly allowlisted.
    -   All other cache usage in this project stores scalars, arrays, or
    -   Fractal-transformed array output — no other classes need allowlisting.
-   Update database.php ([2f466b02d](https://github.com/pixelfed/pixelfed/commit/2f466b02d76ca0a05199d4fcc7ed38973eaf7286))
-   Update database.php ([323cfc9cf](https://github.com/pixelfed/pixelfed/commit/323cfc9cf75381d143fccf998984c19471a58248))
-   Change DB_STRICT environment variable to true ([35cb9a9dc](https://github.com/pixelfed/pixelfed/commit/35cb9a9dcb1495cc3c5d38225549fc7bad290cf9))
-   Set strict mode to true in database configuration ([542434785](https://github.com/pixelfed/pixelfed/commit/542434785c72a6746018306dac75bea085203454))
-   Update model loading behavior in AppServiceProvider ([c04fec21f](https://github.com/pixelfed/pixelfed/commit/c04fec21fcfa94e7b4e160561d5c1b46fd4376d6))
-   Update AppServiceProvider.php ([1ae0feb12](https://github.com/pixelfed/pixelfed/commit/1ae0feb1291f48b8a89299b448f07dc251c6bcdb))
-   Update AppServiceProvider.php ([de8de9251](https://github.com/pixelfed/pixelfed/commit/de8de9251b9fa8115c870b116363641c7de313b6))
-   Pint app/ ([33dce75f2](https://github.com/pixelfed/pixelfed/commit/33dce75f2c91413edb8018f7a7d4922977abc427))
-   Pint config/ ([42f361540](https://github.com/pixelfed/pixelfed/commit/42f361540c9b6afbe5bc93cb1ab1c3573e760b34))
-   Update AppServiceProvider.php ([f13a891ff](https://github.com/pixelfed/pixelfed/commit/f13a891ff4c20d31edab0b82f096de53128bfde7))
-   Pint database/ ([db636ee08](https://github.com/pixelfed/pixelfed/commit/db636ee08ae0fa04ee70439e52b4b3d7541bd632))
-   Improve test assertions and imports ([412c29bb4](https://github.com/pixelfed/pixelfed/commit/412c29bb47a92e6485b4f066a05e18770466583f))
-   Update app.php ([2c704d9a7](https://github.com/pixelfed/pixelfed/commit/2c704d9a76e07862fc811bc493d8ba7dcc354c6e))
-   Move ValidateCsrfToken middleware to a new position ([46393bd9f](https://github.com/pixelfed/pixelfed/commit/46393bd9fc2b8cdba87647ea7206f205cb6e9316))
-   refactor: add return type declarations to controller methods ([54cfdf3c2](https://github.com/pixelfed/pixelfed/commit/54cfdf3c2b5f37013c40bc8567a96da1f42627d1))
    -   Adds explicit return type declarations to 498 controller methods
    -   across 88 files. Types inferred from return statements:
    -   JsonResponse for response()->json() returns
    -   RedirectResponse for redirect()/back() returns
    -   View (contract) for view() returns
    -   Response for response() returns
    -   void for methods with no return value
    -   array for array returns
    -   string/int/bool for scalar returns
    -   Also fixes 3 methods with incorrect bare returns:
    -   AvatarController::deleteAvatar - bare return → json response
    -   ImportPostController::checkPermissions - bare return → true
    -   RemoteAuthController::accountToId - bare return → empty array
-   fix: resolve 6 Larastan errors in controller return types ([17a5b5c3d](https://github.com/pixelfed/pixelfed/commit/17a5b5c3de929c223d93c2394fe5b774871580f7))
    -   DeckController: add missing View contract import
    -   CuratedRegisterController::proceed(): add default switch case
    -   GroupController::reportAction(): add default switch case
    -   InstallController::checkDatabase/precheckDatabase: add missing return
-   refactor: replace Guzzle pool with Laravel HTTP client in StatusDelete ([00dd5b3d9](https://github.com/pixelfed/pixelfed/commit/00dd5b3d922fbd016a777a457950971165278ca7))
    -   Replace direct GuzzleHttp\\Client and Pool usage in fanoutDelete()
    -   with Laravel's Http::pool() facade. This provides:
    -   Testability via Http::fake() in tests
    -   Consistent timeout/retry configuration
    -   No direct Guzzle dependency in application code
    -   Proper integration with Laravel's HTTP client features
-   fix: improve NewStatusPipeline retry configuration ([a0f721781](https://github.com/pixelfed/pixelfed/commit/a0f721781a292f938ad2a36ddcbf8f95a5b6aa03))
    -   Previous config (timeout=5, tries=1) was too aggressive — a single
    -   transient failure would permanently lose the status publication.
    -   New config:
    -   timeout: 5 → 30 (sufficient for DB check + job dispatch)
    -   tries: 1 → 3 (recover from transient Redis/DB issues)
    -   maxExceptions: 1 (don't retry actual bugs)
    -   backoff: \[5, 10\] (exponential delay between retries)
-   polish ([2b1c9c818](https://github.com/pixelfed/pixelfed/commit/2b1c9c818b6ca35b3d24f8c9ac351d9015427063))
-   polish ([a142db87b](https://github.com/pixelfed/pixelfed/commit/a142db87b4e63d7d760d5f1d337e9636c388e169))
-   refactor: move 52 legacy models from App\\ to App\\Models\\ ([c0cde2f68](https://github.com/pixelfed/pixelfed/commit/c0cde2f682f2e3f3f64bd2ec42d5f1c993ba221d))
    -   Move all Eloquent models from the app/ root directory to app/Models/
    -   for consistency with modern Laravel conventions. The project already had
    -   54 models in App\\Models; this migrates the remaining 52 legacy models.
    -   Changes:
    -   Move 52 model files from app/ to app/Models/
    -   Update namespace declarations in each model
    -   Update all ~1000 import references across the codebase
    -   Add Relation::morphMap() in AppServiceProvider for backward
    -   compatibility with existing polymorphic database records
    -   Add missing HasSnowflakePrimary imports for models that relied
    -   on same-namespace resolution
-   test: verify morph map resolves legacy model namespaces ([4231ce993](https://github.com/pixelfed/pixelfed/commit/4231ce993863a3cdd615b70f9912988453796d4e))
    -   Ensures that existing database records using the old App\\Status,
    -   App\\Profile, etc. morph types correctly resolve to the new
    -   App\\Models\\ classes via Relation::morphMap().
-   fix: resolve 3 remaining Larastan errors from model migration ([aae7700bf](https://github.com/pixelfed/pixelfed/commit/aae7700bf3d42bb6531bbd8e5552c20f04c5562a))
    -   AccountInterstitial middleware: fix FQCN reference
    -   Like/UndoLike transformers: fix aliased import namespace
-   refactor: split ActivityPub Inbox into focused traits with shared helpers ([f4b6d03ae](https://github.com/pixelfed/pixelfed/commit/f4b6d03ae603494e3acf22e27cd2ebb9d54be9f3))
    -   Extract InboxHelpers trait with common utilities (domain/user blocking, actor validation, notification deletion, cache clearing)
    -   Create domain-specific handler traits: HandlesFollows, HandlesLikes, HandlesCreates, HandlesAnnouncements, HandlesDeletes, HandlesUndos, HandlesStories, HandlesFlags, HandlesUpdates, HandlesMoves
    -   Merge duplicate story reaction/reply handlers into single handleStoryInteraction method
    -   Break handleDirectMessage into focused sub-methods
    -   Reduce Inbox.php to thin verb router (~167 lines)
    -   No behavioral changes; public API preserved
-   refactor: extract shared ActivityPub pool delivery into ActivityPubDeliveryService ([9c9e2a5a2](https://github.com/pixelfed/pixelfed/commit/9c9e2a5a22a9589aab893d4614b868888384f42b))
    -   Add ActivityPubDeliveryService::pool() using Laravel's Http::pool() to
    -   consolidate the duplicated delivery pattern found across 10 jobs.
    -   Updated jobs:
    -   StatusActivityPubDeliver
    -   StatusDelete
    -   StatusLocalUpdateActivityPubDeliverPipeline
    -   FanoutDeletePipeline
    -   SharePipeline
    -   UndoSharePipeline
    -   StoryFanout
    -   StoryExpire
    -   StoryDelete
    -   ProfileMigrationDeliverMoveActivityPipeline
    -   The shared method accepts a Profile (sender), audience (inbox URLs),
    -   and activity (payload array), handling signing, user-agent, timeout,
    -   and concurrency in one place. No direct Guzzle usage remains in
    -   app/Jobs/.
-   refactor: extract duplicate patterns into shared methods ([e7b70c608](https://github.com/pixelfed/pixelfed/commit/e7b70c6084bc171404af1320283c2aff037a2865))
    -   1. Add FractalService with static item() and collection() helpers
    -   replacing 22 call sites that repeated the 4-line Fractal Manager
    -   ArraySerializer boilerplate.
    -   2. Add AccountInterstitial::createFromStatus() factory method
    -   consolidating 4 identical 15-line blocks that create interstitials
    -   with status metadata.
    -   3. Add NotificationService::createNotification() to handle the
    -   repeated pattern of creating, caching, and registering a
    -   notification in the recipient's feed.
    -   4. Add NotificationService::firstOrCreateNotification() for
    -   idempotent notifications (share/boost, mention) that should
    -   only notify once per actor+action+item combination.
-   fix: resolve larastan class.notFound errors ([941c30510](https://github.com/pixelfed/pixelfed/commit/941c305104c3dc50b48a0e6f361e1cbcb15729bf))
    -   Add missing FractalService import to Groups/GroupCommentService and
    -   Groups/GroupPostService (wrong namespace resolution)
    -   Update Inbox handler traits to use App\\Models\\\* namespace instead of
    -   old App\\\* references (Status, Profile, DirectMessage, Media, Follower,
    -   Like, Instance, Story, User, FollowRequest, Notification, UserFilter,
    -   StoryView)
    -   Update HttpClientMigrationTest to use App\\Models\\\* namespace
-   refactor: migrate LikePipeline to use NotificationService::firstOrCreateNotification ([8b53f23e7](https://github.com/pixelfed/pixelfed/commit/8b53f23e77f8abc5e9aca03039df9fda8e134f7c))
-   Fix endsWith. Closes #6904 ([e3a264070](https://github.com/pixelfed/pixelfed/commit/e3a2640704421280dd2edfc7dd4a31bfc773d20d))
-   Update composer ([1810caac2](https://github.com/pixelfed/pixelfed/commit/1810caac2d82e0be0b3148486cb20a70d3c7b70c))
-   Fix cache error ([44275154e](https://github.com/pixelfed/pixelfed/commit/44275154ea862c2b2cefc9edc6a41835f37f45b7))
-   refactor: consolidate username validation into PixelfedUsername rule ([7c5d93e96](https://github.com/pixelfed/pixelfed/commit/7c5d93e96ba35414fd64b5f31949e79d11801b7d))
    -   Replace 7 duplicated inline username validation closures across 6
    -   controllers (ApiV1Dot1, RemoteAuth, CuratedRegister, AdminInvite x2,
    -   AppRegister, Auth/Register) with the existing PixelfedUsername rule.
    -   Add the 'must contain at least one alphabetical character' check to
    -   the rule so all call sites share consistent, stricter validation.
    -   Add PixelfedUsernameTest covering all validation branches.
-   Create DeduplicationChanges.md ([d1ea7a5be](https://github.com/pixelfed/pixelfed/commit/d1ea7a5be75f22c02469046de477dfda589b7e28))
-   refactor: rename PixelfedUsername rule to ValidUsername ([302edf09d](https://github.com/pixelfed/pixelfed/commit/302edf09d5c5cc92f19ecd63e2de1bfd086cee50))
    -   Pure rename of the App\\Rules\\PixelfedUsername validation rule to
    -   App\\Rules\\ValidUsername for a clearer, more idiomatic name. Updates
    -   the class, filename, test, and all 8 controller call sites. No
    -   behavior change.
-   Rename DeduplicationChanges.md to notes/DeduplicationChanges.md ([0b6f8e4ab](https://github.com/pixelfed/pixelfed/commit/0b6f8e4ab66777b7a40a34b6bd6c7cd7cd92fdd6))
-   Update DeduplicationChanges.md ([101b529ac](https://github.com/pixelfed/pixelfed/commit/101b529acf35f482f32ebfff49789f3d61895aed))
-   Remove duplicate boilerplate code in documentation ([27c778f4b](https://github.com/pixelfed/pixelfed/commit/27c778f4bf76a4031e084e00ebb35f41da5e275e))
    -   Removed duplicate lines from username validation, notification, and fractal boilerplate sections.
-   Update DeduplicationChanges.md ([4982839c5](https://github.com/pixelfed/pixelfed/commit/4982839c5a3d807c76b59a6a2415f821e2ef7113))
-   Refactor boilerplate examples for deduplication ([6cce21032](https://github.com/pixelfed/pixelfed/commit/6cce2103215450498428194097d812ebb384026a))
    -   Updated boilerplate examples for ActivityPub Delivery, Username validation, and Notification services to replace repeated code snippets with concise method calls.
-   Update DeduplicationChanges.md with service reference ([78a48f0ef](https://github.com/pixelfed/pixelfed/commit/78a48f0ef06d2ca0928252c56ec9b32e9f18a95d))
-   chore: remove unused import and fix spacing in cache config ([3be6dbf54](https://github.com/pixelfed/pixelfed/commit/3be6dbf5477673d7613d5403cc1b000f62c9da53))
-   Fix ProfileMigrationStorageRequest, use signed requests for gts and other compat ([81245ec46](https://github.com/pixelfed/pixelfed/commit/81245ec4675b95f30606089949b9fcbdcca00ca0))
-   Fix AdminReports ([5b63f5f22](https://github.com/pixelfed/pixelfed/commit/5b63f5f225cd29d434306a8e1cbaa833a9467bf4))
-   Update compiled assets ([db37202e6](https://github.com/pixelfed/pixelfed/commit/db37202e6fd94013b19e03755931288c99e6746a))
-   fix: stop caching raw Eloquent models to prevent incomplete-object 500s ([f0e951dcc](https://github.com/pixelfed/pixelfed/commit/f0e951dcce7edeea392ff6cc4b83a752f59c2790))
    -   Caching an Eloquent model in a Cache::remember closure could deserialize
    -   into a \_\_PHP_Incomplete_Class on read, throwing 'attempt to access a
    -   property on an incomplete object' and returning a 500. This surfaced on
    -   guest profile pages (ProfileController::buildProfile reading
    -   $user->user->settings) and affected several other latent call sites.
    -   Changes:
    -   ProfileController: cache a plain settings array instead of the
    -   UserSetting model; fall back to defaults when the settings row is missing
    -   StoryService::getById: fetch a live model instead of caching it
    -   InstanceService::getByDomain, CustomEmoji::scan: cache arrays
    -   Site/MobileController: cache Page data as an array via a shared
    -   ManagesCachedPages trait; update blade views to array access
    -   Add public-route smoke/regression tests covering the cache-read path
-   Add user:status artisan command for account login/reset diagnostics ([e39b0f1b5](https://github.com/pixelfed/pixelfed/commit/e39b0f1b508f79048d72728f85673eca2733398a))
-   Update AdminReportController ([0679216fa](https://github.com/pixelfed/pixelfed/commit/0679216fa2adae822bca875e2cd4bd8ee207ad7e))
-   Add user:setpassword artisan command for CLI password reset ([7148c5828](https://github.com/pixelfed/pixelfed/commit/7148c582835c8222d95c515e225b4314275c9881))
-   Update UserAccountDelete command ([51beaa30d](https://github.com/pixelfed/pixelfed/commit/51beaa30d18fb33fb9bed951aedf19fa53e5ccfb))
-   Add user:checkpassword read-only command to diagnose rejected logins ([2c7227a9c](https://github.com/pixelfed/pixelfed/commit/2c7227a9c11d164425b14ae71ebe9dd05c853ab6))
-   Fix CSRF token not found error on guest pages (login/register) ([54f99334b](https://github.com/pixelfed/pixelfed/commit/54f99334bb7c424d6ecd43bcdeae8a9a29f9db19))
    -   The app layout renders separate head blocks for auth vs guest users.
    -   The guest block was missing the <meta name="csrf-token"> tag that
    -   app.js reads to set the axios X-CSRF-TOKEN header, causing a console
    -   error on the login and register pages. Add the meta tag to the guest
    -   head to match the authenticated head block.
-   Fix CSRF token not found error on guest pages (login/register) ([32e391d26](https://github.com/pixelfed/pixelfed/commit/32e391d2678fcbd56f3764211efa430d3eb2aa44))
    -   The app layout renders separate head blocks for auth vs guest users.
    -   The guest block was missing the <meta name="csrf-token"> tag that
    -   app.js reads to set the axios X-CSRF-TOKEN header, causing a console
    -   error on the login and register pages. Add the meta tag to the guest
    -   head to match the authenticated head block.
-   Add csrf-token meta to anon and app-guest layouts ([ea1a629b1](https://github.com/pixelfed/pixelfed/commit/ea1a629b1a275e28ddfadbaa66e2550b9ebe4383))
    -   These guest layouts also load app.js, which reads the csrf-token meta
    -   tag to set the axios X-CSRF-TOKEN header. Without it, they logged the
    -   same 'CSRF token not found' console error and had no CSRF header for
    -   AJAX requests. Adds the meta tag to match the other layouts.
-   Expand user:status profile section with full column dump and derived metadata ([93f813848](https://github.com/pixelfed/pixelfed/commit/93f81384826f7d90ab7537407b2fdfdd0c598ecc))
    -   Dump every profiles column dynamically (keys redacted, long text trimmed),
    -   add derived metadata (local/remote type, urls, live vs cached follower/
    -   following/status counts, avatar, federation fields), and profile health
    -   checks (soft-delete, id mismatches, missing crypto keys, count desync).
-   Add profile:status command for local and remote profile diagnostics ([92d09ffaa](https://github.com/pixelfed/pixelfed/commit/92d09ffaaf4d4c44c81e557f3b80d4a75878ab1d))
    -   Unlike user:status (local users only, keyed on the users table),
    -   profile:status keys on the profiles table so it works for remote/
    -   federated actors too. Resolves id, username, user@domain, @user@domain,
    -   webfinger, or remote_url. Shows full column dump, derived/federation
    -   metadata, linked local user (local) or Instance row (remote), and
    -   health checks for orphans, missing keys, and count desync.
-   Fix unauthenticated SSRF in remote media/avatar fetch (variant of CVE-2026-71246) ([3d82a8e8b](https://github.com/pixelfed/pixelfed/commit/3d82a8e8b206e5e7a0ca69626827b1263e45c9a5))
    -   The remote media path validated URLs only as strings (Helpers::validateUrl
    -   normalizes the host + checks a ban list) and then downloaded them with
    -   Http::head + file_get_contents($url), which resolve DNS themselves and
    -   follow redirects with no private-IP checks and no address pinning. A remote
    -   actor whose icon.url redirected to an internal address (e.g. 172.18.0.1 or
    -   169.254.169.254) made the queue worker fetch internal content and, for
    -   image responses, republish it at a public avatar URL. No account required.
    -   Fixes:
    -   Add SecureMediaFetchService: validates URL, resolves + rejects non-global
    -   IPs (fail-closed), pins the connection to the validated IP via
    -   CURLOPT_RESOLVE, disables auto-redirects with per-hop re-validation, and
    -   enforces https-only + a byte cap. Mirrors the ActivityPubFetchService
    -   hardening from CVE-2026-71246.
    -   Route MediaStorageService head()/fetchAvatar()/remoteToCloud() through it,
    -   removing the bare Http::head and file_get_contents($url) sinks.
    -   validateUrl(): when DNS verification is enabled, reject hosts that resolve
    -   into reserved ranges, closing the metadata.google.internal bypass.
    -   Harden adjacent same-class sinks: CustomEmojiService (emoji doc + image +
    -   head), FetchCacheService/webfinger, and DiscoverActor.
    -   Add regression tests (tests/Unit/ActivityPub/SsrfUrlValidationTest.php).
-   Allow gif and webp mime types for custom emoji import ([7482befd8](https://github.com/pixelfed/pixelfed/commit/7482befd8f08845c7b79631e39cd50a8c491a980))
    -   Add image/gif and image/webp to the accepted custom emoji image types via
    -   a shared CustomEmojiService::ALLOWED_MIME_TYPES constant used by both the
    -   ActivityPub mediaType check and the response-content headCheck, so the
    -   allowlist stays in sync. File extension derives from the mime type.
-   Update SecureMediaFetchService.php ([8123dcf93](https://github.com/pixelfed/pixelfed/commit/8123dcf9342c1c296bbeea5ca8066dca89b72db1))
-   Add fix:followercount command to resync drifted follower/following counts ([9fe1fe55a](https://github.com/pixelfed/pixelfed/commit/9fe1fe55af19c48cbdce4be842a2930bd8e2b7f9))
    -   profiles.followers_count/following_count are cached columns reconciled
    -   lazily by FollowServiceWarmCache (throttled up to 7 days), so they can
    -   drift from the followers table. This command recomputes them from the
    -   source-of-truth table for a single profile or --all drifted local
    -   profiles, with --dry-run to report and --dispatch to queue the warm-cache
    -   job (which also rebuilds the Redis sets). Mirrors the existing
    -   fix:statuscount convention.
-   Add fix:profilecounts (total profile cache resync); remove redundant count commands ([037f1ac0b](https://github.com/pixelfed/pixelfed/commit/037f1ac0b987b2b31cd954d8685f6304afcc3c72))
    -   Consolidate cached-count reconciliation into a single fix:profilecounts
    -   command that resyncs followers_count, following_count and status_count
    -   from the source-of-truth tables for one profile or --all. Only reports
    -   profiles with actual drift (silent when in sync); supports --dry-run and
    -   --dispatch (queues FollowServiceWarmCache and rebuilds Redis sets).
    -   Removes the superseded manual commands fix:followercount, fix:statuscount
    -   and fix:rpc. Keeps app:account-post-count-stat-update, which is scheduled
    -   (runs every 6 hours) and queue-driven.
-   Apply Pint formatting to SecureMediaFetchService ([aadde946d](https://github.com/pixelfed/pixelfed/commit/aadde946d267594c8468f74c2eb176f310718a0e))
-   Refactor profile count reconciliation into shared AccountStatService methods ([a187ab663](https://github.com/pixelfed/pixelfed/commit/a187ab6639be43ca6381be710bed34f396bf3c53))
    -   Extract canonical source-of-truth count logic into AccountStatService:
    -   recalculateStatusCount/FollowerCount/FollowingCount and a
    -   reconcileProfileCounts() that fixes only drifted columns and busts caches.
    -   Both the scheduled app:account-post-count-stat-update (status-only, its
    -   correct scope) and fix:profilecounts now use these instead of duplicating
    -   the SQL. Also corrects the status_count definition to match the actual
    -   increment logic in StatusEntityLexer/StatusDelete (media post types only:
    -   photo/video albums), rather than the previous inconsistent all-statuses /
    -   scoped counts that could themselves cause drift.
    -   The scheduled updater keeps its incremental, dirty-set design and remains
    -   status-only; follower/following stay owned by FollowServiceWarmCache.
-   Schedule weekly profile-count reconcile and add reconciliation tests ([698ba224e](https://github.com/pixelfed/pixelfed/commit/698ba224e38d76fbc8105de92d1888c18ebac595))
    -   Add --force flag to fix:profilecounts for unattended runs and schedule
    -   'fix:profilecounts --all --force' weekly (Sun 03:37) as a safety-net
    -   reconcile. Kept as a low-frequency full scan rather than a new event-driven
    -   dirty-set; it only writes profiles that actually drifted.
    -   Add Feature tests for AccountStatService recompute helpers and
    -   reconcileProfileCounts (media-type status_count semantics, follower/
    -   following counts, drift/no-drift/no-write, metric restriction, missing
    -   profile) plus fix:profilecounts command behavior (silent-when-synced,
    -   dry-run makes no changes).
-   Rename to admin:fixProfileCounts, make --active its own mode, add --type ([96f26405f](https://github.com/pixelfed/pixelfed/commit/96f26405f1eef5b0e403d00da3311a91cbb2f650))
    -   Rename command signature fix:profilecounts -> admin:fixProfileCounts.
    -   --active is now its own bulk mode (recently-active local accounts),
    -   mutually exclusive with --all and a single id.
    -   Add --type=followers|following|statuses to restrict reconciliation to a
    -   single metric (validated).
    -   Update/extend tests for the new name, --type restriction and invalid-type
    -   rejection.
-   Update stale command-name reference in comment to admin:fixProfileCounts ([55e9201b1](https://github.com/pixelfed/pixelfed/commit/55e9201b1a0330c236cbcd3f1ccf2a232e36da6b))
-   Fix VueIntersect single-element warning in notifications section ([b3be61c47](https://github.com/pixelfed/pixelfed/commit/b3be61c47c0e267ba151258ee800acd61bbfcac7))
    -   The <intersect> in sections/Notifications.vue wrapped four <placeholder>
    -   elements directly. vue-intersect requires exactly one child (it checks
    -   $slots.default.length and observes $slots.default\[0\]), so it logged
    -   '\[VueIntersect\] You may only wrap one element in a <intersect> component.'
    -   and only observed the first placeholder. Wrap the placeholders in a single
    -   <div> so the slot has one root element.
-   Require --scope (local/remote/both) for admin:fixProfileCounts --all ([bcd5a5bd7](https://github.com/pixelfed/pixelfed/commit/bcd5a5bd7b6d8aae5ac6015aa654349937765a41))
    -   Bulk --all reconciliation previously scanned both local and remote profiles
    -   implicitly. Now --all requires an explicit --scope of local, remote, or
    -   both. --active stays local-only and rejects a non-local --scope. Adds the
    -   BelongsTo return type to Profile::user() so the whereHas('user') scope
    -   filter passes Larastan, and adds tests for scope requirement/validation and
    -   local/remote filtering.
-   Add post:status command for post/media diagnostics ([4aa7b5728](https://github.com/pixelfed/pixelfed/commit/4aa7b572807cc00c41252f97223d1e59671fa1cd))
    -   Dumps a Status and its media for debugging. Accepts a post id or URL
    -   (/p/username/ID). Shows status columns, author, every media row's storage
    -   fields (media_path, thumbnail_path, cdn_url, thumbnail_url, optimized_url,
    -   remote_url, etc.), computed url()/thumbnailUrl()/expected-from-path, a URL
    -   health check comparing stored URL hosts against the configured cloud disk
    -   host (flags stale hosts), and the cached MediaService media_attachments
    -   actually served to clients.
-   Add admin:MigrateLocalMediaURL; replace media:cloud-url-rewrite ([04536a6e3](https://github.com/pixelfed/pixelfed/commit/04536a6e322a2c007d2f37d08b937364c76b83fe))
    -   Rebuilds stale local media URLs (cdn_url, thumbnail_url, optimized_url) and
    -   avatar cdn_urls from their storage paths via the configured cloud disk.
    -   Default target host comes from the configured cloud disk (AWS_URL);
    -   requires confirmation (or --force) and can be overridden with --newDomain.
    -   Optional --oldDomain filters to a single old backend host; by default all
    -   stale hosts are rewritten.
    -   Refuses to run when PF_ENABLE_CLOUD is false (local storage) and, when
    -   auto-detecting, refuses a target equal to the app domain — so local-storage
    -   instances are never rewritten.
    -   Single status id / post URL, --all, --avatars; --dry-run; busts
    -   MediaService/StatusService caches for affected statuses.
    -   Removes the superseded media:cloud-url-rewrite command.
    -   Adds feature tests covering rewrite/skip/dry-run/oldDomain/newDomain/
    -   remote-skip/local-storage-refusal.
-   Rename to admin:MigrateLocalS3MediaURL and drop --avatars ([da9e73dd2](https://github.com/pixelfed/pixelfed/commit/da9e73dd2207b7eea7ee1e0b0a78db91415f6f50))
    -   Rename the command (and test) to admin:MigrateLocalS3MediaURL to reflect its
    -   scope: rewriting stale S3/cloud media URLs only. Remove avatar handling and
    -   the --avatars option; the command now focuses solely on status media
    -   (cdn_url, thumbnail_url, optimized_url).
-   Fix MigrateLocalS3MediaUrl tests failing in CI ([34d6fb31f](https://github.com/pixelfed/pixelfed/commit/34d6fb31f945eb733455110ab1c4397988d5065c))
    -   config_cache() falls through to config() when instance.enable_cc is off
    -   (ENABLE_CONFIG_CACHE=false, as in CI/.env.testing), so ConfigCacheService::put()
    -   alone did not toggle pixelfed.cloud_storage and the command's cloud-enabled
    -   guard aborted with exit 1. Set the underlying config value too (both in
    -   beforeEach and the local-storage refusal test).
-   Update CHANGELOG.md ([45918bbb1](https://github.com/pixelfed/pixelfed/commit/45918bbb1a23b6982f873dceb923c4edf75e825d))
-   Add media storage migration commands (local<->cloud) with integrated GC ([6ff9ffbbb](https://github.com/pixelfed/pixelfed/commit/6ff9ffbbb8f406fae09b06669da9acf36b136703))
    -   Add admin:MediaMoveStorageLocalToCloud and admin:MediaMoveStorageCloudToLocal:
    -   Copy media (+thumbnail) between local and cloud disks, verify by size (and
    -   sha256 against original_sha256 when present) before deleting the source.
    -   Integrated GC: delete the verified source copy (local on upload, cloud on
    -   download), set version=4 / reset to 3, and bust MediaService/StatusService
    -   caches. --keep-local / --keep-cloud opt out.
    -   Manage PF_ENABLE_CLOUD in .env AND the live runtime + config cache so new
    -   uploads route to the correct backend mid-migration on a hot server. Uses the
    -   installer's atomic .env writer (shared ManagesMediaStorageEnv trait).
    -   --limit / --dry-run / --force.
    -   Replaces media:migrate2cloud (CloudMediaMigrate) and media:s3gc
    -   (MediaS3GarbageCollector); scheduler now runs MediaMoveStorageLocalToCloud
    -   hourly for straggler upload + GC. Keeps media:fix-nonlocal-driver.
    -   Adds feature tests (download+GC, --keep-cloud, dry-run, env-flag flip both
    -   directions, unknown-disk guard).
-   Add admin:MediaMoveStorageCloudToCloud for cold S3->S3 migration ([70b4a05b5](https://github.com/pixelfed/pixelfed/commit/70b4a05b5c56525d88e894db63cef7480c72db2f))
    -   Cold-migrate existing media from an old S3 bucket to the current cloud
    -   bucket, one media row at a time (like MigrateLocalS3MediaURL):
    -   Source = --sourceDisk (default s3-old, reads AWS*OLD*\*); destination = the
    -   current cloud disk (config filesystems.cloud). No .env editing: operators
    -   point AWS\_\* at the new bucket first (restarting workers as usual) so new
    -   uploads/downloads land on the new bucket, then run this to backfill old data.
    -   Copies media (+thumbnail) source->destination, verifies by size and by
    -   sha256 of the freshly-written destination object (against original_sha256),
    -   rewrites cdn_url/optimized_url/thumbnail_url to the destination host, and
    -   GCs the source objects (unless --keep-source). Busts caches.
    -   Only touches rows whose cdn_url still points at the source host; idempotent.
    -   --sourceDisk / --limit / --dry-run / --force.
    -   Adds the s3-old disk (AWS*OLD*\*) to config/filesystems.php and feature tests.
-   polish ([68366c7dd](https://github.com/pixelfed/pixelfed/commit/68366c7dde2768709be812bf75f7deb9a86aa87f))
-   Fix duplicate-key violation when importing remote media attachments ([0d01d5a96](https://github.com/pixelfed/pixelfed/commit/0d01d5a96338b72c5265e80672dc579a4553545b))
    -   Helpers::importNoteAttachment unconditionally inserted a new Media row per
    -   attachment, so re-importing a remote status (an Announce racing another
    -   inbox job, a re-fetch, or a duplicate url within one activity) hit the
    -   media_status_id_media_path_unique constraint and crashed the queue job with
    -   a 1062 UniqueConstraintViolationException, dropping the boost/import.
    -   Make createMediaAttachment idempotent on (status_id, media_path): skip when
    -   a row already exists, and catch the unique-constraint violation as a
    -   lost-race no-op, returning null so the caller skips re-dispatching storage.
    -   Adds regression tests (re-import no-op, distinct urls still stored,
    -   concurrent-insert returns null).
-   refactor: rename status debug commands to status: prefix ([16c7c5d2e](https://github.com/pixelfed/pixelfed/commit/16c7c5d2e36752425418ac698402f4a51a01e44c))
    -   Rename user:status, profile:status, and post:status console commands
    -   to status:user, status:profile, and status:post. Rename the command
    -   files and classes to match (StatusUser, StatusProfile, StatusPost) and
    -   update the cross-reference tip in StatusProfile.
-   refactor: organize Artisan commands into subfolders ([1eae4bbd4](https://github.com/pixelfed/pixelfed/commit/1eae4bbd4304d3a59f1bfeadaff4daa2b8754e77))
    -   Group console commands into Admin, Dev, FixBugs, Install, Internal, and
    -   User subfolders (matching the earlier reorganization), and add a new
    -   Status subfolder for the status:user, status:profile, and status:post
    -   debug commands. Namespaces updated to match; command signatures and the
    -   total command count are unchanged.
-   docs: add README for Artisan commands with listing and audit ([c99b8068a](https://github.com/pixelfed/pixelfed/commit/c99b8068a24726fcee101927870ea5f8ac32f905))
-   Update README.md ([cafec250f](https://github.com/pixelfed/pixelfed/commit/cafec250fe5911844ca44869b35537fc833618ca))
-   refactor: move resolved one-off migrations to Deprecated/ ([cd353a830](https://github.com/pixelfed/pixelfed/commit/cd353a8305514377d8e55b3b3f7a026e1dc1988e))
    -   status:dedup and fix:avatars address historical data states that can no
    -   longer occur (unique statuses.uri index since 2019; SVG identicon avatars
    -   no longer generated). Move both to a Deprecated/ folder and update the
    -   README audit accordingly. media:fix stays in FixBugs/ since image filters
    -   are still an active feature.
-   Update Profile component ([32ff6d48c](https://github.com/pixelfed/pixelfed/commit/32ff6d48cdde8d6f2b2a0e216c035aaf3e73167d))
-   Update compiled assets ([ba8b92105](https://github.com/pixelfed/pixelfed/commit/ba8b92105d50e9e0494a232d0e4b05083f76282d))
-   feat: add admin:fixPostCounts to resync post like/boost/comment counts ([744e45360](https://github.com/pixelfed/pixelfed/commit/744e4536069d13886aaa4a673a7f44f2a45dd743))
    -   Add a FixPostCounts command mirroring admin:fixProfileCounts (single-id,
    -   --all --scope, --active, --type, --dry-run, --force). It reconciles the
    -   statuses likes_count, reblogs_count, and reply_count columns against
    -   source-of-truth tables.
    -   Add canonical recompute helpers and reconcileStatusCounts() to
    -   StatusService (mirroring AccountStatService), busting the status cache
    -   only when a column actually drifted.
-   refactor: move admin:fix\*Counts commands to Admin/ ([73b8353da](https://github.com/pixelfed/pixelfed/commit/73b8353dab311843875069aef86649c871f30f68))
    -   FixProfileCounts and FixPostCounts use the admin: signature prefix and
    -   are operator-run maintenance tools, so move them from FixBugs/ to Admin/
    -   (namespace updated) and refresh the README tables to match.
-   fix: display comments count as 0 instead of blank in admin:fixPostCounts ([d50024a57](https://github.com/pixelfed/pixelfed/commit/d50024a578d4a457108546c2169223299f585c27))
    -   reply_count is a nullable column, so NULL rendered as an empty string in
    -   the resync summary. Cast the summary output to int so a null/absent
    -   comment count prints as 0. No behavior change to the reconcile logic.
-   fix: make admin:fixPostCounts summary report only changed metrics ([de850836c](https://github.com/pixelfed/pixelfed/commit/de850836cac44a6bb8b7f6c35ca9f358fa4d6240))
    -   The resynced summary printed all three counts unconditionally, which
    -   made an untouched metric (e.g. an already-correct comments count) look
    -   like it had been resynced. Drive the summary from the drifted set and
    -   show before->after values, so it matches the drift detection exactly.
-   test: add feature tests for admin:fixPostCounts ([ec5be5241](https://github.com/pixelfed/pixelfed/commit/ec5be52418678fae8a417047f056a5e2df4a0e2e))
    -   Cover source-of-truth resync of likes/boosts/comments, dry-run, no-op on
    -   correct data, --type restriction, argument validation, and bulk --all
    -   mode. Includes regression tests for the two reporting bugs: the summary
    -   now lists only drifted metrics, and a null reply_count renders as 0.
-   style: import DB facade in FixPostCounts test (pint) ([078380723](https://github.com/pixelfed/pixelfed/commit/078380723f8df98da5a04629146403d796295064))
-   chore: add Psalm static analysis (plugin-laravel, baseline, CI) ([1a234c392](https://github.com/pixelfed/pixelfed/commit/1a234c39207eaf79cdc0d148cc38197fa8c535a0))
    -   Port PR #6646 onto staging: add psalm/plugin-laravel with psalm.xml,
    -   a staging-generated baseline, and a CI workflow that emits GitHub
    -   annotations and uploads SARIF to Code Scanning. Fix the psalm.xml schema
    -   for Psalm 6.5 (drop unsupported ClassMustBeFinal handler) and ignore
    -   generated report artifacts in git/docker.
-   ci(psalm): report findings but never fail the job ([2617211c1](https://github.com/pixelfed/pixelfed/commit/2617211c1f95fe281e7980eb54ff8549dfdcb1f6))
-   ci(psalm): align workflow with php-\* conventions, test on PHP 8.5 ([6945277e2](https://github.com/pixelfed/pixelfed/commit/6945277e2ccb1e7c32ce4269e70d17f61f9a9706))
    -   Rename psalm.yml to php-psalm.yml to match sibling workflows, bump PHP
    -   8.4 -> 8.5, use the shared checkout/setup-php/cache/composer steps and
    -   staging/dev/unstable triggers. Keeps report-only behavior and SARIF
    -   Code Scanning upload.
-   ci(psalm): guarantee SARIF file exists and upgrade upload-sarif to v4 ([5cecde670](https://github.com/pixelfed/pixelfed/commit/5cecde67089f2f6d431f26b3a2019e7bb78daa7c))
    -   Add a fallback step that writes a minimal valid SARIF report when Psalm
    -   exits before producing one, so the Code Scanning upload never hard-fails
    -   the job. Bump github/codeql-action/upload-sarif v3 -> v4.
-   ci(psalm): skip SARIF upload when report is missing ([aed7936e4](https://github.com/pixelfed/pixelfed/commit/aed7936e4efc78823becc596d3d1b3607cc1f8f4))
    -   Replace the blank-SARIF fallback with an existence check; uploading an
    -   empty SARIF would clear existing Code Scanning alerts. Now the upload is
    -   skipped (with a warning) when Psalm produced no report.
-   ci(psalm): run analyzer on PHP 8.4 to avoid 8.5 crash ([25494c491](https://github.com/pixelfed/pixelfed/commit/25494c49110e6623b396c9894c5a2150ce65e5cd))
    -   Psalm 6.5.0 fatally crashes on PHP 8.5 (deprecated SplObjectStorage::attach
    -   escalated by its error handler) before analyzing anything. Pin the Psalm
    -   job to 8.4 so it runs and produces SARIF; revert to 8.5 once Psalm supports
    -   it.
-   Update php-psalm.yml ([3058d3f81](https://github.com/pixelfed/pixelfed/commit/3058d3f8169ac0a4f8a180259904234d3332ddfe))
    -   Comment out the SARIF report check and upload steps in the workflow.
-   Update php-psalm.yml ([27f7127cc](https://github.com/pixelfed/pixelfed/commit/27f7127ccdac125e97e4c35850846bb1160f7131))
-   Add vimeo/psalm and composer scripts ([4e0c567ec](https://github.com/pixelfed/pixelfed/commit/4e0c567ecd510925262cdd2df7fba49c0d73d96c))
-   Update VideoThumbnail.php ([186fa7c86](https://github.com/pixelfed/pixelfed/commit/186fa7c860a212344a953686515a1abd35c6922d))
-   Fix larastan errors in RemoteOidcTest: import Test attribute and RefreshDatabase, replace removed str_random helper ([b7c15dc7c](https://github.com/pixelfed/pixelfed/commit/b7c15dc7ca66e25d1e1302e7a7dd5759e3c5d8ac))
-   polish ([d86fd28e3](https://github.com/pixelfed/pixelfed/commit/d86fd28e3432112978b910468b14b122a601f6ae))
-   Accept compacted Note attachments ([#6588](https://github.com/pixelfed/pixelfed/pull/6588))
    -   Normalize JSON-LD compacted single attachments (a bare object instead of a
    -   one-item array) in getAttachments(), and route verifyAttachments() through it
    -   so validation and import share one normalization path.
    -   Includes PR #6589's tests plus additional edge-case coverage: list-form
    -   preservation, bare-input normalization, and guards for missing/empty/scalar
    -   attachments.
-   Update 2025_07_31_164635_change_hashtags_collation.php ([e53e82faf](https://github.com/pixelfed/pixelfed/commit/e53e82faf3cb7c53da8618f3c6c672a7e7b3584a))
-   Create HashtagCollationTest.php ([27768cd69](https://github.com/pixelfed/pixelfed/commit/27768cd69cfe2a28de89abf88e3c122305ff25e6))
-   Update php-larastan.yml ([9dfb5c062](https://github.com/pixelfed/pixelfed/commit/9dfb5c062e46d28d29871486d65d94d56946c32c))
-   Update php-laravel-tests.yml ([338d4da42](https://github.com/pixelfed/pixelfed/commit/338d4da427ad5c918faaeba04b715ba24a037f66))
-   Update php-pint.yml ([765e10ea6](https://github.com/pixelfed/pixelfed/commit/765e10ea6651f5f5117599d78d0d1944a8dc24fe))
-   Revert hashtags collation migration from #6098 ([405674766](https://github.com/pixelfed/pixelfed/commit/40567476662d432522b684fc7f2d8c3980c94465))
    -   Migration failed with a duplicate entry error: recollating to
    -   utf8mb4_unicode_520_ci causes previously-distinct hashtag names/slugs
    -   to collide on the unique indexes. Reverting until the data is
    -   de-duplicated first.
-   chore: add composer psalm:report script for a full local txt report ([6eea565ba](https://github.com/pixelfed/pixelfed/commit/6eea565babaeea565d73848dbde9be05e21d9bd3))
    -   Adds a psalm:report script that ignores the baseline and writes a full
    -   human-readable report to psalm-report.txt, including informational issues,
    -   so all outstanding items to fix are surfaced in one file.
-   chore: target PHP 8.4 in psalm config ([fb69275cd](https://github.com/pixelfed/pixelfed/commit/fb69275cd7443d0221f30016e8c191a536ff256b))
    -   Set phpVersion="8.4" so Psalm targets 8.4 explicitly instead of
    -   inferring 8.3 from composer.json's ^8.3|^8.4 constraint.
-   chore: resolve psalm issues in admin commands and auth ([878775cab](https://github.com/pixelfed/pixelfed/commit/878775cab95be5a29649f6e35139ffd72c4c08c6))
    -   Add return type hints (void) and final class markers
    -   Guard null returns from newestBackup() and putFileAs() in BackupToCloud
    -   Type ask() default values as strings
    -   Fix uses_left fallback condition for null/zero max_uses
    -   Annotate AdminInvite::whereInviteCode and cast Str::uuid() to string
    -   Ignore local redis-data and mysql-9-data dev directories
-   implement search by country ([129778ef2](https://github.com/pixelfed/pixelfed/commit/129778ef2e2bf90c43519f19c758931b47d8a24a))
-   fix: prevent remcache temp file leaks and add GC command ([3232761a7](https://github.com/pixelfed/pixelfed/commit/3232761a74999284df11345f1870db6a1dfb6fcf))
    -   The remote avatar/media fetchers wrote temp files to storage/app/remcache/
    -   and only unlinked them on the happy path. Any exception between the write
    -   and the unlink (e.g. a cloud upload failure) leaked the file, and nothing
    -   swept the directory.
    -   Wrap post-write logic in fetchAvatar() and remoteToCloud() in try/finally
    -   so the temp file is always removed, even on failure
    -   Add gc:remcache command to delete stale remcache files (default >24h old,
    -   preserves .gitignore, supports --hours and --dry-run)
    -   Schedule gc:remcache daily to clean up any stragglers
    -   StoryFetch already handled cleanup via try/catch and was left unchanged.
-   refactor: rename RemcacheGarbageCollector to GCRemcache ([57b3bf140](https://github.com/pixelfed/pixelfed/commit/57b3bf140b64824f33f27420d1c475b63156b1e6))
-   refactor: use GarbageCollector prefix for GC console command classes ([9704fd5a3](https://github.com/pixelfed/pixelfed/commit/9704fd5a362fd54bbfe24cbba4b56c0f308b8e1c))
    -   Rename the internal garbage collector commands to a consistent
    -   GarbageCollector\* naming scheme (file + class). Command signatures are
    -   unchanged, so the scheduler and cron entries are unaffected.
    -   MediaGarbageCollector -> GarbageCollectorMedia
    -   DatabaseSessionGarbageCollector -> GarbageCollectorDatabaseSession
    -   FailedJobGC -> GarbageCollectorFailedJob
    -   PasswordResetGC -> GarbageCollectorPasswordReset
    -   GCRemcache -> GarbageCollectorRemcache
    -   StoryGC -> GarbageCollectorStory
    -   ImportUploadGarbageCollection -> GarbageCollectorImportUpload
-   feat: migrate local story media to cloud storage ([9f110bb74](https://github.com/pixelfed/pixelfed/commit/9f110bb74c4ebcff72b3eba7fc9a645a713e4cf7))
    -   Ensure story media lands on and stays on cloud storage for S3 instances.
    -   StoryExpire: archive expiring story media on the same explicit disk the
    -   media lives on (S3 move is a server-side copy+delete), with error handling
    -   Add admin:StoryMoveStorageLocalToCloud to migrate local story media
    -   (active + story_archives) to cloud: copy, verify by size, then delete local
    -   --orphans option relocates untracked story_archives/ files to cloud using
    -   the same copy/verify/delete flow (media is moved, never discarded)
    -   Schedule it hourly alongside the media migration when cloud storage is on
-   feat: store custom emoji on cloud storage when enabled ([fa76e1014](https://github.com/pixelfed/pixelfed/commit/fa76e1014a0cc5e75f1aa7d84b800e7827779384))
    -   Custom emoji were always written locally and served via hardcoded /storage
    -   URLs, so they never used S3 even on cloud instances.
    -   CustomEmoji: centralize URL + storage on the active disk (cloud when
    -   pixelfed.cloud_storage is enabled, else local public/ disk) via
    -   urlForPath/url/storageTarget/storeMedia/storeMediaFromFile/deleteMedia
    -   Route emoji writes/deletes and URL generation (scan, CustomEmojiService::all)
    -   through those helpers in ImportEmojis, CustomEmojiService::import and
    -   AdminController
    -   Add admin:EmojiMoveStorageLocalToCloud to migrate existing local emoji to
    -   cloud: copy, verify by size, delete local, bust caches
    -   Schedule it daily when cloud storage is enabled
-   feat: migrate all local emoji to cloud in one pass by default ([979df6e39](https://github.com/pixelfed/pixelfed/commit/979df6e39e4ead7349a24600113d4209468f7b04))
    -   Change --limit default to 0 (no limit) so the emoji migration processes
    -   every local emoji in a single run instead of capping at 1000, and drop the
    -   limit from the scheduled invocation. Avoids a multi-run window where
    -   not-yet-migrated emoji resolve to missing cloud URLs.
-   feat: add migration to move local emoji to cloud on deploy ([a945efbf7](https://github.com/pixelfed/pixelfed/commit/a945efbf74a26b8587c492acf2b7564ad6d6976c))
    -   Runs admin:EmojiMoveStorageLocalToCloud during migrate so existing local
    -   emoji are relocated to cloud as part of the upgrade, shrinking the window
    -   where emoji URLs resolve to cloud before the files are there. No-op unless
    -   cloud storage is enabled.
-   fix: schedule StoryMoveStorageLocalToCloud command ([842681b99](https://github.com/pixelfed/pixelfed/commit/842681b99b8c658471b6dffc1f82d2fc7f96b96f))
    -   The story cloud-migration command was merged (#6958) but its scheduler
    -   entry was dropped when the emoji branch (based off staging before that
    -   merge) later merged and overwrote the scheduler block. Restore the hourly
    -   schedule so local story media is migrated to cloud automatically.
-   fix: emoji admin URLs and cloud-migration guard ([aa9bb868d](https://github.com/pixelfed/pixelfed/commit/aa9bb868dd6ce159a775113b55be85beebfe7beb))
    -   Two issues prevented emoji from serving/migrating correctly on cloud:
    -   Admin custom-emoji views hardcoded url('storage/'.media_path), so they
    -   always showed local URLs and bypassed cloud resolution. Use $emoji->url().
    -   The migration/command guard relied solely on config_cache('pixelfed.cloud_storage'),
    -   which is DB/12h-cached and can read stale-false right after cloud is
    -   enabled, causing the migration to silently no-op. Treat cloud as enabled
    -   when either live config() or config_cache() is true.
-   revert: story cloud-migration work ([9f55f7204](https://github.com/pixelfed/pixelfed/commit/9f55f7204cfa3f1d6eb930492d9e0d63f97c1620))
    -   Back out the story local->cloud migration so we can land and verify the
    -   emoji cloud work first, one change at a time.
    -   Reverts:
    -   9f110bb74 feat: migrate local story media to cloud storage
    -   842681b99 fix: schedule StoryMoveStorageLocalToCloud command
    -   Removes StoryMoveStorageLocalToCloud command, its scheduler entry, and the
    -   StoryExpire explicit-disk changes. Remcache and emoji work are untouched.
    -   Will revisit story once emoji is confirmed.
-   fix: make emoji cloud migration disk-driven + add --debug ([79eef56be](https://github.com/pixelfed/pixelfed/commit/79eef56be07cb276c296badef4b0ce42043e6d80))
    -   The migration was DB-driven (whereNull('uri')), which excluded federated
    -   emoji whose media is stored locally but have a uri set -> the disk was never
    -   scanned, resulting in moved=0.
    -   Drive the migration by enumerating local files under public/emoji/ instead
    -   of a DB query; the local file is the source of truth for what needs moving
    -   Add --debug to print config, custom_emoji table breakdown, local emoji dir
    -   contents, and per-file decisions
-   fix: skip missing.png in emoji cloud migration ([9e5fcb9a9](https://github.com/pixelfed/pixelfed/commit/9e5fcb9a903524516f6bed942fa2933353c03a42))
    -   The frontend renders a hardcoded /storage/emoji/missing.png local onerror
    -   fallback for emoji, so that placeholder must stay on local disk. Skip it in
    -   the migration so it is never moved to cloud or deleted locally.
-   perf: parallelise emoji cloud migration with worker processes ([00564ac22](https://github.com/pixelfed/pixelfed/commit/00564ac221c9c722891c7ce4ca084cf91fae183e))
    -   The migration was ~1-2s/file due to sequential S3 round-trips (HEAD + PUT +
    -   verify HEAD). Speed it up:
    -   --workers=N spawns N child processes, each handling a strided slice of the
    -   files (index % N == shard) for real concurrency on the I/O-bound uploads
    -   --skip-cloud-check skips the upfront HEAD (always upload, idempotent)
    -   --skip-verify skips the post-upload size re-check
    -   --offset for manual chunking
    -   Storage/Flysystem has no batch or async upload API, so process-level
    -   concurrency is the pragmatic lever here.
-   feat: add uploads/sec throughput counter to emoji migration ([f17a88e16](https://github.com/pixelfed/pixelfed/commit/f17a88e16c25588e17c36f96008180bb6c5de03c))
    -   Live 'up/s' rate shown on the progress bar during single-worker runs
    -   Final summary reports elapsed time and uploads/sec
    -   Parallel runs tally moved across workers and report aggregate uploads/sec
    -   Makes it easy to compare --workers counts and decide whether async S3
    -   (option 2) is worth pursuing.
-   perf: async S3 SDK upload path for emoji migration ([a0a262f07](https://github.com/pixelfed/pixelfed/commit/a0a262f07fcd370994efbb0e99e30cd7f4c7d1ec))
    -   Process-level workers plateaued at ~7.5 uploads/sec against Fastly Object
    -   Storage because each PUT is high-latency and only a handful ran concurrently.
    -   Add --concurrency=N which uses the AWS SDK CommandPool to keep N PutObject
    -   requests in flight from a single process. A successful PutObject response is
    -   the confirmation (no separate HEAD verify), and the local file is deleted on
    -   success. Commands are yielded lazily so memory stays flat over large runs.
    -   --no-acl escape hatch for S3-compatible stores that reject the ACL header.
-   Update EmojiMoveStorageLocalToCloud.php ([948da00f0](https://github.com/pixelfed/pixelfed/commit/948da00f0cb1efc57b6bd13f18f79358c8c34a8e))
-   revert: remove emoji local-to-cloud storage changes ([40b323bca](https://github.com/pixelfed/pixelfed/commit/40b323bca4f76625c46a5147fcf703dc6c045408))
    -   Back out all emoji cloud-storage work from staging so it can be reworked and
    -   re-landed separately (the URL resolution flips to cloud on a global config
    -   flag, which created a broken-URL window, and the migration approach needs
    -   revisiting).
    -   Reverts to pre-emoji state:
    -   CustomEmoji model URL/storage helpers (urlForPath, storageTarget, storeMedia,
    -   storeMediaFromFile, deleteMedia, url) and callers in ImportEmojis,
    -   CustomEmojiService, AdminController
    -   admin custom-emoji blade views back to local /storage URLs
    -   Remove admin:EmojiMoveStorageLocalToCloud command
    -   Remove the deploy migration and its scheduler entry
    -   Media (and the already-reverted story) scheduler entries are untouched.
-   chore: modernize service providers for Laravel 13 readiness ([3c6ba88e6](https://github.com/pixelfed/pixelfed/commit/3c6ba88e6e5cdc418e49d4f9f9b19144280e6eb6))
    -   Remove deprecated Foundation\\Support\\Providers\\AuthServiceProvider and
    -   EventServiceProvider base classes. Move policy and event listener
    -   registrations into AppServiceProvider using Gate::policy() and
    -   Event::listen(). Behavior is unchanged (verified via event:list and
    -   auth test suite).
-   perf: fix N+1 queries; fix ComposeController lint and test namespace ([b52c3d765](https://github.com/pixelfed/pixelfed/commit/b52c3d7659dbcf97dd77f5fe9bed4a6b4582577c))
    -   Performance:
    -   TrendingHashtagService: batch-load hashtags with whereIn/keyBy instead
    -   of Hashtag::find() per trending row.
    -   DirectMessageController: eager-load status.media and read the in-memory
    -   collection instead of firstMedia() issuing a query per DM message.
    -   GroupsSearchController: batch Profile/Follower/GroupInvitation lookups
    -   with whereIn instead of per-invitee queries.
    -   Lint/tests:
    -   ComposeController: whitespace formatting (Pint).
    -   ComposeControllerTest: correct App\\User to App\\Models\\User, fixing the
    -   larastan class.notFound error and import ordering.
    -   Full suite: 547 passed. Pint and PHPStan clean.
-   chore: uplift framework skeleton toward Laravel 12 defaults ([4e83eb086](https://github.com/pixelfed/pixelfed/commit/4e83eb0867c11f80432749b69d7d0b83e1963513))
    -   Modernize artisan and public/index.php to the streamlined bootstrap form,
    -   migrate factories/seeders autoload to PSR-4 (database/seeds -> seeders), and
    -   backfill missing env-driven config keys across app, session, database, queue,
    -   mail, logging, and cache. All changes are additive and preserve existing
    -   Pixelfed behavior and defaults.
-   Update composer.json ([54a0b3ee9](https://github.com/pixelfed/pixelfed/commit/54a0b3ee96475cf61207b6596c0a4ddff9a53ec4))
-   Revert "Merge pull request #6981 from pixelfed/chore/laravel12-skeleton-uplift" ([7aa1f8936](https://github.com/pixelfed/pixelfed/commit/7aa1f8936dcee49c5aca250083d98267197bb850))
    -   This reverts commit 39384e2f9339505a776964bffdac5cf15ba4f2f8, reversing
    -   changes made to f76bcfa3f9478cadb2c5bc8bfeb04dacf89788fb.
-   chore: uplift framework skeleton toward Laravel 12 defaults ([80214c5e7](https://github.com/pixelfed/pixelfed/commit/80214c5e71ce9f91d795f83df6abedbdefe19463))
    -   Modernize artisan and public/index.php to the streamlined bootstrap form,
    -   migrate factories/seeders autoload to PSR-4 (database/seeds -> seeders), and
    -   backfill missing env-driven config keys across app, session, database, queue,
    -   mail, logging, and cache. All changes are additive and preserve existing
    -   Pixelfed behavior and defaults.
-   Update composer.json ([b86e4f731](https://github.com/pixelfed/pixelfed/commit/b86e4f7318cd6eb318f14ae379b87dd3b9fe65f2))
-   refactor: rename MigrateLocalS3MediaURL class and move media move-storage commands to unstable ([41c9b8830](https://github.com/pixelfed/pixelfed/commit/41c9b8830f2570198b1e2502d7a201131fdc415a))
    -   Rename App\\Console\\Commands\\Admin\\MigrateLocalS3MediaURL to MediaUpdateS3CDNUrl
    -   (class + filename only; the admin:MigrateLocalS3MediaURL signature is unchanged)
    -   Move the three MediaMoveStorage{LocalToCloud,CloudToLocal,CloudToCloud} commands
    -   into App\\Console\\Commands\\Admin\\Unstable and change their signatures from
    -   admin: to unstable:
    -   Update the scheduler in bootstrap/app.php, the command README, the
    -   config/filesystems.php reference comment, and the affected feature tests
-   Update filesystems.php ([d18e87133](https://github.com/pixelfed/pixelfed/commit/d18e871338c7701c927d78730737643c21456598))
-   refactor: keep MediaMoveStorageLocalToCloud as a stable admin command ([a81270770](https://github.com/pixelfed/pixelfed/commit/a81270770cf4886b4c923acd3605241b4bbacd87))
    -   MediaMoveStorageLocalToCloud is stable, so move it back out of the Unstable
    -   namespace: restore App\\Console\\Commands\\Admin\\MediaMoveStorageLocalToCloud and
    -   its admin:MediaMoveStorageLocalToCloud signature, and update the scheduler,
    -   README, and feature test. CloudToLocal and CloudToCloud remain under unstable:.
-   feat: storage:maintenance command + in-flow cleanup of emptied dirs ([400f00c5e](https://github.com/pixelfed/pixelfed/commit/400f00c5e13c0e8b7fdbf22d2a506cb5adbc717a))
    -   Replace the remcache GC (GarbageCollectorRemcache / gc:remcache) with a
    -   broader storage:maintenance command that sweeps stale remcache temp files and
    -   recursively removes the random empty directories accumulated under the media,
    -   story, avatar and import trees (--hours/--only/--except/--dry-run), scheduled
    -   daily.
    -   Fix the root causes so flows clean up after themselves rather than relying on
    -   the sweep:
    -   MediaDeletePipeline removes its own emptied m/\_v2 leaf dir
    -   AvatarOptimize logs the previously-swallowed exception, still cleans up the
    -   old avatar on failure, and removes the old file's now-empty splayed dir
    -   AvatarController::deleteAvatar removes the emptied splayed dir
    -   StoryExpire/StoryDelete remove the story's own emptied leaf dir
    -   TransformImports removes imports/{userId} once its files are moved out
    -   StoryFetch cleans up its remcache temp file in a finally block
-   polish ([8ca3ac4dc](https://github.com/pixelfed/pixelfed/commit/8ca3ac4dccfd6760548011cd527abaa67d2aa3b6))
-   refactor: simplify storage:maintenance flags and make it quiet by default ([fce75030e](https://github.com/pixelfed/pixelfed/commit/fce75030e0c4298759f64800fa8a6f843ef26626))
    -   Drop --except (--only already covers task selection)
    -   Quiet by default; per-root/per-item and summary lines now require -v/--verbose
    -   Errors are always shown regardless of verbosity
    -   Document the command in the console README
-   fix: delete superseded image/thumbnail files instead of orphaning them ([b6d645a4d](https://github.com/pixelfed/pixelfed/commit/b6d645a4d48cb36dd930b53742d24d5adc9508dd))
    -   Image::handleImageTransform derives the output filename from the current
    -   media_path and applies the encoder's output extension. When that differs from
    -   what is already stored (heic/avif -> jpg, or a thumbnail regenerated to a new
    -   extension), the new file landed at a different path and the previous file was
    -   left orphaned in the media directory — the source of the leftover \_thumb files
    -   under public/m/\_v2.
    -   Capture the path each transform supersedes and delete it after a successful
    -   write (only when the new output path differs, so we never delete what we just
    -   wrote). Remove the stale MediaDeleteLeafCleanupTest whose source change is not
    -   in the tree.
-   Add domain-mismatch metadata to Announce status fetch and stop noisy ERROR logs ([0df4c7117](https://github.com/pixelfed/pixelfed/commit/0df4c7117d089c14b01533e2391170683edd728b))
    -   storeStatus() now throws with JSON metadata (checked id/url hosts, expected
    -   rule, and the full activity payload) when status domains mismatch. The Announce
    -   inbox handler catches this, logs the context at debug level, and returns
    -   gracefully instead of surfacing a full production ERROR stack trace.
-   Add structured metadata to MediaDeletePipeline skip/failure logs ([d456b7b64](https://github.com/pixelfed/pixelfed/commit/d456b7b64a4fd3589a96da11f66de58d5c7809b2))
    -   Replace interpolated log strings with structured context (media/status/profile/
    -   user ids, mime, size, order, paths, hls_path, remote flag, timestamps) so
    -   operators can trace why orphan-purge deletions are skipped or fail.
-   Add admin:resyncemoji command to re-download remote emoji locally ([9214e9680](https://github.com/pixelfed/pixelfed/commit/9214e9680ab8cdf4507574cb3d4f085ed4a5203a))
    -   Adds CustomEmojiService::resync() which re-fetches a remote custom emoji's
    -   media from its origin (image_remote_url) via the SSRF-hardened
    -   SecureMediaFetchService and stores it locally under public/{media_path},
    -   reusing the existing headCheck validation and cache busting.
    -   The admin:resyncemoji command takes a comma-separated list of emoji
    -   filenames, looks each up by media_path, and resyncs remote ones. Supports
    -   --missingonly, --dry-run and --force.
-   Fix remote status deletion leaking attached media, add status:media command ([69869536a](https://github.com/pixelfed/pixelfed/commit/69869536a1972d0505429d2e5d0fedd4ef6c2025))
    -   MediaDeletePipeline skips deletion when media->status_id is set. status_id has
    -   no FK/cascade, so deleting a status never clears it, and the delete jobs
    -   dispatched by the status-delete paths were always skipped, leaking media files.
    -   Detach media (status_id = null) before dispatching the delete in StatusDelete,
    -   RemoteStatusDelete and DeleteRemoteStatusPipeline, so the row is genuinely
    -   orphaned by the time the guard checks it and the deletion proceeds.
    -   Also adds a status:media diagnostic command that dumps all metadata for a
    -   media id (DB columns, computed URLs, attachment state, parent status including
    -   the dangling status_id case, owner, metadata, and an optional live URL check).
-   Add media:maintenance command with orphanedMedia scope ([87dad44d0](https://github.com/pixelfed/pixelfed/commit/87dad44d092ab937eea1c385c1724ec732d103a2))
    -   media:maintenance --scope orphanedMedia cleans up media whose status_id
    -   references a status that no longer exists (hard-deleted) or is soft-deleted.
    -   These dangling references predate the delete-path fix and MediaDeletePipeline's
    -   attached guard would otherwise refuse to delete them, leaking files.
    -   Detaches (status_id = null) before dispatching deletion via MediaStorageService,
    -   so the guard sees a genuinely orphaned row. Supports --limit (batched),
    -   --dry-run, and --force. The --scope map is extensible for future routines.
-   Add verbose output to media:maintenance ([48a1fe5e5](https://github.com/pixelfed/pixelfed/commit/48a1fe5e5e8f7088e9f1b2d515f2355c8cfb8631))
    -   With -v, print per-row detail (media_id, original status_id, remote_media,
    -   profile_id, mime, size, path) as each orphaned row is processed instead of the
    -   progress bar, and expand the dry-run table with extra columns. Uses Laravel's
    -   built-in verbosity flag.
-   Add TODO.md; enhance media:maintenance with --server filter and state annotations ([838a6b999](https://github.com/pixelfed/pixelfed/commit/838a6b999cc710d3c91f55d84003f6fdc1b38cb6))
    -   TODO.md: capture follow-ups (centralized status media teardown, DM leak fix,
    -   no-DB-cascade rationale, remote-edit orphaning, media:gc re-check).
    -   media:maintenance: add --server remote|local|both (default both) to filter
    -   orphaned media by origin.
    -   Annotate each row with status state (live/soft-deleted/hard-deleted) next to
    -   status_id and profile state (live/soft-deleted/hard-deleted) next to
    -   profile_id, in both dry-run table and verbose run output. States are resolved
    -   in batched, trashed-aware queries.
-   Remove TODO.md ([6355caa90](https://github.com/pixelfed/pixelfed/commit/6355caa903fa881f894724093eb43908ae51beb7))
    -   Drop the TODO.md added in 838a6b9; keep the media:maintenance changes.
-   Add --status and --profile state filters to media:maintenance ([4ad6e91ef](https://github.com/pixelfed/pixelfed/commit/4ad6e91ef92232d2f9ed5c84c53db04afd7c0e9b))
    -   --status live|soft|hard and --profile live|soft|hard narrow orphaned media by
    -   the lifecycle state of the referenced status/profile row. Filters are applied
    -   at the SQL level (whereExists/whereNotExists on deleted_at) so they compose
    -   correctly with --limit. --status=live short-circuits since orphaned media never
    -   has a live status. Options are validated up front.
-   Drop live from --status on media:maintenance ([4dfb34de7](https://github.com/pixelfed/pixelfed/commit/4dfb34de75cfb02efae08dc3b72fd5ac948ec8ca))
    -   Orphaned media never references a live status, so --status only accepts soft
    -   and hard. --profile still accepts live/soft/hard. Removes the now-redundant
    -   live short-circuit and makes valid values per-option.
-   Rename media:maintenance to media:filtercleanup ([d62c58988](https://github.com/pixelfed/pixelfed/commit/d62c58988193a081980ea5afbd1a2eb585947294))
    -   Rename the command signature (media:maintenance -> media:filtercleanup), class
    -   (MediaMaintenance -> MediaFilterCleanup), and file to match. Behavior
    -   unchanged.
-   Rename status:post to status:statuses ([237ed61b5](https://github.com/pixelfed/pixelfed/commit/237ed61b5d2b5e1f9d32c07c25a1a1e2b3bf1e7a))
    -   Rename command signature (status:post -> status:statuses), class
    -   (StatusPost -> StatusStatuses), and file to match.
-   Add status:instance, status:avatar, status:emoji inspector commands ([9888923a8](https://github.com/pixelfed/pixelfed/commit/9888923a848046d088257e5c05bb460399d91db9))
    -   Diagnostic commands mirroring status:statuses/status:media:
    -   status:instance {id|domain|url|@user@domain}: instance row, moderation
    -   state (banned/unlisted/auto_cw), sync timestamps, local profile count.
    -   status:avatar {avatar_id|profile_id} \[--check\]: avatar row, storage state
    -   (local/cloud existence), owning profile, optional live HEAD on remote_url.
    -   status:emoji {id|:shortcode:|filename} \[--check\]: emoji row, origin
    -   (local/remote), local file existence, optional live HEAD on image_remote_url.
    -   Also includes the status:post -> status:statuses rename.
-   Update AdminReportController.php ([10ae3fa8c](https://github.com/pixelfed/pixelfed/commit/10ae3fa8cbfbc1f4d582983d52bef36ab16c3b13))
-   Add success message for profile update action ([45e4de392](https://github.com/pixelfed/pixelfed/commit/45e4de3928c115b571fa117eda40f2f830abf6ab))
-   Update ApiV1Controller.php ([7c3644c3e](https://github.com/pixelfed/pixelfed/commit/7c3644c3e19082c09bc913a35df15e6b248fbc0d))
-   Remove 'true' argument from usernameToId call ([c623a7afb](https://github.com/pixelfed/pixelfed/commit/c623a7afbf80e1826936c04b6f8621ff054014e0))
-   Add Sanctum support ([584ce27f7](https://github.com/pixelfed/pixelfed/commit/584ce27f7142f472677d1c8e03168d3713075c67))
-   Create 2019_12_14_000001_create_personal_access_tokens_table.php ([8e7b368ea](https://github.com/pixelfed/pixelfed/commit/8e7b368ea25859b58eebbd827ad1f7ca852c0318))
-   Lint ([c19fd269b](https://github.com/pixelfed/pixelfed/commit/c19fd269b1bd780c55ea5fc6cfe8f7c9a558a2b0))
-   Update composer.json ([8a0368ab0](https://github.com/pixelfed/pixelfed/commit/8a0368ab00a2f557a3a8ae867fb92edf91580919))
-   Update Report endpoint, add support for optional message ([ccac8b31b](https://github.com/pixelfed/pixelfed/commit/ccac8b31bd11af192adeea234997f63c4bb46c26))
-   Update ASF ([5468eaeb5](https://github.com/pixelfed/pixelfed/commit/5468eaeb56b1452e7f17b36b6352d2e13cf6b052))
-   Fix reblog handling ([a6117a240](https://github.com/pixelfed/pixelfed/commit/a6117a2407e8b021db916df627acba57e7af27df))
-   composer ([595620e5b](https://github.com/pixelfed/pixelfed/commit/595620e5b855fc6b51564895b28856d2fb44c716))
-   Update dependabot.yml ([07b5c1fd3](https://github.com/pixelfed/pixelfed/commit/07b5c1fd34d340908622d6864e2e6542667eabfe))
-   Drop the no-op pf_type assignment in the group topic feed ([71cade540](https://github.com/pixelfed/pixelfed/commit/71cade540ac3b1e0b7f442b6185ee44bf2824d66))
-   Update ApiV1Controller, fix napi in timelines ([4c4a457fe](https://github.com/pixelfed/pixelfed/commit/4c4a457fe4d7ce96a0c391036aec55ac557f5c37))
-   chore(deps): bump postcss-selector-parser ([e5bda87a1](https://github.com/pixelfed/pixelfed/commit/e5bda87a10744d31eee38a84bd7a353114adc6b8))
    -   Bumps and \[postcss-selector-parser\](https://github.com/postcss/postcss-selector-parser). These dependencies needed to be updated together.
    -   Updates `postcss-selector-parser` from 7.1.1 to 7.1.5
    -   \[Release notes\](https://github.com/postcss/postcss-selector-parser/releases)
    -   \[Changelog\](https://github.com/postcss/postcss-selector-parser/blob/main/CHANGELOG.md)
    -   \[Commits\](https://github.com/postcss/postcss-selector-parser/compare/v7.1.1...7.1.5)
    -   Updates `postcss-selector-parser` from 6.1.2 to 6.1.4
    -   \[Release notes\](https://github.com/postcss/postcss-selector-parser/releases)
    -   \[Changelog\](https://github.com/postcss/postcss-selector-parser/blob/main/CHANGELOG.md)
    -   \[Commits\](https://github.com/postcss/postcss-selector-parser/compare/v7.1.1...7.1.5)
    ***
    -   updated-dependencies:
    -   dependency-name: postcss-selector-parser
    -   dependency-version: 7.1.5
    -   dependency-type: indirect
    -   dependency-name: postcss-selector-parser
    -   dependency-version: 6.1.4
    -   dependency-type: indirect
    -   ...
-   chore(deps): bump ip-address from 10.2.0 to 10.7.0 ([66f8b61ef](https://github.com/pixelfed/pixelfed/commit/66f8b61ef76a9c1acc067c8f7738df6862a14170))
    -   Bumps \[ip-address\](https://github.com/beaugunderson/ip-address) from 10.2.0 to 10.7.0.
    -   \[Release notes\](https://github.com/beaugunderson/ip-address/releases)
    -   \[Commits\](https://github.com/beaugunderson/ip-address/compare/v10.2.0...v10.7.0)
    ***
    -   updated-dependencies:
    -   dependency-name: ip-address
    -   dependency-version: 10.7.0
    -   dependency-type: indirect
    -   ...
-   chore(deps): bump browserslist from 4.28.2 to 4.28.8 ([ec8fa5f61](https://github.com/pixelfed/pixelfed/commit/ec8fa5f619da85f082dd1996dd29f32472549631))
    -   Bumps \[browserslist\](https://github.com/browserslist/browserslist) from 4.28.2 to 4.28.8.
    -   \[Release notes\](https://github.com/browserslist/browserslist/releases)
    -   \[Changelog\](https://github.com/browserslist/browserslist/blob/main/CHANGELOG.md)
    -   \[Commits\](https://github.com/browserslist/browserslist/compare/4.28.2...4.28.8)
    ***
    -   updated-dependencies:
    -   dependency-name: browserslist
    -   dependency-version: 4.28.8
    -   dependency-type: indirect
    -   ...
-   chore(deps)(deps-dev): bump laravel/telescope from 5.22.1 to 5.23.0 ([447b76fed](https://github.com/pixelfed/pixelfed/commit/447b76fedead7660477c52782ff68f40a749dc3c))
    -   Bumps \[laravel/telescope\](https://github.com/laravel/telescope) from 5.22.1 to 5.23.0.
    -   \[Release notes\](https://github.com/laravel/telescope/releases)
    -   \[Changelog\](https://github.com/laravel/telescope/blob/5.x/CHANGELOG.md)
    -   \[Commits\](https://github.com/laravel/telescope/compare/v5.22.1...v5.23.0)
    ***
    -   updated-dependencies:
    -   dependency-name: laravel/telescope
    -   dependency-version: 5.23.0
    -   dependency-type: direct:development
    -   update-type: version-update:semver-minor
    -   ...
-   chore(deps)(deps): bump laravel/tinker from 2.11.1 to 3.0.2 ([7eded54f1](https://github.com/pixelfed/pixelfed/commit/7eded54f1c34570f3185f9850e6c44fcdd70f0ad))
    -   Bumps \[laravel/tinker\](https://github.com/laravel/tinker) from 2.11.1 to 3.0.2.
    -   \[Release notes\](https://github.com/laravel/tinker/releases)
    -   \[Changelog\](https://github.com/laravel/tinker/blob/3.x/CHANGELOG.md)
    -   \[Commits\](https://github.com/laravel/tinker/compare/v2.11.1...v3.0.2)
    ***
    -   updated-dependencies:
    -   dependency-name: laravel/tinker
    -   dependency-version: 3.0.2
    -   dependency-type: direct:production
    -   update-type: version-update:semver-major
    -   ...
-   chore(deps): bump fast-uri from 3.1.2 to 3.1.6 ([0eedf0dee](https://github.com/pixelfed/pixelfed/commit/0eedf0deefebecc1a817e92c5ed0e0400eb31a5b))
    -   Bumps \[fast-uri\](https://github.com/fastify/fast-uri) from 3.1.2 to 3.1.6.
    -   \[Release notes\](https://github.com/fastify/fast-uri/releases)
    -   \[Commits\](https://github.com/fastify/fast-uri/compare/v3.1.2...v3.1.6)
    ***
    -   updated-dependencies:
    -   dependency-name: fast-uri
    -   dependency-version: 3.1.6
    -   dependency-type: indirect
    -   ...
-   Update dependabot.yml ([93e8c7d86](https://github.com/pixelfed/pixelfed/commit/93e8c7d8680399c61fd5375751ea2db1d84b1a23))
-   Add only_reposts ([9e33bed63](https://github.com/pixelfed/pixelfed/commit/9e33bed630d0cd8b4da3121975e5fefc8b43f253))
-   chore(deps)(deps): bump blurhash from 1.1.5 to 2.0.5 ([f428ff6fb](https://github.com/pixelfed/pixelfed/commit/f428ff6fb5cbb8f68ff6c4cc662f336e880f940f))
    -   Bumps \[blurhash\](https://github.com/woltapp/blurhash) from 1.1.5 to 2.0.5.
    -   \[Commits\](https://github.com/woltapp/blurhash/commits)
    ***
    -   updated-dependencies:
    -   dependency-name: blurhash
    -   dependency-version: 2.0.5
    -   dependency-type: direct:production
    -   update-type: version-update:semver-major
    -   ...
-   Fix silent failure in avatar upload endpoints ([29280cd95](https://github.com/pixelfed/pixelfed/commit/29280cd950cc3ddecc4262cadab2a7262b6fd2cc))
    -   AvatarController@store and BaseApiController@avatarUpdate wrapped the
    -   upload flow in an empty catch(\\Exception) block and returned a success
    -   response even when the upload or save failed.
    -   Log the exception and return a real error response (500 JSON for the
    -   API endpoint, a redirect with validation errors for the web endpoint).
    -   Adds regression tests covering the failure path, the success path, and
    -   non-image rejection.
-   Mark direct messages read with a single bulk update ([fbfd26d77](https://github.com/pixelfed/pixelfed/commit/fbfd26d7759a67caa48b10e485ba3b2a6d95570d))
    -   DirectMessageController@read fetched every matching DirectMessage and
    -   saved each one individually in a loop, issuing one UPDATE per row. On an
    -   active thread this is N queries.
    -   Pluck the matching ids and perform a single bulk update, preserving the
    -   existing response (the list of affected message ids) and updated_at
    -   behaviour.
    -   Adds regression tests covering the marked-read ids, the status_id lower
    -   bound, and sender isolation.
-   Add timeout, retry and error handling to remote auth HTTP calls ([2ad6e2831](https://github.com/pixelfed/pixelfed/commit/2ad6e28318731c5d34c4fff48495d8f7b06209f0))
    -   RemoteAuthService::getVerifyCredentials, getFollowing and getToken made
    -   outbound HTTP requests to a user-controlled remote instance during the
    -   Mastodon login flow with no timeout, no retry and no exception handling.
    -   A slow or hostile instance could hang the request or surface an uncaught
    -   exception.
    -   Wrap all three in timeout(20)->retry(3, 750) with try/catch that returns
    -   false on failure, matching the existing pattern in isDomainCompatible().
    -   Callers already treat a falsy return as a failure; add the missing guard
    -   at the one verify_credentials call site that accessed the result array
    -   without checking it first.
    -   Adds RemoteAuthServiceTest covering connection failure, server error and
    -   success paths.
-   Compute Year-in-Review averages in SQL instead of in PHP ([cd873c897](https://github.com/pixelfed/pixelfed/commit/cd873c89764583168c871cb88eb710961afbc580))
    -   SeasonalController::getData computed the average posts/likes per profile
    -   by grouping in SQL, then pulling every grouped row into a collection and
    -   calling ->pluck('count')->avg() in PHP. This loaded one row per profile
    -   into memory just to average.
    -   Wrap the grouped per-profile counts in a subquery and let the database
    -   compute AVG(count), returning a single value. Also drops the invalid
    -   SELECT _ with GROUP BY (ONLY_FULL_GROUP_BY) by selecting count(_) only.
    -   Adds a test verifying the average-of-per-profile-counts and its
    -   exclusions (remote, wrong type, out-of-range date), plus the empty case.
-   Extract duplicated blocked-id and duplicate-shortcode query patterns ([e4e12fad7](https://github.com/pixelfed/pixelfed/commit/e4e12fad7cffee99ef017c30d03037fafb9bfae3))
    -   Two query patterns were copy-pasted across several call sites:
    -   The 'users who blocked me, plus myself' list used to filter profile
    -   search (UserFilter::whereFilterableId($pid)->pluck('user_id')->push($pid))
    -   appeared in ComposeController (x2) and DirectMessageController. Extracted
    -   to UserFilterService::searchExcludedProfileIds(). Note this is the
    -   inverse of blocks() (who I blocked), so it is a distinct method.
    -   CustomEmoji duplicate detection (groupBy('shortcode')->havingRaw(
    -   'count(\*) > 1')) appeared three times in AdminController. Extracted to a
    -   CustomEmoji::duplicateShortcodes() query scope.
    -   Adds tests for both. No behaviour change.
-   Remove dead debug methods that echoed the raw request ([661b84142](https://github.com/pixelfed/pixelfed/commit/661b841428f76aa5a1a5ab375622bf4737c40fe1))
    -   CollectionController::index and StoryComposeController::createPoll had no
    -   route mapping and simply returned $request->all(). Both are unreachable
    -   debug leftovers; remove them. The live poll route maps to
    -   ComposeController::createPoll, which is unaffected.
-   Stream deletions with cursor and batch notification lookups in delete jobs ([c4e5b96d2](https://github.com/pixelfed/pixelfed/commit/c4e5b96d25bc1694c1469a79b7c71907f60dd426))
    -   The status- and account-deletion jobs loaded whole collections with
    -   ->get() and then looped, running a per-row Notification lookup inside
    -   each iteration.
    -   StatusDelete / RemoteStatusDelete: resolve associated DirectMessage and
    -   MediaTag ids, fetch their notifications in a single whereIn query,
    -   clear each via cursor (NotificationService::del must run per row for
    -   cache/redis cleanup), then bulk delete the DMs and media tags.
    -   DeleteAccountPipeline / DeleteRemoteProfilePipeline: stream Story and
    -   Collection deletions with cursor() instead of loading every row into
    -   memory. Per-row file unlink and item deletes are preserved.
    -   Adds StatusDeleteCleanupTest covering DM + notification cleanup, media
    -   tag + notification cleanup, and the no-associations case.
-   Extract following-ids lookup into FollowerService::getFollowingIds ([667f6e2fc](https://github.com/pixelfed/pixelfed/commit/667f6e2fc9a5dbfbcc24b65771f25c2e83153eff))
    -   The Cache::remember('profile:following:'.$pid, ...) block that plucks
    -   following_id and appends the caller's own id was copy-pasted across four
    -   call sites, with inconsistent TTLs (1440 minutes vs 1209600 seconds).
    -   Add FollowerService::getFollowingIds($pid), which owns the cache key that
    -   add()/remove() already invalidate, and use it from InternalApiController,
    -   PublicApiController, ApiV1Controller and HashtagUnfollowPipeline. Removes
    -   the now-unused Follower/Cache imports left behind.
    -   Adds a test covering the followed-ids-plus-self result and the
    -   follows-nobody case.
-   Refactor NotificationService ([c9b0ee3bd](https://github.com/pixelfed/pixelfed/commit/c9b0ee3bddb95492d618bab3c4ac47c7376b4387))
-   chore(deps): bump svgo from 2.8.2 to 2.8.4 ([55b932d77](https://github.com/pixelfed/pixelfed/commit/55b932d777fd3fcac03cbfdc075f7d048daf21cb))
    -   Bumps \[svgo\](https://github.com/svg/svgo) from 2.8.2 to 2.8.4.
    -   \[Release notes\](https://github.com/svg/svgo/releases)
    -   \[Commits\](https://github.com/svg/svgo/compare/v2.8.2...v2.8.4)
    ***
    -   updated-dependencies:
    -   dependency-name: svgo
    -   dependency-version: 2.8.4
    -   dependency-type: indirect
    -   ...
-   chore(deps): bump body-parser from 1.20.5 to 1.20.6 ([50ea4a9b4](https://github.com/pixelfed/pixelfed/commit/50ea4a9b4699a4d973a99569d17c747e15de86b1))
    -   Bumps \[body-parser\](https://github.com/expressjs/body-parser) from 1.20.5 to 1.20.6.
    -   \[Release notes\](https://github.com/expressjs/body-parser/releases)
    -   \[Changelog\](https://github.com/expressjs/body-parser/blob/master/HISTORY.md)
    -   \[Commits\](https://github.com/expressjs/body-parser/compare/1.20.5...1.20.6)
    ***
    -   updated-dependencies:
    -   dependency-name: body-parser
    -   dependency-version: 1.20.6
    -   dependency-type: indirect
    -   ...
-   Fix Larastan error: correct Status import in NotificationService ([ef7e485e7](https://github.com/pixelfed/pixelfed/commit/ef7e485e7d9109bdc0b5d4966b09da38c57faf38))
    -   Use App\\Models\\Status instead of the non-existent App\\Status class.
-   Fix media storage migration crash when no .env file exists ([6b14b229d](https://github.com/pixelfed/pixelfed/commit/6b14b229d1e6275245c16c4ba20c1be5a965033b))
    -   The media storage migration commands read/parsed the .env file directly to
    -   check and flip PF_ENABLE_CLOUD. In containerized deploys there is no .env on
    -   disk (config is injected via env vars), so updateEnvFile() threw
    -   'file_get_contents(.env): Failed to open stream' and the scheduled command
    -   exited 1.
    -   Check the live setting via config_cache('pixelfed.cloud_storage') like the
    -   rest of the app, instead of parsing .env.
    -   Make the .env write best-effort in ManagesMediaStorageEnv: skip gracefully
    -   when the file is missing or read-only, and still apply the runtime + DB
    -   config-cache updates (the load-bearing changes on a hot server).
    -   Apply the same fix to the sibling unstable:MediaMoveStorageCloudToLocal.
    -   Add a regression test covering the no-.env container scenario.
-   Add per-file transfer output and --debug detail to MediaMoveStorageLocalToCloud ([b8ca4da3a](https://github.com/pixelfed/pixelfed/commit/b8ca4da3a6bdece89c7d89bf68cbe15c0565fa0d))
-   chore: move resources/lang to top-level lang/ per Laravel 9+ convention ([9db2218ca](https://github.com/pixelfed/pixelfed/commit/9db2218ca62a547976d57b13c286958b8789566b))
    -   Relocate translation files from resources/lang to lang/ via git mv
    -   Update PHP references to use the lang_path() helper
    -   Update crowdin.yml source/translation paths
    -   Update phpstan.neon translationDirectories
-   polish ([24fd8c5cb](https://github.com/pixelfed/pixelfed/commit/24fd8c5cbb1e151bb33f39ed35198f39de3d41b1))
-   Update app.php ([5cc0a68ff](https://github.com/pixelfed/pixelfed/commit/5cc0a68ffed4b7827ce316a6fb13e8f397774efb))
-   Update Localization.php ([7cf6e7714](https://github.com/pixelfed/pixelfed/commit/7cf6e771482edb5de945645bde085c4b1a02fa80))
-   Convert string class references to ::class ([6d8ad3885](https://github.com/pixelfed/pixelfed/commit/6d8ad3885a8d90541d2363334abab8ed030f9309))
    -   Applies the ::class conversion from pixelfed-staging PR #9 (patch 1/21),
    -   formatted with Pint (short imported ::class form). Excludes the
    -   ModelNamespaceMigrationTest namespace assertions, which intentionally
    -   compare against literal namespace strings.
-   Convert optional() to nullsafe operator ([042ab0a6e](https://github.com/pixelfed/pixelfed/commit/042ab0a6e42255eb87bfc3e74ff94c00405b6ea2))
    -   Applies patch 2/21 from pixelfed-staging PR #9: replaces optional($x)->y
    -   with $x?->y across 16 files. Pint-clean.
-   Remove unnecessary $model property from factories ([8140ef7b0](https://github.com/pixelfed/pixelfed/commit/8140ef7b028ee1194101f6992723b11a7bb76017))
    -   Applies patch 3/21 from pixelfed-staging PR #9: removes the redundant
    -   protected $model property from 5 factories (Laravel resolves the model
    -   from the factory name). Unused imports dropped via Pint. Verified
    -   factories still resolve their models and affected tests pass.
-   Convert route options to fluent methods ([c0f3469ee](https://github.com/pixelfed/pixelfed/commit/c0f3469ee2b1a45aaa9fbdcf40c28318680b3b3c))
    -   Laravel 8 adopts the tuple syntax for controller actions. Since the old options array is incompatible with this syntax, Shift converted them to use modern, fluent methods.
-   Update dependabot.yml ([5d542d209](https://github.com/pixelfed/pixelfed/commit/5d542d209cf6dde562e52d30308273307efaac18))
-   Default core files ([e973ddcfa](https://github.com/pixelfed/pixelfed/commit/e973ddcfa439fc5bb448aacd41430324ceff214d))
-   Rename Bootstrap 3 pagination templates ([938f0cf20](https://github.com/pixelfed/pixelfed/commit/938f0cf205c632c294dca362a573506dee15d58a))
-   Shift `ENV` variables ([e4120ce10](https://github.com/pixelfed/pixelfed/commit/e4120ce106ca1b2d80ae3d27bb96cd2f889e4e55))
-   Default config files ([c81c4be68](https://github.com/pixelfed/pixelfed/commit/c81c4be6833efd6bfc22f84c877eb93a04e88fb7))
    -   In an effort to make upgrading the constantly changing config files easier, Shift defaulted them and merged your true customizations - where ENV variables may not be used.
-   Update session.php ([1b034aa42](https://github.com/pixelfed/pixelfed/commit/1b034aa429ebd3eb18b0ac2d60ac58d52935a9ba))
-   chore(deps-dev): bump larastan/larastan from 3.10.0 to 3.11.0 ([8f8b95e17](https://github.com/pixelfed/pixelfed/commit/8f8b95e170e08de4e69b5606a4ead51d8384e83a))
    -   Bumps \[larastan/larastan\](https://github.com/larastan/larastan) from 3.10.0 to 3.11.0.
    -   \[Release notes\](https://github.com/larastan/larastan/releases)
    -   \[Changelog\](https://github.com/larastan/larastan/blob/3.x/RELEASE.md)
    -   \[Commits\](https://github.com/larastan/larastan/compare/v3.10.0...v3.11.0)
    ***
    -   updated-dependencies:
    -   dependency-name: larastan/larastan
    -   dependency-version: 3.11.0
    -   dependency-type: direct:development
    -   update-type: version-update:semver-minor
    -   ...
-   Update DeleteAccountPipeline.php ([f51f1ef0d](https://github.com/pixelfed/pixelfed/commit/f51f1ef0db31459e4a905062865180baced5a119))
-   Update cache.php ([15d30f803](https://github.com/pixelfed/pixelfed/commit/15d30f803d4d7f0a699e617962384ef0cd134850))
-   Update broadcasting.php ([fff81fe59](https://github.com/pixelfed/pixelfed/commit/fff81fe596070a2b6d5a0ae39840ecd67f03f2b3))
-   Change default log stack from 'single' to 'daily' ([0200c9e0c](https://github.com/pixelfed/pixelfed/commit/0200c9e0cd82cb699f579476eb499834943f7d79))
-   Update session.php ([ab2447b0f](https://github.com/pixelfed/pixelfed/commit/ab2447b0f46d9d0fe3bf8d5b3a51be3f60970ea9))
-   Update cache.php ([5383c2ecc](https://github.com/pixelfed/pixelfed/commit/5383c2ecc9018389d61f0a274e8b71ff2b5f73c5))
-   Update database.php ([690d71069](https://github.com/pixelfed/pixelfed/commit/690d710693a1d9f05313c1d9fe1ad845fc3ccaac))
-   Update default mailer configuration to use MAIL_DRIVER ([1d712e147](https://github.com/pixelfed/pixelfed/commit/1d712e14782eb08efa3be0c343d3544a46e7b37c))
-   Update queue.php ([19c8bea00](https://github.com/pixelfed/pixelfed/commit/19c8bea00b3a506cf14375fffa25b4002ecbdca1))
-   Require ext-redis to support phpredis client ([dcc4f87ba](https://github.com/pixelfed/pixelfed/commit/dcc4f87ba0c4a4ded65d7320f4407990afe16d46))
    -   Add ext-redis as a required PHP extension so phpredis can be used as
    -   the Redis client without manual setup. predis remains available, so
    -   users can switch between REDIS_CLIENT=phpredis and predis freely.
-   Apply staged formatting and session config changes ([95e316e86](https://github.com/pixelfed/pixelfed/commit/95e316e86f24873b045b4cdec1fcb692653155af))
-   Update DeleteAccountPipeline.php ([8d375aed8](https://github.com/pixelfed/pixelfed/commit/8d375aed8a4bb034ff32c10368136120ee193b4b))
-   Fix PostEditModal. Closes #7084 ([d7cfd0720](https://github.com/pixelfed/pixelfed/commit/d7cfd072049811dd63f2d1d4f09593e3adca000f))
-   Update compiled assets ([a1c5bf062](https://github.com/pixelfed/pixelfed/commit/a1c5bf0626cdb9194a54038e740c14495ec8da50))
-   Update redirect route for 2FA setup ([0a39ccef9](https://github.com/pixelfed/pixelfed/commit/0a39ccef984e647992e364aea99775a18603aaf0))
-   Update MediaStorageService.php ([ce4df0092](https://github.com/pixelfed/pixelfed/commit/ce4df00921c33f873908018da5ffdf87edf3f74d))
-   Update password validation rule to include string and min length ([c2a568a5c](https://github.com/pixelfed/pixelfed/commit/c2a568a5cc0dc3e928f4f672bf0d25dad75d50d6))
-   Change 2FA code validation to require 6 digits ([76d4e1ab2](https://github.com/pixelfed/pixelfed/commit/76d4e1ab23c49f29032d2c3b7b1fc4e39555918d))
-   Update UpdatePersonValidator.php ([b62eafd05](https://github.com/pixelfed/pixelfed/commit/b62eafd054e09ec2f4ec152ed47bdc49793e316d))
-   Lint ([6f688a31d](https://github.com/pixelfed/pixelfed/commit/6f688a31d7fa1a2e55b74747da95f1504329d063))
-   Create PruneOldNotifications.php ([85fec3ac8](https://github.com/pixelfed/pixelfed/commit/85fec3ac82da8941866b15aaf9d4ba576c652e9e))
-   Create 2026_09_07_071046_add_deleted_at_profile_idindex_to_notifications_table.php ([d3746bc8c](https://github.com/pixelfed/pixelfed/commit/d3746bc8ceb1e3954a85a5d4cf07981e54ed8d59))
-   Fix media gc ([764a98437](https://github.com/pixelfed/pixelfed/commit/764a98437d8a67af4646c4b4004bc29e186454c2))
-   Update SiteController.php ([99a013acc](https://github.com/pixelfed/pixelfed/commit/99a013accc6157b3c353c1b92b5f1dc64efb394b))
-   Update SiteController.php ([3673cf30e](https://github.com/pixelfed/pixelfed/commit/3673cf30e4407e217d6895570d457a4e242631e1))
-   Update MediaMoveStorageLocalToCloud.php ([896342a57](https://github.com/pixelfed/pixelfed/commit/896342a57f4d4f09dd42ccf9c16d3f19f08b86b2))
-   Lint ([c07706a41](https://github.com/pixelfed/pixelfed/commit/c07706a415fa434f197fce12a543457fba3c6c18))
-   fix: correct SiteController view return types, drop ViewContract alias ([8b4a7d4e3](https://github.com/pixelfed/pixelfed/commit/8b4a7d4e3c8beb66aea8c7c4dc94a9c22465f9c0))
    -   An automated return-type pass aliased the view contract as ViewContract
    -   to avoid clashing with the imported View facade, but left four methods
    -   (curatedOnboarding, language, redirectUrl, followIntent) typed against
    -   the facade instead of the contract. That threw a TypeError on
    -   /auth/sign_up.
    -   Convert the four View::make() calls to the view() helper, drop the
    -   facade import, and use a single Illuminate\\Contracts\\View\\View import
    -   for all return types.
-   polish ([5bf04957d](https://github.com/pixelfed/pixelfed/commit/5bf04957d5711db1366366eadb164dde1dfd156c))
-   Add notification gc ([f37c5fc95](https://github.com/pixelfed/pixelfed/commit/f37c5fc95c51e6e6bfd2947bd8a07971cf5da79a))
-   Fix typo ([ac3421312](https://github.com/pixelfed/pixelfed/commit/ac34213121a1de6da6af6545b9cdead970265886))
-   Replace custom register token with spatie/laravel-honeypot ([ce4343e3e](https://github.com/pixelfed/pixelfed/commit/ce4343e3e26073abd0d9107e7ca02bd8799eba03))
    -   Swap the custom 'rt' register token anti-spam mechanism for
    -   spatie/laravel-honeypot on the registration and parental-controls
    -   invite flows.
    -   Add spatie/laravel-honeypot and publish config/honeypot.php
    -   Remove getRegisterToken() and the rt validation rule from RegisterController
    -   Replace the rt hidden field with the @honeypot directive in both forms
    -   Attach ProtectAgainstSpam middleware to POST /register and the
    -   parental-controls invite register route
    -   Update RegisterTest to disable honeypot for the valid registration case
-   Fix DeleteAccountPipeline ([40fedfca4](https://github.com/pixelfed/pixelfed/commit/40fedfca4f35337eec55ceae36d738d0ed1ad787))
-   Replace Auth::routes() with explicit auth route definitions ([889d9efe9](https://github.com/pixelfed/pixelfed/commit/889d9efe99bb12058b1514f4aca0a0cea041fe64))
    -   Expand the laravel/ui Auth::routes() helper into explicit route
    -   definitions for login, logout, registration and password reset. This
    -   removes the routing magic, makes every auth route visible in web.php,
    -   and lets the honeypot ProtectAgainstSpam middleware live directly on the
    -   single POST /register definition instead of a duplicate route.
    -   laravel/ui is retained since the Auth controllers still rely on its
    -   Illuminate\\Foundation\\Auth traits.
-   Update Helpers.php ([e57f7ccd3](https://github.com/pixelfed/pixelfed/commit/e57f7ccd3af0721133885552ab983bf125ed343f))
-   Update AP Helpers ([c558724e4](https://github.com/pixelfed/pixelfed/commit/c558724e470d84e82517731206240e7bf75b4ce1))
-   Update account statuses endpoint ([085eabccf](https://github.com/pixelfed/pixelfed/commit/085eabccf983a3a2064d117a434b9968c499254f))
-   Update ApiV1Controller.php ([7376a007a](https://github.com/pixelfed/pixelfed/commit/7376a007a5a4197db69288a2dac34dfd7e38ae98))
-   Fix tests ([1281fa375](https://github.com/pixelfed/pixelfed/commit/1281fa37515ceb54ab1be19680cac762f93584e2))
-   Fix test ([48750f707](https://github.com/pixelfed/pixelfed/commit/48750f707972d3ad7b995a661dbe53f5f3e7dbbe))
-   Remove blindKeyRotation method from InboxWorker ([767c6ec20](https://github.com/pixelfed/pixelfed/commit/767c6ec2019b19f34121f0767ff662f9a0422899))
    -   Removed the blindKeyRotation method and its associated logic.
-   Update InboxValidator.php ([56251cb05](https://github.com/pixelfed/pixelfed/commit/56251cb05f02ccf44c75e477d8a592cab69eb706))
-   Update DeleteWorker.php ([4acc0dd52](https://github.com/pixelfed/pixelfed/commit/4acc0dd528671942f385c9069e83a7673012756c))
-   Update AccountTransformer ([94b8fea32](https://github.com/pixelfed/pixelfed/commit/94b8fea32af013b2664a7efc338c6fa60605d761))
-   Fix accounts statuses max_id pagination returning duplicate boundary status ([3b951d41d](https://github.com/pixelfed/pixelfed/commit/3b951d41d8384a4a91258cc2c8d67b51e75fd859))
-   Fix StatusDelete crashing on soft-deleted owning profile ([6e7419bb9](https://github.com/pixelfed/pixelfed/commit/6e7419bb96374bf1f3c3662c5a1d9d90ad95516b))
-   Fix admin instance stats endpoint 404 on Postgres via strict is_admin check ([df1e771f9](https://github.com/pixelfed/pixelfed/commit/df1e771f930f9520099a9fb681294d98cffe3d5b))
-   Validate publicKey.id host on inbox actor ingest to prevent key_id poisoning ([1905da723](https://github.com/pixelfed/pixelfed/commit/1905da723d0507ebe0364337c0178f6fdb3c0053))
-   Guard hashtag follow against null profile for soft-deleted accounts ([8258a5a5f](https://github.com/pixelfed/pixelfed/commit/8258a5a5f88807ca29d8c063136331f95b0836fa))
-   Harden remote status update media fetch against SSRF ([856f2f8f2](https://github.com/pixelfed/pixelfed/commit/856f2f8f2d2225ab7df64aa0e2d9d08a8ca8ce7a))
-   Fix favourites pagination skipping one favourite per page boundary ([e79135a77](https://github.com/pixelfed/pixelfed/commit/e79135a771fd529e4d3dad54558a6f1eba7b531b))
-   Scope reclaim-username profile deletion and fail on surviving orphan ([a1724a4b1](https://github.com/pixelfed/pixelfed/commit/a1724a4b1cbb8b5ca4116082439f1c58732d618d))
-   Enforce poll scope authorization on vote endpoint ([a8a7a430d](https://github.com/pixelfed/pixelfed/commit/a8a7a430d77af79a6886cf66cc951c81b1e5bf5a))
-   Require visibility on collection store to match NOT NULL schema ([a28650962](https://github.com/pixelfed/pixelfed/commit/a286509622370d56d354c6e7abe73c73c409639e))
-   Update ApiV1Controller.php ([602e498f0](https://github.com/pixelfed/pixelfed/commit/602e498f0ac5785e7c14eaf9e8d691e425a357eb))
-   Fix directory listing reporting oauth and activitypub flags always true ([fe70cd115](https://github.com/pixelfed/pixelfed/commit/fe70cd115518b2c489a0b8743546fcd46bc13183))
-   Fix login activity groupBy returning stale rows and 500 on strict DBs ([3cb5b6e1f](https://github.com/pixelfed/pixelfed/commit/3cb5b6e1fff7fffdec443d0b2797388137238624))
-   Enforce pat_enabled kill-switch on personal access token renew ([d2b11a71b](https://github.com/pixelfed/pixelfed/commit/d2b11a71b37df32da638f09eb75dfdd370fb713b))
-   Fix isDomainCompatible throwing on non-json beagle response ([d51cf4ccc](https://github.com/pixelfed/pixelfed/commit/d51cf4ccc91ff5bdd4b213f02939d420c092f439))
-   Route StoryFetch outbound requests through SSRF-hardened fetch service ([9e84ad261](https://github.com/pixelfed/pixelfed/commit/9e84ad261d375cf8761615f2518dc79d25e6d511))
-   Fix registration form redirecting when max_users is falsy ([afcb68c18](https://github.com/pixelfed/pixelfed/commit/afcb68c183cb393a25b9ab869e2874415a1d4cac))
-   Fix custom filter rate-limit counter never expiring ([63e3c95fa](https://github.com/pixelfed/pixelfed/commit/63e3c95faead959b08ef89b005d597732738239a))
-   Invalidate latest-story cache on remote story expiry and null-guard latest() ([9850aac67](https://github.com/pixelfed/pixelfed/commit/9850aac676bf60d770c95d4aa1d1160a6092b4b3))
-   Detect OOB oauth client when redirect_uri omitted on authorize ([462b4bc0d](https://github.com/pixelfed/pixelfed/commit/462b4bc0da6770d2433d88dbb249d265375014d0))
-   Ignore own row when validating email update uniqueness ([53ad34b32](https://github.com/pixelfed/pixelfed/commit/53ad34b321f480c70c909c5735b6c3e9047539e5))
-   Send Pixelfed User-Agent on federated account deletion deliveries ([327348be0](https://github.com/pixelfed/pixelfed/commit/327348be02fe27499bcd7575cfd89f8bbd4cb72b))
-   Drop Instagram import job when profile is missing instead of crashing ([922d7f766](https://github.com/pixelfed/pixelfed/commit/922d7f766e851c0710e039dc908e76d60ff911dc))
-   Only dispatch SharePipeline for newly-created reblogs ([d190ba7b6](https://github.com/pixelfed/pixelfed/commit/d190ba7b66452b8546c65e24e281ef248805550e))
-   Use ILIKE for case-insensitive search on PostgreSQL ([613cf413d](https://github.com/pixelfed/pixelfed/commit/613cf413de1b76abc8f0d7594e10f4454271e5fd))
-   Escape user-provided content in curated register admin emails ([4df40cb77](https://github.com/pixelfed/pixelfed/commit/4df40cb77264ac4f2d5dd99b4736b06728ea3daa))
-   Check media blocklist before storing uploads to prevent orphaned files ([ec6827bae](https://github.com/pixelfed/pixelfed/commit/ec6827bae2f49f1ddaefaf7a4abb84480e3577f9))
-   Rate limit and audit-log 2FA checkpoint verification ([8cebb24c0](https://github.com/pixelfed/pixelfed/commit/8cebb24c04806501a7fbc65957aabc9fc803f42c))
-   Apply Pint lint fixes to session test files ([eb9bd1130](https://github.com/pixelfed/pixelfed/commit/eb9bd113034c0ccf0a5924b0881c436e6d0a3cc7))
-   Federate unlike before deleting Like so retries can deliver ([1ffda3eba](https://github.com/pixelfed/pixelfed/commit/1ffda3eba9768c3e323b6c7e4e16d7d6bad818c4))
-   Use intended-redirect session for authorize_interaction guest login ([0234a305a](https://github.com/pixelfed/pixelfed/commit/0234a305ae98704cf5e212744a8dee03a82a2f04))
-   Deterministically keep earliest status per uri in dedupe command ([e360fab61](https://github.com/pixelfed/pixelfed/commit/e360fab61982e74086cbe7ed6b7f12eb3d3d577a))
-   Update StoryApiV1Controller.php ([fc80bce46](https://github.com/pixelfed/pixelfed/commit/fc80bce46009e25edc063082301ca5ad62ff268c))
-   Trigger StatusHashtag observer on deletion to keep cached_count accurate ([444c796ba](https://github.com/pixelfed/pixelfed/commit/444c796bac5e618f9fcfe523f65f7fee87d805aa))
-   Fall back to stored profile when remote refresh fails ([73fb5ed69](https://github.com/pixelfed/pixelfed/commit/73fb5ed6963bd22dae1d732d33c43b525d35429b))
-   Filter null-account statuses from non-cached network timeline ([917a13d4a](https://github.com/pixelfed/pixelfed/commit/917a13d4a7cffe41dfebede849a26d4d7d64ca30))
-   Scope DangerZone OIDC sudo bypass to OIDC-registered users ([7483a4b05](https://github.com/pixelfed/pixelfed/commit/7483a4b05b45f93c5f87950c27f8090b4fd53c3d))
-   Clear 2FA session state on forced logout after failed attempts ([97f1a097f](https://github.com/pixelfed/pixelfed/commit/97f1a097ff122377e725abb9322033575bf15b0f))
-   Exclude private profiles from public directory and clear suggestable on going private ([158186309](https://github.com/pixelfed/pixelfed/commit/1581863093fa5d18142b893f54d2d3a867736659))
-   lint ([68becbe2c](https://github.com/pixelfed/pixelfed/commit/68becbe2cc0f4a10dd31f6d9a4962fe7204aba9b))
-   Require dangerzone sudo mode on curated register, shadow filter and page admin controllers ([742c1a6bc](https://github.com/pixelfed/pixelfed/commit/742c1a6bc82e0fcd8a44e01ea160cbd08bff4fc4))
-   Deliver posts regardless of profile no_autolink flag ([9e1415122](https://github.com/pixelfed/pixelfed/commit/9e141512283340399a75859e9f71850b4f8aa025))
-   Use indexed query for media blocklist lookups and allow removing inactive hashes ([e8f2b06af](https://github.com/pixelfed/pixelfed/commit/e8f2b06afe0cea77899a81ac8eda10dd44dc5e36))
-   Invalidate session on DangerZone forced logout to clear 2FA state ([58e8a4922](https://github.com/pixelfed/pixelfed/commit/58e8a4922dc684766cb7b46449797ca9b7ccb7df))
-   Refactor comments in DangerZone middleware ([519b1b94d](https://github.com/pixelfed/pixelfed/commit/519b1b94dc57668ce206056fd01ffd048bcbbd3b))
    -   Removed redundant comments to clarify code functionality.
-   Delete larastan ([171a027c5](https://github.com/pixelfed/pixelfed/commit/171a027c565bceba82c62cba06b1bc5f1d599550))
-   Update ApiV1Controller.php ([13aa36efb](https://github.com/pixelfed/pixelfed/commit/13aa36efb4533f9a77dc79f0928f0cbb226a7aa7))
-   Fix StoryIndexService ([57e7eef08](https://github.com/pixelfed/pixelfed/commit/57e7eef08296acf97d9c7a7b25d8a6d268ab52c3))
-   Refactor Auth, remove expensive middleware ([1d96c9405](https://github.com/pixelfed/pixelfed/commit/1d96c9405434784c17ed327a7967843fdf176f20))
-   Update ResetPasswordController ([194c881cb](https://github.com/pixelfed/pixelfed/commit/194c881cb2310f25bf9e43d35ed8117cc2d886dc))
-   Update AccountTransformer.php ([edcf97875](https://github.com/pixelfed/pixelfed/commit/edcf978755dd6a7275c7e3f54bca71fd69b6c271))
-   Fix account storage limit not freeing on media deletion ([#7169](https://github.com/pixelfed/pixelfed/pull/7169))
    -   users.storage_used only ever grew: uploads incremented it but no deletion
    -   path decremented it, so users hit the account size limit even when their
    -   real media usage was well below it.
    -   Decrement storage_used in MediaDeletePipeline when media is removed
    -   Add UserStorageService::increaseStorageUsed / decrementStorageUsed as the
    -   fast, symmetric hot-path counter updates (floor-based, clamped at zero)
    -   Refactor the 6 upload call sites to use increaseStorageUsed instead of
    -   duplicated inline writes (also fixes ceil/floor drift vs the reconciler)
    -   Add (user_id, size) covering index so per-user SUM(size) is not a full
    -   table scan (INPLACE/LOCK=NONE, skipped on sqlite)
    -   Add user:storage:recalculate command to repair affected accounts, with a
    -   daily --stale=168 scheduled reconciler to correct any drift
    -   Add regression tests for the pipeline and UserStorageService
-   Self-heal stale storage_used on upload/delete hot path ([26d3e8bb8](https://github.com/pixelfed/pixelfed/commit/26d3e8bb8efa69788406bd1b1e990361e0e2e76e))
    -   Make increaseStorageUsed/decrementStorageUsed recalculate from source when
    -   the cached counter is older than STALE_AFTER_HOURS (168h) or never
    -   calculated, so an affected user is corrected the next time they upload or
    -   delete without waiting for the nightly reconciler. Callers save/delete the
    -   media row before calling these, so the from-source recalc already reflects
    -   the change and the incremental delta is skipped on the recalc path.
    -   Add UserStorageService::STALE_AFTER_HOURS and isStale() helper (no extra
    -   query: reads the already-loaded model), with defensive Carbon parsing
    -   Cast users.storage_used_updated_at to datetime so freshness comparisons
    -   work on a Carbon instance
    -   Add tests for stale/fresh/never-calculated increase and decrement paths
-   Remove unused CACHE_KEY constant from UserStorageService ([f467dc04d](https://github.com/pixelfed/pixelfed/commit/f467dc04d55ad060883388115d746bd4d52eb9b6))
    -   The constant was never referenced; the service reads and writes the
    -   storage_used column directly on the User model rather than via cache.
-   Fix larastan noAuthFacadeInRequestScope in LoginController ([10559c23e](https://github.com/pixelfed/pixelfed/commit/10559c23e3f2f1f980fb501d7a54043c6209c2a0))
    -   Replace Auth::check() with $request->user() !== null in confirmEmail(),
    -   which already has the request in scope, and drop the now-unused Auth
    -   facade import. Resolves the 2 remaining project-wide larastan errors.
-   Self-heal stale storage_used on read to unblock stuck accounts ([61a1c3075](https://github.com/pixelfed/pixelfed/commit/61a1c30756f89dd8e0b3bcc78129525aa32c0a17))
    -   UserStorageService::get() now recalculates from source when the cached
    -   counter is missing or older than STALE_AFTER_HOURS, instead of returning a
    -   possibly-inflated cached value. This is what unblocks a user stuck at the
    -   account size limit: the limit check on their next upload attempt reads the
    -   freshly recalculated real usage rather than the drifted value (#7169).
    -   The upload flow reads get() and enforces the limit BEFORE the write-path
    -   heal runs, so a blocked user could never self-heal via upload/delete alone.
    -   Healing on read closes that gap and makes the scheduled reconciler a
    -   belt-and-suspenders safety net rather than a requirement.
    -   A fresh counter is still trusted as-is (no per-read SUM). Adds tests for the
    -   stale-get recompute and fresh-get trust paths.
-   Run storage recalculate reconciler weekly instead of daily ([28573e863](https://github.com/pixelfed/pixelfed/commit/28573e863f36ec10466a7708e977679bc46951d3))
    -   Now that the upload/delete hot path and get() self-heal stale counters, the
    -   scheduled reconciler is a background drift safety net rather than the primary
    -   unblock mechanism, so weekly is sufficient.
-   Backfill storage_used on upgrade via queued job + data migration ([5a9c23592](https://github.com/pixelfed/pixelfed/commit/5a9c235922e1dfb094c14e56e34fb78295c42dfe))
    -   Repair accounts whose storage counter drifted before the self-heal logic
    -   existed (#7169). A data migration dispatches RecalculateAllUserStoragePipeline
    -   to the low queue so the deploy is not blocked while every user is recomputed
    -   from source. The job is unique and idempotent, so re-runs are harmless.
    -   RecalculateAllUserStoragePipeline: chunked recalc of all active users
    -   Migration dispatches the job (no inline heavy work during deploy)
    -   Test covers bulk recalculation from actual media
-   Expand test coverage for storage_used improvements ([007f97f98](https://github.com/pixelfed/pixelfed/commit/007f97f98731ed9cda1896627d203d0f472329c4))
    -   Suspended/missing user guards for get, increase, decrement, recalculate
    -   Staleness window boundary (fresh at N-1h, stale at N+1h)
    -   Sub-1000-byte rounding on increase/decrement (floor to KB)
    -   Command --stale filter (only recomputes stale/never-calculated users) and
    -   missing --user id failure
    -   Migration dispatches the backfill job to the low queue (Bus::fake)
-   Gate storage reconciler schedule behind a disabled-by-default flag ([4f284e089](https://github.com/pixelfed/pixelfed/commit/4f284e089388b9299da8d30e5da3f62b59fd5cf2))
    -   The upload/delete/read paths now self-heal stale storage_used counters and
    -   the upgrade backfill migration repairs existing accounts, so the weekly
    -   reconciler is no longer required. Gate it behind pixelfed.account*storage*
    -   reconcile (ACCOUNT_STORAGE_RECONCILE), defaulting off, so operators can opt
    -   in to the background hygiene job without editing source.
-   Extract scheduled tasks into routes/scheduledtasks.php ([61c087e56](https://github.com/pixelfed/pixelfed/commit/61c087e56009e2b19489552f3992114282a3adf5))
    -   Move the schedule definitions out of the withSchedule() closure in
    -   bootstrap/app.php into a dedicated routes/scheduledtasks.php, required with
    -   the Schedule instance in scope. Behavior-preserving; verified with
    -   schedule:list.
-   Move account_storage_reconcile flag to config/scheduledtasks.php ([219ce0ca2](https://github.com/pixelfed/pixelfed/commit/219ce0ca2b006f24845bc8507769ae47f1034ccf))
    -   Introduce a dedicated config/scheduledtasks.php for scheduled-task toggles
    -   and relocate the reconciler flag there (ACCOUNT_STORAGE_RECONCILE), reading
    -   it via config() in routes/scheduledtasks.php. Removed the setting from
    -   config/pixelfed.php. Verified both states with schedule:list.
-   Move scheduled tasks file from routes/ to bootstrap/ ([cc183ec8a](https://github.com/pixelfed/pixelfed/commit/cc183ec8abb7fb5b885dadb10471f7923ed535dc))
    -   The schedule definitions are bootstrap wiring, not route definitions, so
    -   bootstrap/scheduledtasks.php is a better home. Updated the require path in
    -   bootstrap/app.php. Verified with schedule:list.
-   polish ([9b6eea066](https://github.com/pixelfed/pixelfed/commit/9b6eea066401dee863479fb0c5cd2de2152ad206))
-   polish ([1a5274954](https://github.com/pixelfed/pixelfed/commit/1a5274954d3dc6544cbef10bcc78c913b35a8e3d))
-   Rewrite 2FA tests for the pending-login refactor ([08a442e66](https://github.com/pixelfed/pixelfed/commit/08a442e6617e05b26c890d9b47400f2e2767e984))
    -   The 2FA flow moved from a middleware-gated i/auth/checkpoint model to a
    -   pending-login model (auth.pending session, POST /login/2fa, /login?step=2fa
    -   challenge). The old tests referenced the removed route and dead session keys
    -   (2fa.session.active, 2fa.attempts) and failed with 404s.
    -   Rewritten against the new code as source of truth:
    -   Checkpoint test: throttle assertion retargeted to the login/2fa route;
    -   failed-verification audit log now driven through a pending 2FA session.
    -   Logout-session test: asserts auth.pending is cleared and the user stays a
    -   guest after MAX_2FA_ATTEMPTS failures (replacing the old flag cleanup).
    -   TwoFactorTest: challenge-redirect and challenge-page cases rewritten around
    -   the login flow; setup/recovery password-confirmation cases unchanged.
    -   MiddlewarePipelineTest: 2FA is enforced at login, not per-request, so an
    -   authenticated 2FA user browses normally.
    -   Full suite: 715 passed.
-   Fix index migrations to support PostgreSQL and MariaDB ([cd4d9e5f3](https://github.com/pixelfed/pixelfed/commit/cd4d9e5f3615d64ccf93110ebf83965e0b51c381))
    -   The three recent index migrations used raw MySQL-only DDL (backtick
    -   identifiers, ADD INDEX inside ALTER TABLE, ALGORITHM=INPLACE/LOCK=NONE)
    -   guarded only against sqlite, so PostgreSQL instances failed with
    -   SQLSTATE\[42601\] on migrate (#7177).
    -   Each migration now branches on the driver:
    -   mysql/mariadb keep the online-DDL fast path (non-blocking on large
    -   instances)
    -   other drivers use the portable Schema::table builder
    -   Table names, index names, and columns are unchanged so already-migrated
    -   MySQL instances are unaffected.
-   Update ApiV1Controller, fix account suggestions ([6a3371687](https://github.com/pixelfed/pixelfed/commit/6a33716870269f1d80d2acf11b73dd3fb0a13498))
-   Update .dockerignore ([d1ba86475](https://github.com/pixelfed/pixelfed/commit/d1ba86475d9390f442a627b3a4ffb03aade8f5f0))
-   Update ApiV1Controller.php ([9aafa3736](https://github.com/pixelfed/pixelfed/commit/9aafa373649bdd59434d2de3fdb689320b924333))
-   Update account suggestions ([915879ff5](https://github.com/pixelfed/pixelfed/commit/915879ff579aa2765508033db0dede8eb6eb5594))
-   Fix StoryCarousel cache invalidation ([7975ba9c7](https://github.com/pixelfed/pixelfed/commit/7975ba9c758fb87f1aaab22330a571c8f7976b46))
-   Update StoryService and add has_story to AccountTransformer ([bbd7618c4](https://github.com/pixelfed/pixelfed/commit/bbd7618c46da7f274dd9442da57a8f598e52a129))
-   Fix StoryExpireRemoteCacheTest ([7a5192481](https://github.com/pixelfed/pixelfed/commit/7a5192481c271182edaaa773af273121e51f3c68))
-   Update docker-push.yml ([9357c1117](https://github.com/pixelfed/pixelfed/commit/9357c1117f2f55fa398af75c787027fdf3564e5e))
-   Update docker-tag.yml ([503c86e14](https://github.com/pixelfed/pixelfed/commit/503c86e147e85cd6a948f5e4d716e3c0b12a567f))
-   Update Ubuntu version in Docker workflow ([fdcdc6229](https://github.com/pixelfed/pixelfed/commit/fdcdc62291cf1e5ec6640bbfd7b77ec0ef306870))
-   Update docker-push.yml ([6fcc51872](https://github.com/pixelfed/pixelfed/commit/6fcc518720001690efcbcac60e38953bb2dc07db))
-   Update StoryExpireRemoteCacheTest.php ([84df3de56](https://github.com/pixelfed/pixelfed/commit/84df3de5622000c332f94a7f6c89e4cab56c1a03))
-   Bump version ([fb3e218d6](https://github.com/pixelfed/pixelfed/commit/fb3e218d64a3e48d916d6cf6fffbaa5de64d85a6))

## [v0.12.9 (2026-08-25)](https://github.com/pixelfed/pixelfed/compare/v0.12.9...dev)

-   Account Migration fix ([2fd3162f404](https://github.com/pixelfed/pixelfed/commit/2fd3162f4041bf82a51018ffbe3e1509b6576e33))
-   Fix post likes modal ([d1b11e2a8](https://github.com/pixelfed/pixelfed/commit/d1b11e2a8f61f87a36bd1d3bee43f9c7507ab19a))
-   Refactor UserFilterService ([ecb04f3ab3](https://github.com/pixelfed/pixelfed/commit/ecb04f3ab3bd64d694747f929cfb9beeb9a76021))
-   Add alt tag to avatars ([5e79dbd3321](https://github.com/pixelfed/pixelfed/commit/5e79dbd33216f702f798d3c76a49188e69b5fa47))

## [v0.12.8 (2026-08-24)](https://github.com/pixelfed/pixelfed/compare/v0.12.8...dev)

-   Several security fixes
-   Update mail.php to allow SMTP to bypass STARTTLS on email server with broken TLS ([b5ce7a8](https://github.com/pixelfed/pixelfed/commit/b5ce7a8))
-   Update passport. Fixes #6480 ([a78a02228](https://github.com/pixelfed/pixelfed/commit/a78a02228))

## [v0.12.7 (2026-02-17)](https://github.com/pixelfed/pixelfed/compare/v0.12.7...dev)

-   Update Status storage, add SanitizerService to fix spacing in html stripped content ([3686c9212](https://github.com/pixelfed/pixelfed/commit/3686c9212))
-   Update app config, add description and rule env variables ([0980519a9](https://github.com/pixelfed/pixelfed/commit/0980519a9))
-   Update InstanceService, fix total post count when config_cache is disabled ([f0bc9d66e](https://github.com/pixelfed/pixelfed/commit/f0bc9d66e))
-   Update media storage pipeline, improve support for non-local filesystems ([2e719bd00](https://github.com/pixelfed/pixelfed/commit/2e719bd00))
-   Update partial status updates nullify omitted caption/CW causing data loss) ([416c02e](https://github.com/pixelfed/pixelfed/commit/416c02e))
-   Update compose, improve validation ([f1af72e](https://github.com/pixelfed/pixelfed/commit/f1af72e))
-   Update ImportMediaToCloudPipeline to handle multiple files being uploaded ([bff9aae](https://github.com/pixelfed/pixelfed/commit/bff9aae))
-   InboxWorker and InboxValidator moved lock after the signature validation ([4b923ed](https://github.com/pixelfed/pixelfed/commit/4b923ed))
-   Update sendmail security ([5bc768b](https://github.com/pixelfed/pixelfed/commit/5bc768b))

## [v0.12.6 (2025-09-03)](https://github.com/pixelfed/pixelfed/compare/v0.12.6...dev)

### Added

-   Pinned Posts ([2f655d000](https://github.com/pixelfed/pixelfed/commit/2f655d000))
-   Custom Filters ([#5928](https://github.com/pixelfed/pixelfed/pull/5928)) ([437d742ac](https://github.com/pixelfed/pixelfed/commit/437d742ac))
-   Legal Notice page ([#5606](https://github.com/pixelfed/pixelfed/pull/5606)) ([c72fa0529](https://github.com/pixelfed/pixelfed/commit/c72fa0529))
-   OIDC Support ([#5608](https://github.com/pixelfed/pixelfed/pull/5608)) ([c72fa0529](https://github.com/pixelfed/pixelfed/commit/c72fa0529))
-   Avif, HEIC, webp, libvips support + Preserve ICC color profiles ([ab9c13fe0](https://github.com/pixelfed/pixelfed/commit/ab9c13fe0))
-   Added StoryIndexService, an optimized fan-out-on-write service for story carousel generation/rendering ([950fc0474](https://github.com/pixelfed/pixelfed/commit/950fc0474))

### Updates

-   Update PublicApiController, use pixelfed entities for /api/pixelfed/v1/accounts/id/statuses with bookmarked state ([5ddb6d842](https://github.com/pixelfed/pixelfed/commit/5ddb6d842))
-   Update Profile.vue, fix pagination ([2ea107805](https://github.com/pixelfed/pixelfed/commit/2ea107805))
-   Update ProfileMigrationController, fix race condition by chaining batched jobs ([3001365025](https://github.com/pixelfed/pixelfed/commit/3001365025))
-   Update Instance total post, add optional estimation for huge status tables ([5a5821fe8](https://github.com/pixelfed/pixelfed/commit/5a5821fe8))
-   Update ApiV1Controller, fix notifications favourited/reblogged/bookmarked state. Fixes #5901 ([8a86808a0](https://github.com/pixelfed/pixelfed/commit/8a86808a0))
-   Update ApiV1Controller, fix relationship fields. Fixes #5900 ([245ab3bc4](https://github.com/pixelfed/pixelfed/commit/245ab3bc4))
-   Update instance config, return proper matrix limits. Fixes #4780 ([473201908](https://github.com/pixelfed/pixelfed/commit/473201908))
-   Update SearchApiV2Service, fix offset bug. Fixes #5875 ([0a98b7ad2](https://github.com/pixelfed/pixelfed/commit/0a98b7ad2))
-   Update ApiV1Controller, add better direct error message. Fixes #4789 ([658fe6898](https://github.com/pixelfed/pixelfed/commit/658fe6898))
-   Update DiscoverController, improve public hashtag feed. Fixes #5866 ([32fc3180c](https://github.com/pixelfed/pixelfed/commit/32fc3180c))
-   Update report views, fix missing forms ([475d1d627](https://github.com/pixelfed/pixelfed/commit/475d1d627))
-   Update private settings, change "Private Account" to "Manually Review Follow Requests" ([31dd1ab35](https://github.com/pixelfed/pixelfed/commit/31dd1ab35))
-   Update ReportController, fix type validation ([ccc7f2fc6](https://github.com/pixelfed/pixelfed/commit/ccc7f2fc6))
-   Update footer to use legalNotice i18n ([0e59098da](https://github.com/pixelfed/pixelfed/commit/0e59098da))
-   Update sidebar with gap padding for footer links ([dbd8289fe](https://github.com/pixelfed/pixelfed/commit/dbd8289fe))
-   Update translations for Stories ([0a4dc7724](https://github.com/pixelfed/pixelfed/commit/0a4dc7724))
-   Update translations for Auth ([756102696](https://github.com/pixelfed/pixelfed/commit/756102696))
-   Update HttpSignatures, auto generate instance actor if missing ([bb16c95b1](https://github.com/pixelfed/pixelfed/commit/bb16c95b1))
-   Update CreateNote to use cached MediaService attachments ([6a7307104](https://github.com/pixelfed/pixelfed/commit/6a7307104))
-   Update ComposeController, fix cache invalidation order ([ae47ba73d](https://github.com/pixelfed/pixelfed/commit/ae47ba73d))
-   Update ApiV1Controller, fix cache invalidation order ([4747266b0](https://github.com/pixelfed/pixelfed/commit/4747266b0))
-   Update CreateNote, improve media attachement handling by leveraging the MediaService cache ([7ae61a74a](https://github.com/pixelfed/pixelfed/commit/7ae61a74a))
-   Update ActivityPub attachements, use Document type by default ([51ce7e1f0](https://github.com/pixelfed/pixelfed/commit/51ce7e1f0))
-   Update MediaService, improve activitypub format ([837014e06](https://github.com/pixelfed/pixelfed/commit/837014e06))
-   Update StatusController, fix mimeTypeCheck ([7f7387ee4](https://github.com/pixelfed/pixelfed/commit/7f7387ee4))
-   Update MediaTransformer, return proper image type ([0dff48adb](https://github.com/pixelfed/pixelfed/commit/0dff48adb))
-   Update StoryComposeController, fix intervention/image v3 support ([86fbeeec3](https://github.com/pixelfed/pixelfed/commit/86fbeeec3))
-   Update StoryController, fix intervention/image v3 support ([9d89425e6](https://github.com/pixelfed/pixelfed/commit/9d89425e6))
-   Update Groups ImageResizePipeline with intervention/image v3 support ([616e37066](https://github.com/pixelfed/pixelfed/commit/616e37066))
-   Update app config, add Str alias ([5539dd0e1](https://github.com/pixelfed/pixelfed/commit/5539dd0e1))
-   Update PlaceController, fix show method ([f81a4acdc](https://github.com/pixelfed/pixelfed/commit/f81a4acdc))
-   Update Places, improve cache invalidation/ttl ([ece23d751](https://github.com/pixelfed/pixelfed/commit/ece23d751))
-   Update ComposeController, add addl compose settings data ([9048ab52c](https://github.com/pixelfed/pixelfed/commit/9048ab52c))
-   Update Admin Users dashboard ([b6bc1e50e](https://github.com/pixelfed/pixelfed/commit/b6bc1e50e))
-   Update TransformImports command, fix IG import bug ([c692c7655](https://github.com/pixelfed/pixelfed/commit/c692c7655))
-   Update ImportService and TransformImports to fix race condition bug ([a8d1d0f2e](https://github.com/pixelfed/pixelfed/commit/a8d1d0f2e))
-   Update ComposeController, prioritize followed users and follower_count first ([10eb1a8ac](https://github.com/pixelfed/pixelfed/commit/10eb1a8ac))
-   Update ComposeController, fix user tagging endpoint ([2a9c28b81](https://github.com/pixelfed/pixelfed/commit/2a9c28b81))
-   Update RemoteStatusDelete, fix decrement logic ([4ab85248e](https://github.com/pixelfed/pixelfed/commit/4ab85248e))
-   Update DangerZone middleware to skip sudo mode for OIDC configurations. Fixes #6057 ([062ec5520](https://github.com/pixelfed/pixelfed/commit/062ec5520))
-   Update curated onboarding username max length ([ab378b8fc](https://github.com/pixelfed/pixelfed/commit/ab378b8fc))
-   Update AppRegister controller, add scheduled cleanup task to delete older than 90d ([c319dfbcc](https://github.com/pixelfed/pixelfed/commit/c319dfbcc))
-   Update MediaCloudUrlRewrite command, add avatar support. Fixes #6069 ([506fe14c1](https://github.com/pixelfed/pixelfed/commit/506fe14c1))
-   Update Notifications component, fix pagination and dark mode ([154dd4b4d](https://github.com/pixelfed/pixelfed/commit/154dd4b4d))
-   Update DirectMessageController, add mutuals endpoint ([86af73455](https://github.com/pixelfed/pixelfed/commit/86af73455))
-   Update HomeSettings, remove unnecessary relation query ([35424ccb4](https://github.com/pixelfed/pixelfed/commit/35424ccb4))
-   Update ApiV1Dot1Controller, add story report support ([f5dced0f7](https://github.com/pixelfed/pixelfed/commit/f5dced0f7))
-   Update StoryView resource, include viewed_at timestamp ([d361b0dca](https://github.com/pixelfed/pixelfed/commit/d361b0dca))
-   Update AP Inbox, handle Story View with the new StoryIndexService markSeen method ([ab8d0ff46](https://github.com/pixelfed/pixelfed/commit/ab8d0ff46))
-   Update StoryFetch pipeline job, make more robust and add StoryIndexService indexStory support ([fd3df358b](https://github.com/pixelfed/pixelfed/commit/fd3df358b))
-   Update StoryExpire pipeline job, add StoryIndexService removeStory support ([5a263e89e](https://github.com/pixelfed/pixelfed/commit/5a263e89e))
-   Update StoryController, add StoryIndexService s markSeen support for webUI endpoint ([44914a514](https://github.com/pixelfed/pixelfed/commit/44914a514))
-   Update StoryApiV1Controller, add new v1.2 endpoints ([97badbbdd](https://github.com/pixelfed/pixelfed/commit/97badbbdd))
-   Update StoryIndexService, improve markSeen handling ([3296a7a58](https://github.com/pixelfed/pixelfed/commit/3296a7a58))
-   Update StoryIndexService, fix markSeen method ([e09291775](https://github.com/pixelfed/pixelfed/commit/e09291775))
-   Update StoryComposeController, add StoryIndexService support ([6c701b335](https://github.com/pixelfed/pixelfed/commit/6c701b335))
-   Update StoryIndexService, improve predis + phpredis support ([564d8d109](https://github.com/pixelfed/pixelfed/commit/564d8d109))
-   Update StoryApiV1Controller, add missing validation rule ([76d9ded69](https://github.com/pixelfed/pixelfed/commit/76d9ded69))
-   Update StoryIndexService, improve predis/phpredis support ([53b74bf16](https://github.com/pixelfed/pixelfed/commit/53b74bf16))
-   Update StoryApiV1Controller, improve text overlay validation regex for improved support ([8fb44e316](https://github.com/pixelfed/pixelfed/commit/8fb44e316))
-   Update StoryIndexService, improve redis compatability ([dbba52303](https://github.com/pixelfed/pixelfed/commit/dbba52303))
-   Update StoryFetch, fix mass assignment bug preventing proper model creation ([1e3147028](https://github.com/pixelfed/pixelfed/commit/1e3147028))
-   Update StoryRotateMedia job, handle StoryIndexService cache invalidation ([e2a64c730](https://github.com/pixelfed/pixelfed/commit/e2a64c730))
-   Update ApiV1StoryController, fix viewer pagination ([5d4674daa](https://github.com/pixelfed/pixelfed/commit/5d4674daa))
-   Update StoryApiV1Controller, reduce min story size to 10kb ([f195102b3](https://github.com/pixelfed/pixelfed/commit/f195102b3))
-   Update ApiV1Dot1Controller, fix Story report follower check ([ec21eec50](https://github.com/pixelfed/pixelfed/commit/ec21eec50))

## [v0.12.5 (2025-03-23)](https://github.com/pixelfed/pixelfed/compare/v0.12.5...dev)

### Added

-   Add app register email verify resends ([dbd1e17](https://github.com/pixelfed/pixelfed/commit/dbd1e17))
-   Add AVIF support ([7ddbe0c47](https://github.com/pixelfed/pixelfed/commit/7ddbe0c47))

### Features

-   WebGL photo filters ([#5374](https://github.com/pixelfed/pixelfed/pull/5374))

### OAuth

-   Fix oauth oob (urn:ietf:wg:oauth:2.0:oob) support. ([8afbdb03](https://github.com/pixelfed/pixelfed/commit/8afbdb03))

### Updates

-   Update AP helpers, reject statuses with invalid dates ([960f3849](https://github.com/pixelfed/pixelfed/commit/960f3849))
-   Update DirectMessage API, fix broken threading ([044d410c](https://github.com/pixelfed/pixelfed/commit/044d410c))
-   Update Status caption render logic ([fb8dbb95](https://github.com/pixelfed/pixelfed/commit/fb8dbb95))
-   Update ApiV1Controller, fix bookmark bug. Closes #5216 ([9f7cc52c](https://github.com/pixelfed/pixelfed/commit/9f7cc52c))
-   Update Status caption logic, stop storing duplicate html caption in db and defer to cached StatusService rendering ([9eeb7b67](https://github.com/pixelfed/pixelfed/commit/9eeb7b67))
-   Update AutolinkService, optimize lookups ([eac2c196](https://github.com/pixelfed/pixelfed/commit/eac2c196))
-   Update DirectMessageController, remove 72h limit for admins ([639df410](https://github.com/pixelfed/pixelfed/commit/639df410))
-   Update StatusService, fix newlines ([56c07b7a](https://github.com/pixelfed/pixelfed/commit/56c07b7a))
-   Update confirm email template, add plaintext link. Fixes #5375 ([45986707](https://github.com/pixelfed/pixelfed/commit/45986707))
-   Update UserVerifyEmail command ([77da9ad8](https://github.com/pixelfed/pixelfed/commit/77da9ad8))
-   Update StatusStatelessTransformer, refactor the caption field to be compliant with the MastoAPI. Fixes #5364 ([79039ba5](https://github.com/pixelfed/pixelfed/commit/79039ba5))
-   Update mailgun config, add endpoint and scheme ([271d5114](https://github.com/pixelfed/pixelfed/commit/271d5114))
-   Update search and status logic to fix postgres bugs ([8c39ef4](https://github.com/pixelfed/pixelfed/commit/8c39ef4))
-   Update db, fix sqlite migrations ([#5379](https://github.com/pixelfed/pixelfed/pull/5379))
-   Update CatchUnoptimizedMedia command, make 1hr limit opt-in ([99b15b73](https://github.com/pixelfed/pixelfed/commit/99b15b73))
-   Update IG, fix Instagram import. Closes #5411 ([fd434aec](https://github.com/pixelfed/pixelfed/commit/fd434aec))
-   Update StatusTagsPipeline, fix hashtag bug and formatting ([d516b799](https://github.com/pixelfed/pixelfed/commit/d516b799))
-   Update CollectionController, fix showCollection signature ([4e1dd599](https://github.com/pixelfed/pixelfed/commit/4e1dd599))
-   Update ApiV1Dot1Controller, fix in-app registration ([56f17b99](https://github.com/pixelfed/pixelfed/commit/56f17b99))
-   Update VerifyCsrfToken middleware, add oauth token. Fixes #5426 ([79ebbc2d](https://github.com/pixelfed/pixelfed/commit/79ebbc2d))
-   Update AdminSettingsController, increase max photo size limit from 50MB to 1GB ([aa448354](https://github.com/pixelfed/pixelfed/commit/aa448354))
-   Update BearerTokenResponse, return scopes in /oauth/token endpoint. Fixes #5286 ([d8f5c302](https://github.com/pixelfed/pixelfed/commit/d8f5c302))
-   Update hashtag component, fix missing video thumbnails ([witten](https://github.com/witten)) ([#5427](https://github.com/pixelfed/pixelfed/pull/5427))
-   Update AP Status Transformer, fix inReplyTo. Fixes #5409 ([83cc932f](https://github.com/pixelfed/pixelfed/commit/83cc932f))
-   Update Data Export, refactor following/follower and statuses exports to allow accounts of any size with api entity instead of ap ([0d25917c](https://github.com/pixelfed/pixelfed/commit/0d25917c))
-   Update oauth/token, fix scope to be space separated string instead of array ([4ce6e610](https://github.com/pixelfed/pixelfed/commit/4ce6e610))
-   Update SearchApiV2Service, fix hashtag search ([83c1a7fd](https://github.com/pixelfed/pixelfed/commit/83c1a7fd))
-   Update AP Helpers, fix comment bug ([22eae69f](https://github.com/pixelfed/pixelfed/commit/22eae69f))
-   Update ComposeController, add max_media_attachments attribute ([17918cbe](https://github.com/pixelfed/pixelfed/commit/17918cbe))
-   Fix GroupController, move groups enabled check to each method to fix route:list ([f260572e](https://github.com/pixelfed/pixelfed/commit/f260572e))
-   Update MediaStorageService, handle local media deletes after successful S3 upload ([280f63dc](https://github.com/pixelfed/pixelfed/commit/280f63dc))
-   Update status twitter:card to summary_large_image for images/albums ([9a5a9f55](https://github.com/pixelfed/pixelfed/commit/9a5a9f55))
-   Update CuratedOnboarding, add new app:curated-onboarding command, extend email verification window to 7 days and fix resend verification mails ([49604210](https://github.com/pixelfed/pixelfed/commit/49604210))
-   Update DirectMessageController, fix performance issue ([4ec9f99](https://github.com/pixelfed/pixelfed/commit/4ec9f99))
-   Update App Register to expire codes after 4 hours instead of 60 minutes ([0844094b](https://github.com/pixelfed/pixelfed/commit/0844094b))
-   Update ApiV1Controller, fix max_id pagination on home and public timeline feeds ([38e17a06e](https://github.com/pixelfed/pixelfed/commit/38e17a06e))
-   Update Post component, rewrite local post urls ([d2f2a1b1c](https://github.com/pixelfed/pixelfed/commit/d2f2a1b1c))
-   Update Profile component, rewrite local profile urls ([dfbccaa19](https://github.com/pixelfed/pixelfed/commit/dfbccaa19))
-   Update AccountPostCountStatUpdate, fix memory leak ([134eb6324](https://github.com/pixelfed/pixelfed/commit/134eb6324))
-   Update snowflake config, allow custom datacenter/worker ids ([806e210f1](https://github.com/pixelfed/pixelfed/commit/806e210f1))
-   Update ApiV1Controller, return empty statuses feed for private accounts instead of 403 response ([cce657d9c](https://github.com/pixelfed/pixelfed/commit/cce657d9c))
-   Update DM config, allow new users to send DMs by default, with a new env variable to enforce a 72h limit ([717f17cde](https://github.com/pixelfed/pixelfed/commit/717f17cde))
-   Update ApiV1Controller, add pagination to conversations endpoint with min/max/since id pagination and link header support ([244e86bad](https://github.com/pixelfed/pixelfed/commit/244e86bad))
-   Update Direct message component, fix pagination ([e6ef64857](https://github.com/pixelfed/pixelfed/commit/e6ef64857))
-   Update ActivityPub helpers, improve private account handling ([75e7a678c](https://github.com/pixelfed/pixelfed/commit/75e7a678c))
-   Update ApiV1Controller, improve follower handling ([976a1873e](https://github.com/pixelfed/pixelfed/commit/976a1873e))
-   Update Inbox, improve Accept Follower handling ([3725c689e](https://github.com/pixelfed/pixelfed/commit/3725c689e))
-   Update Inbox handler, add Reject Follow support ([fbe76e37f](https://github.com/pixelfed/pixelfed/commit/fbe76e37f))
-   Update Inbox handler, improve Undo Follow logic ([5525369fe](https://github.com/pixelfed/pixelfed/commit/5525369fe))
-   Update ApiV1Controller, send UndoFollow when cancelling a follow request on remote accounts ([2cf301181](https://github.com/pixelfed/pixelfed/commit/2cf301181))

## [v0.12.4 (2024-11-08)](https://github.com/pixelfed/pixelfed/compare/v0.12.4...dev)

### Added

-   Implement Admin Domain Blocks API (Mastodon API Compatible) [ThisIsMissEm](https://github.com/ThisIsMissEm) ([#5021](https://github.com/pixelfed/pixelfed/pull/5021))
-   Authorize Interaction support (for handling remote interactions) ([4ca7c6c3](https://github.com/pixelfed/pixelfed/commit/4ca7c6c3))
-   Contact Form Admin Responses ([52cc6090](https://github.com/pixelfed/pixelfed/commit/52cc6090))
-   Profile Carousels ([8af77a3f](https://github.com/pixelfed/pixelfed/commit/8af77a3f))
-   Moderated Profiles ([39f16321](https://github.com/pixelfed/pixelfed/commit/39f16321))

### Federation

-   Add ActiveSharedInboxService, for efficient sharedInbox caching ([1a6a3397](https://github.com/pixelfed/pixelfed/commit/1a6a3397))
-   Add MovePipeline queue jobs ([9904d05f](https://github.com/pixelfed/pixelfed/commit/9904d05f))
-   Add ActivityPub Move validator ([909a6c72](https://github.com/pixelfed/pixelfed/commit/909a6c72))
-   Add delay to move handler to allow for remote cache invalidation ([8a362c12](https://github.com/pixelfed/pixelfed/commit/8a362c12))

### Updates

-   Update ApiV1Controller, add support for notification filter types ([f61159a1](https://github.com/pixelfed/pixelfed/commit/f61159a1))
-   Update ApiV1Dot1Controller, fix mutual api ([a8bb97b2](https://github.com/pixelfed/pixelfed/commit/a8bb97b2))
-   Update ApiV1Controller, fix /api/v1/favourits pagination ([72f68160](https://github.com/pixelfed/pixelfed/commit/72f68160))
-   Update RegisterController, update username constraints, require atleast one alpha char ([dd6e3cc2](https://github.com/pixelfed/pixelfed/commit/dd6e3cc2))
-   Update AdminUser, fix entity casting ([cb5620d4](https://github.com/pixelfed/pixelfed/commit/cb5620d4))
-   Update instance config, update network cache feed max_hours_old falloff to 90 days instead of 6 hours to allow for less active instances to have more results ([c042d135](https://github.com/pixelfed/pixelfed/commit/c042d135))
-   Update ApiV1Dot1Controller, add new single media status create endpoint ([b03f5cec](https://github.com/pixelfed/pixelfed/commit/b03f5cec))
-   Update AdminSettings component, add link to Custom CSS settings ([958daac4](https://github.com/pixelfed/pixelfed/commit/958daac4))
-   Update ApiV1Controller, fix v1/instance stats, force cast to int ([dcd95d68](https://github.com/pixelfed/pixelfed/commit/dcd95d68))
-   Update BeagleService, disable discovery if AP is disabled ([6cd1cbb4](https://github.com/pixelfed/pixelfed/commit/6cd1cbb4))
-   Update NodeinfoService, fix typo ([edad436d](https://github.com/pixelfed/pixelfed/commit/edad436d))
-   Update ActivityPubFetchService, reduce cache ttl from 1 hour to 7.5 mins and add uncached fetchRequest method ([21da2b64](https://github.com/pixelfed/pixelfed/commit/21da2b64))
-   Update UserAccountDelete command, increase sharedInbox ttl from 12h to 14d ([be02f48a](https://github.com/pixelfed/pixelfed/commit/be02f48a))
-   Update HttpSignature, add signRaw method and improve error checking ([d4cf9181](https://github.com/pixelfed/pixelfed/commit/d4cf9181))
-   Update AP helpers, add forceBanCheck param to validateUrl method ([42424028](https://github.com/pixelfed/pixelfed/commit/42424028))
-   Update layout, add og:logo ([4cc576e1](https://github.com/pixelfed/pixelfed/commit/4cc576e1))
-   Update ReblogService, fix cache sync issues ([3de8ceca](https://github.com/pixelfed/pixelfed/commit/3de8ceca))
-   Update config, allow Beagle discover service to be disabled ([de4ce3c8](https://github.com/pixelfed/pixelfed/commit/de4ce3c8))
-   Update ApiV1Dot1Controller, allow upto 5 similar push tokens ([7820b506](https://github.com/pixelfed/pixelfed/commit/7820b506))
-   Update AdminReports, add missing click handler. Fixes #5332 ([fe48b8ad](https://github.com/pixelfed/pixelfed/commit/fe48b8ad))
-   Improve media filtering by using OffscreenCanvas, if supported ([aea5392](https://github.com/pixelfed/pixelfed/commit/aea5392))

## [v0.12.3 (2024-07-01)](https://github.com/pixelfed/pixelfed/compare/v0.12.2...v0.12.3)

### Updates

-   Fix migrations bug ([4d1180b1](https://github.com/pixelfed/pixelfed/commit/4d1180b1))

## [v0.12.2 (2024-07-01)](https://github.com/pixelfed/pixelfed/compare/v0.12.1...v0.12.2)

### Framework

-   Updated to Laravel 11 (requires php 8.2+)

### Added

-   New api/v1/instance/peers API endpoint, disabled by default ([4aad1c22](https://github.com/pixelfed/pixelfed/commit/4aad1c22))
-   Added disable_embeds setting, and fix cache invalidation in other settings ([c5e7e917](https://github.com/pixelfed/pixelfed/commit/c5e7e917))

### Updates

-   Update DirectMessageController, add 72 hour delay for new accounts before they can send a DM ([61d105fd](https://github.com/pixelfed/pixelfed/commit/61d105fd))
-   Update AdminCuratedRegisterController, increase message length from 1000 to 3000 ([9a5e3471](https://github.com/pixelfed/pixelfed/commit/9a5e3471))
-   Update ApiV1Controller, add pe (pixelfed entity) support to /api/v1/statuses/{id}/context endpoint ([d645d6ca](https://github.com/pixelfed/pixelfed/commit/d645d6ca))
-   Update Admin Curated Onboarding, add select-all/mass action operations ([b22cac94](https://github.com/pixelfed/pixelfed/commit/b22cac94))
-   Update AdminCuratedRegisterController, fix existing account approval ([cbb96cfd](https://github.com/pixelfed/pixelfed/commit/cbb96cfd))
-   Update ActivityPubFetchService, fix Friendica bug ([e4edc6f1](https://github.com/pixelfed/pixelfed/commit/e4edc6f1))
-   Update ProfileController, fix atom feed cache ttl. Fixes #5093 ([921e2965](https://github.com/pixelfed/pixelfed/commit/921e2965))
-   Update CollectionsController, add new self route ([bc2495c6](https://github.com/pixelfed/pixelfed/commit/bc2495c6))
-   Update FederationController, add webfinger support for actor uri. Fixes #5068 ([24194f7d](https://github.com/pixelfed/pixelfed/commit/24194f7d))
-   Update FetchNodeinfoPipeline, set last_fetched_at timestamp ([a7fce91e](https://github.com/pixelfed/pixelfed/commit/a7fce91e))
-   Update task scheduler, add weekly instance scan to check nodeinfo for known instances ([dc6b9f46](https://github.com/pixelfed/pixelfed/commit/dc6b9f46))
-   Update AP fetch service and domain service ([42915ff9](https://github.com/pixelfed/pixelfed/commit/42915ff9))
-   Update ApiV1Controller, add settings to verify_credentials endpoint ([3f4e0b94](https://github.com/pixelfed/pixelfed/commit/3f4e0b94))
-   Update ApiV1Controller, fix update_credentials boolean handling ([19c62aaa](https://github.com/pixelfed/pixelfed/commit/19c62aaa))
-   Update ApiV1Controller, fix cache invalidation bug in update_credentials ([d56a4108](https://github.com/pixelfed/pixelfed/commit/d56a4108))
-   Update ApiV1Controller, fix self relationship response ([28bc7aa4](https://github.com/pixelfed/pixelfed/commit/28bc7aa4))
-   Update ApiController, add pe support to like/unlike endpoints ([679ef677](https://github.com/pixelfed/pixelfed/commit/679ef677))
-   Update ApiV1Dot1Controller, fix username to id endpoint ([4d6cea9a](https://github.com/pixelfed/pixelfed/commit/4d6cea9a))
-   Update StatusController, cache AP object ([a75b89b2](https://github.com/pixelfed/pixelfed/commit/a75b89b2))
-   Update status embed, add support for album carousels ([f4898db9](https://github.com/pixelfed/pixelfed/commit/f4898db9))
-   Update profile embeds, add support for albums ([4fd156c4](https://github.com/pixelfed/pixelfed/commit/4fd156c4))
-   Update DirectMessageController, add timestamps to threads ([b24d2554](https://github.com/pixelfed/pixelfed/commit/b24d2554))
-   Update DirectMessageController, add carousel entity to threads ([96f24f33](https://github.com/pixelfed/pixelfed/commit/96f24f33))
-   Update and refactor total local post count logic, cache value and schedule updates twice daily to eliminate the perf issue on larger instances ([4f2b8ed2](https://github.com/pixelfed/pixelfed/commit/4f2b8ed2))
-   Update Media model, fix broken thumbnail/gray thumbnail bug ([e33643c2](https://github.com/pixelfed/pixelfed/commit/e33643c2))
-   Update StatusController, fix unlisted post guest/ap access bug ([83098428](https://github.com/pixelfed/pixelfed/commit/83098428))
-   Update discover, add network trending using Beagle API ([2cae8b48](https://github.com/pixelfed/pixelfed/commit/2cae8b48))

## [v0.12.1 (2024-05-07)](https://github.com/pixelfed/pixelfed/compare/v0.12.0...v0.12.1)

### Updates

-   Update ApiV1Dot1Controller, fix in app registration bug that prevents proper auth flow due to missing oauth scopes ([cbf996c9](https://github.com/pixelfed/pixelfed/commit/cbf996c9))
-   Update ConfigCacheService, fix database race condition and fallback to file config and enable by default ([60a62b59](https://github.com/pixelfed/pixelfed/commit/60a62b59))

## [v0.12.0 (2024-04-29)](https://github.com/pixelfed/pixelfed/compare/v0.11.13...v0.12.0)

### Updates

-   Update SoftwareUpdateService, add command to refresh latest versions ([632f2cb6](https://github.com/pixelfed/pixelfed/commit/632f2cb6))
-   Update Post.vue, fix cache bug ([3a27e637](https://github.com/pixelfed/pixelfed/commit/3a27e637))
-   Update StatusHashtagService, use more efficient cached count ([592c8412](https://github.com/pixelfed/pixelfed/commit/592c8412))
-   Update DiscoverController, handle discover hashtag redirects ([18382e8a](https://github.com/pixelfed/pixelfed/commit/18382e8a))
-   Update ApiV1Controller, use admin filter service ([94503a1c](https://github.com/pixelfed/pixelfed/commit/94503a1c))
-   Update SearchApiV2Service, use more efficient query ([cee618e8](https://github.com/pixelfed/pixelfed/commit/cee618e8))
-   Update Curated Onboarding view, fix concierge form ([15ad69f7](https://github.com/pixelfed/pixelfed/commit/15ad69f7))
-   Update AP Profile Transformer, add `suspended` attribute ([25f3fa06](https://github.com/pixelfed/pixelfed/commit/25f3fa06))
-   Update AP Profile Transformer, fix movedTo attribute ([63100fe9](https://github.com/pixelfed/pixelfed/commit/63100fe9))
-   Update AP Profile Transformer, fix suspended attributes ([2e5e68e4](https://github.com/pixelfed/pixelfed/commit/2e5e68e4))
-   Update PrivacySettings controller, add cache invalidation ([e742d595](https://github.com/pixelfed/pixelfed/commit/e742d595))
-   Update ProfileController, preserve deleted actor objects for federated account deletion and use more efficient account cache lookup ([853a729f](https://github.com/pixelfed/pixelfed/commit/853a729f))
-   Update SiteController, add curatedOnboarding method that gracefully falls back to open registration when applicable ([95199843](https://github.com/pixelfed/pixelfed/commit/95199843))
-   Update AP transformers, add DeleteActor activity ([bcce1df6](https://github.com/pixelfed/pixelfed/commit/bcce1df6))
-   Update commands, add user account delete cli command to federate account deletion ([4aa0e25f](https://github.com/pixelfed/pixelfed/commit/4aa0e25f))
-   Update web-api popular accounts route to its own method to remove the breaking oauth scope bug ([a4bc5ce3](https://github.com/pixelfed/pixelfed/commit/a4bc5ce3))
-   Update config cache ([5e4d4eff](https://github.com/pixelfed/pixelfed/commit/5e4d4eff))
-   Update Config, use config_cache ([7785a2da](https://github.com/pixelfed/pixelfed/commit/7785a2da))
-   Update ApiV1Dot1Controller, use config_cache for in-app registration ([b0cb4456](https://github.com/pixelfed/pixelfed/commit/b0cb4456))
-   Update captcha, use config_cache helper ([8a89e3c9](https://github.com/pixelfed/pixelfed/commit/8a89e3c9))
-   Update custom emoji, add config_cache support ([481314cd](https://github.com/pixelfed/pixelfed/commit/481314cd))
-   Update ProfileController, fix permalink redirect bug ([75081e60](https://github.com/pixelfed/pixelfed/commit/75081e60))
-   Update admin css, use font-display:swap for nucleo icons ([8a0c456e](https://github.com/pixelfed/pixelfed/commit/8a0c456e))
-   Update PixelfedDirectoryController, fix boolean cast bug ([f08aab22](https://github.com/pixelfed/pixelfed/commit/f08aab22))
-   Update PixelfedDirectoryController, use cached stats ([f2f2a809](https://github.com/pixelfed/pixelfed/commit/f2f2a809))
-   Update AdminDirectoryController, fix type casting ([ad506e90](https://github.com/pixelfed/pixelfed/commit/ad506e90))
-   Update image pipeline, use config_cache ([a72188a7](https://github.com/pixelfed/pixelfed/commit/a72188a7))
-   Update cloud storage, use config_cache ([665581d8](https://github.com/pixelfed/pixelfed/commit/665581d8))
-   Update pixelfed.max_album_length, use config_cache ([fecbe189](https://github.com/pixelfed/pixelfed/commit/fecbe189))
-   Update media_types, use config_cache ([d670de17](https://github.com/pixelfed/pixelfed/commit/d670de17))
-   Update landing settings, use config_cache ([40478f25](https://github.com/pixelfed/pixelfed/commit/40478f25))
-   Update activitypub setting, use config_cache ([5071aaf4](https://github.com/pixelfed/pixelfed/commit/5071aaf4))
-   Update oauth setting, use config_cache ([ce228f7f](https://github.com/pixelfed/pixelfed/commit/ce228f7f))
-   Update stories config, use config_cache ([d1adb109](https://github.com/pixelfed/pixelfed/commit/d1adb109))
-   Update ig import, use config_cache ([da0e0ffa](https://github.com/pixelfed/pixelfed/commit/da0e0ffa))
-   Update autospam config, use config_cache ([a76cb5f4](https://github.com/pixelfed/pixelfed/commit/a76cb5f4))
-   Update app.name config, use config_cache ([911446c0](https://github.com/pixelfed/pixelfed/commit/911446c0))
-   Update UserObserver, fix type casting ([949e9979](https://github.com/pixelfed/pixelfed/commit/949e9979))
-   Update user_filters, use config_cache ([6ce513f8](https://github.com/pixelfed/pixelfed/commit/6ce513f8))
-   Update filesystems config, add to config_cache ([087b2791](https://github.com/pixelfed/pixelfed/commit/087b2791))
-   Update web-admin routes, add setting api routes ([828a456f](https://github.com/pixelfed/pixelfed/commit/828a456f))
-   Update hashtag component ([cee979ed](https://github.com/pixelfed/pixelfed/commit/cee979ed))
-   Update AdminReadMore component, add .prevent to click action ([704e7b12](https://github.com/pixelfed/pixelfed/commit/704e7b12))
-   Update admin dashboard, add admin settings partials ([eb487123](https://github.com/pixelfed/pixelfed/commit/eb487123))
-   Update admin settings, refactor to vue component ([674e560f](https://github.com/pixelfed/pixelfed/commit/674e560f))
-   Update ConfigCacheService, encrypt keys at rest ([3628b462](https://github.com/pixelfed/pixelfed/commit/3628b462))
-   Update RemoteFollowImportRecent, use MediaPathService ([5162c070](https://github.com/pixelfed/pixelfed/commit/5162c070))
-   Update AdminSettingsController, add user filter max limit settings ([ac1f0748](https://github.com/pixelfed/pixelfed/commit/ac1f0748))
-   Update AdminSettingsController, add AdminSettingsService ([dcc5f416](https://github.com/pixelfed/pixelfed/commit/dcc5f416))
-   Update AdminSettings component, fix user settings ([aba1e13d](https://github.com/pixelfed/pixelfed/commit/aba1e13d))
-   Update AdminInstances component ([ec2fdd61](https://github.com/pixelfed/pixelfed/commit/ec2fdd61))
-   Update AdminSettings, add max_account_size support ([2dcbc1d5](https://github.com/pixelfed/pixelfed/commit/2dcbc1d5))
-   Update AdminSettings, use better validation for user integer settings ([d946afcc](https://github.com/pixelfed/pixelfed/commit/d946afcc))
-   Update spa sass, fix timestamp dark mode bug ([4147f7c5](https://github.com/pixelfed/pixelfed/commit/4147f7c5))
-   Update relationships view, fix unfollow hashtag bug. Fixes #5008 ([8c693640](https://github.com/pixelfed/pixelfed/commit/8c693640))
-   Update PrivacySettings controller, refresh RelationshipService when unmute/unblocking ([b7322b68](https://github.com/pixelfed/pixelfed/commit/b7322b68))
-   Update ApiV1Controller, improve refresh relations logic when (un)muting or (un)blocking ([b8e96a5f](https://github.com/pixelfed/pixelfed/commit/b8e96a5f))
-   Update context menu, add mute/block/unfollow actions and update relationship store accordingly ([81d1e0fd](https://github.com/pixelfed/pixelfed/commit/81d1e0fd))
-   Update docker env, fix config_cache. Fixes #5033 ([858fcbf6](https://github.com/pixelfed/pixelfed/commit/858fcbf6))
-   Update UnfollowPipeline, fix follower count cache bug ([6bdf73de](https://github.com/pixelfed/pixelfed/commit/6bdf73de))
-   Update VideoPresenter component, add webkit-playsinline attribute to video element to prevent the full screen video player ([ad032916](https://github.com/pixelfed/pixelfed/commit/ad032916))
-   Update VideoPlayer component, add playsinline attribute to video element ([8af23607](https://github.com/pixelfed/pixelfed/commit/8af23607))
-   Update StatusController, refactor status embeds ([9a7acc12](https://github.com/pixelfed/pixelfed/commit/9a7acc12))
-   Update ProfileController, refactor profile embeds ([8b8b1ffc](https://github.com/pixelfed/pixelfed/commit/8b8b1ffc))
-   Update profile embed view, fix height bug ([65166570](https://github.com/pixelfed/pixelfed/commit/65166570))
-   Update CustomEmojiService, only return local emoji ([7f8bba44](https://github.com/pixelfed/pixelfed/commit/7f8bba44))
-   Update Like model, increase max likes per day from 500 to 1500 ([4223119f](https://github.com/pixelfed/pixelfed/commit/4223119f))

## [v0.11.13 (2024-03-05)](https://github.com/pixelfed/pixelfed/compare/v0.11.12...v0.11.13)

### Features

-   Account Migrations ([#4968](https://github.com/pixelfed/pixelfed/pull/4968)) ([4a6be6212](https://github.com/pixelfed/pixelfed/pull/4968/commits/4a6be6212))
-   Curated Onboarding ([#4946](https://github.com/pixelfed/pixelfed/pull/4946)) ([8dac2caf](https://github.com/pixelfed/pixelfed/commit/8dac2caf))
-   Add Curated Onboarding Templates ([071163b4](https://github.com/pixelfed/pixelfed/commit/071163b4))
-   Add Remote Reports to Admin Dashboard Reports page ([ef0ff78e](https://github.com/pixelfed/pixelfed/commit/ef0ff78e))
-   Improved Docker Support ([#4844](https://github.com/pixelfed/pixelfed/pull/4844)) ([d92cf7f](https://github.com/pixelfed/pixelfed/commit/d92cf7f))

### Updates

-   Update Inbox, cast live filters to lowercase ([d835e0ad](https://github.com/pixelfed/pixelfed/commit/d835e0ad))
-   Update federation config, increase default timeline days falloff to 90 days from 2 days. Fixes #4905 ([011834f4](https://github.com/pixelfed/pixelfed/commit/011834f4))
-   Update cache config, use predis as default redis driver client ([ea6b1623](https://github.com/pixelfed/pixelfed/commit/ea6b1623))
-   Update .gitattributes to collapse diffs on generated files ([ThisIsMissEm](https://github.com/pixelfed/pixelfed/commit/9978b2b9))
-   Update api v1/v2 instance endpoints, bump mastoapi version from 2.7.2 to 3.5.3 ([545f7d5e](https://github.com/pixelfed/pixelfed/commit/545f7d5e))
-   Update ApiV1Controller, implement better limit logic to gracefully handle requests with limits that exceed the max ([1f74a95d](https://github.com/pixelfed/pixelfed/commit/1f74a95d))
-   Update AdminCuratedRegisterController, show oldest applications first ([c4dde641](https://github.com/pixelfed/pixelfed/commit/c4dde641))
-   Update Directory logic, add curated onboarding support ([59c70239](https://github.com/pixelfed/pixelfed/commit/59c70239))
-   Update Inbox and StatusObserver, fix silently rejected direct messages due to saveQuietly which failed to generate a snowflake id ([089ba3c4](https://github.com/pixelfed/pixelfed/commit/089ba3c4))
-   Update Curated Onboarding dashboard, improve application filtering and make it easier to distinguish response state ([2b5d7235](https://github.com/pixelfed/pixelfed/commit/2b5d7235))
-   Update AdminReports, add story reports and fix cs ([767522a8](https://github.com/pixelfed/pixelfed/commit/767522a8))
-   Update AdminReportController, add story report support ([a16309ac](https://github.com/pixelfed/pixelfed/commit/a16309ac))
-   Update kb, add email confirmation issues page ([2f48df8c](https://github.com/pixelfed/pixelfed/commit/2f48df8c))
-   Update AdminCuratedRegisterController, filter confirmation activities from activitylog ([ab9ecb6e](https://github.com/pixelfed/pixelfed/commit/ab9ecb6e))
-   Update Inbox, fix flag validation condition, allow profile reports ([402a4607](https://github.com/pixelfed/pixelfed/commit/402a4607))
-   Update AccountTransformer, fix follower/following count visibility bug ([542d1106](https://github.com/pixelfed/pixelfed/commit/542d1106))
-   Update ProfileMigration model, add target relation ([3f053997](https://github.com/pixelfed/pixelfed/commit/3f053997))
-   Update ApiV1Controller, update Notifications endpoint to filter notifications with missing activities ([a933615b](https://github.com/pixelfed/pixelfed/commit/a933615b))
-   Update ApiV1Controller, fix public timeline scope, properly support both local + remote parameters ([d6eac655](https://github.com/pixelfed/pixelfed/commit/d6eac655))
-   Update ApiV1Controller, handle public feed parameter bug to gracefully fallback to min_id=1 when max_id=0 ([e3826c58](https://github.com/pixelfed/pixelfed/commit/e3826c58))
-   Update ApiV1Controller, fix hashtag feed to include private posts from accounts you follow or your own, and your own unlisted posts ([3b5500b3](https://github.com/pixelfed/pixelfed/commit/3b5500b3))
-   Update checkpoint view, improve input autocomplete. Fixes ([#4959](https://github.com/pixelfed/pixelfed/pull/4959)) ([d18824e7](https://github.com/pixelfed/pixelfed/commit/d18824e7))
-   Update navbar.vue, removes the 50px limit ([#4969](https://github.com/pixelfed/pixelfed/pull/4969)) ([7fd5599](https://github.com/pixelfed/pixelfed/commit/7fd5599))
-   Update ComposeModal.vue, add an informative UI error message when trying to create a mixed media album ([#4886](https://github.com/pixelfed/pixelfed/pull/4886)) ([fd4f41a](https://github.com/pixelfed/pixelfed/commit/fd4f41a))

## [v0.11.12 (2024-02-16)](https://github.com/pixelfed/pixelfed/compare/v0.11.11...v0.11.12)

### Features

-   Autospam Live Filters - block remote activities based on comma separated keywords ([40b45b2a](https://github.com/pixelfed/pixelfed/commit/40b45b2a))
-   Added Software Update banner to admin home feeds ([b0fb1988](https://github.com/pixelfed/pixelfed/commit/b0fb1988))

### Updates

-   Update ApiV1Controller, fix network timeline ([0faf59e3](https://github.com/pixelfed/pixelfed/commit/0faf59e3))
-   Update public/network timelines, fix non-redis response and fix reblogs in home feed ([8b4ac5cc](https://github.com/pixelfed/pixelfed/commit/8b4ac5cc))
-   Update Federation, use proper Content-Type headers for following/follower collections ([fb0bb9a3](https://github.com/pixelfed/pixelfed/commit/fb0bb9a3))
-   Update ActivityPubFetchService, enforce stricter Content-Type validation ([1232cfc8](https://github.com/pixelfed/pixelfed/commit/1232cfc8))
-   Update status view, fix unlisted/private scope bug ([0f3ca194](https://github.com/pixelfed/pixelfed/commit/0f3ca194))

## [v0.11.11 (2024-02-09)](https://github.com/pixelfed/pixelfed/compare/v0.11.10...v0.11.11)

### Fixes

-   Fix api endpoints ([fd7f5dbb](https://github.com/pixelfed/pixelfed/commit/fd7f5dbb))

## [v0.11.10 (2024-02-09)](https://github.com/pixelfed/pixelfed/compare/v0.11.9...v0.11.10)

### Added

-   Resilient Media Storage ([#4665](https://github.com/pixelfed/pixelfed/pull/4665)) ([fb1deb6](https://github.com/pixelfed/pixelfed/commit/fb1deb6))
-   Video WebP2P ([#4713](https://github.com/pixelfed/pixelfed/pull/4713)) ([0405ef12](https://github.com/pixelfed/pixelfed/commit/0405ef12))
-   Added user:2fa command to easily disable 2FA for given account ([c6408fd7](https://github.com/pixelfed/pixelfed/commit/c6408fd7))
-   Added `avatar:storage-deep-clean` command to dispatch remote avatar storage cleanup jobs ([c37b7cde](https://github.com/pixelfed/pixelfed/commit/c37b7cde))
-   Added S3 command to rewrite media urls ([5b3a5610](https://github.com/pixelfed/pixelfed/commit/5b3a5610))
-   Experimental home feed ([#4752](https://github.com/pixelfed/pixelfed/pull/4752)) ([c39b9afb](https://github.com/pixelfed/pixelfed/commit/c39b9afb))
-   Added `app:hashtag-cached-count-update` command to update cached_count of hashtags and add to scheduler to run every 25 minutes past the hour ([1e31fee6](https://github.com/pixelfed/pixelfed/commit/1e31fee6))
-   Added `app:hashtag-related-generate` command to generate related hashtags ([176b4ed7](https://github.com/pixelfed/pixelfed/commit/176b4ed7))
-   Added Mutual Followers API endpoint ([33dbbe46](https://github.com/pixelfed/pixelfed/commit/33dbbe46))
-   Added User Domain Blocks ([#4834](https://github.com/pixelfed/pixelfed/pull/4834)) ([fa0380ac](https://github.com/pixelfed/pixelfed/commit/fa0380ac))
-   Added Parental Controls ([#4862](https://github.com/pixelfed/pixelfed/pull/4862)) ([c91f1c59](https://github.com/pixelfed/pixelfed/commit/c91f1c59))
-   Added Forgot Email Feature ([67c650b1](https://github.com/pixelfed/pixelfed/commit/67c650b1))
-   Added S3 IG Import Media Storage support ([#4891](https://github.com/pixelfed/pixelfed/pull/4891)) ([081360b9](https://github.com/pixelfed/pixelfed/commit/081360b9))

### Federation

-   Update Privacy Settings, add support for Mastodon `indexable` search flag ([fc24630e](https://github.com/pixelfed/pixelfed/commit/fc24630e))
-   Update AP Helpers, consume actor `indexable` attribute ([fbdcdd9d](https://github.com/pixelfed/pixelfed/commit/fbdcdd9d))

### Updates

-   Update FollowerService, add forget method to RelationshipService call to reduce load when mass purging ([347e4f59](https://github.com/pixelfed/pixelfed/commit/347e4f59))
-   Update FollowServiceWarmCache, improve handling larger following/follower lists ([61a6d904](https://github.com/pixelfed/pixelfed/commit/61a6d904))
-   Update StoryApiV1Controller, add viewers route to view story viewers ([941736ce](https://github.com/pixelfed/pixelfed/commit/941736ce))
-   Update NotificationService, improve cache warming query ([2496386d](https://github.com/pixelfed/pixelfed/commit/2496386d))
-   Update StatusService, hydrate accounts on request instead of caching them along with status objects ([223661ec](https://github.com/pixelfed/pixelfed/commit/223661ec))
-   Update profile embed, fix resize ([dc23c21d](https://github.com/pixelfed/pixelfed/commit/dc23c21d))
-   Update Status model, improve thumb logic ([d969a973](https://github.com/pixelfed/pixelfed/commit/d969a973))
-   Update Status model, allow unlisted thumbnails ([1f0a45b7](https://github.com/pixelfed/pixelfed/commit/1f0a45b7))
-   Update StatusTagsPipeline, fix object tags and slug normalization ([d295e605](https://github.com/pixelfed/pixelfed/commit/d295e605))
-   Update Note and CreateNote transformers, include attachment blurhash, width and height ([ce1afe27](https://github.com/pixelfed/pixelfed/commit/ce1afe27))
-   Update ap helpers, store media attachment width and height if present ([8c969191](https://github.com/pixelfed/pixelfed/commit/8c969191))
-   Update Sign-in with Mastodon, allow usage when registrations are closed ([895dc4fa](https://github.com/pixelfed/pixelfed/commit/895dc4fa))
-   Update profile embeds, filter sensitive posts ([ede5ec3b](https://github.com/pixelfed/pixelfed/commit/ede5ec3b))
-   Update ApiV1Controller, hydrate reblog interactions. Fixes ([#4686](https://github.com/pixelfed/pixelfed/issues/4686)) ([135798eb](https://github.com/pixelfed/pixelfed/commit/135798eb))
-   Update AdminReportController, add `profile_id` to group by. Fixes ([#4685](https://github.com/pixelfed/pixelfed/issues/4685)) ([e4d3b196](https://github.com/pixelfed/pixelfed/commit/e4d3b196))
-   Update user:admin command, improve logic. Fixes ([#2465](https://github.com/pixelfed/pixelfed/issues/2465)) ([01bac511](https://github.com/pixelfed/pixelfed/commit/01bac511))
-   Update AP helpers, adjust RemoteAvatarFetch ttl from 24h to 3 months ([36b23fe3](https://github.com/pixelfed/pixelfed/commit/36b23fe3))
-   Update AvatarPipeline, improve refresh logic and garbage collection to purge old avatars ([82798b5e](https://github.com/pixelfed/pixelfed/commit/82798b5e))
-   Update CreateAvatar job, add processing constraints and set `is_remote` attribute ([319ced40](https://github.com/pixelfed/pixelfed/commit/319ced40))
-   Update RemoteStatusDelete and DecrementPostCount pipelines ([edbcf3ed](https://github.com/pixelfed/pixelfed/commit/edbcf3ed))
-   Update lexer regex, fix mention regex and add more tests ([778e83d3](https://github.com/pixelfed/pixelfed/commit/778e83d3))
-   Update StatusTransformer, generate autolink on request ([dfe2379b](https://github.com/pixelfed/pixelfed/commit/dfe2379b))
-   Update ComposeModal component, fix multi filter bug and allow media re-ordering before upload/posting ([56e315f6](https://github.com/pixelfed/pixelfed/commit/56e315f6))
-   Update ApiV1Dot1Controller, allow iar rate limits to be configurable ([28a80803](https://github.com/pixelfed/pixelfed/commit/28a80803))
-   Update ApiV1Dot1Controller, add domain to iar redirect ([1f82d47c](https://github.com/pixelfed/pixelfed/commit/1f82d47c))
-   Update ApiV1Dot1Controller, add configurable app confirm rate limit ttl ([4c6a0719](https://github.com/pixelfed/pixelfed/commit/4c6a0719))
-   Update LikePipeline, dispatch to feed queue. Fixes ([#4723](https://github.com/pixelfed/pixelfed/issues/4723)) ([da510089](https://github.com/pixelfed/pixelfed/commit/da510089))
-   Update AccountImport ([5a2d7e3e](https://github.com/pixelfed/pixelfed/commit/5a2d7e3e))
-   Update ImportPostController, fix IG bug with missing spaces between hashtags ([9c24157a](https://github.com/pixelfed/pixelfed/commit/9c24157a))
-   Update ApiV1Controller, fix mutes in home feed ([ddc21714](https://github.com/pixelfed/pixelfed/commit/ddc21714))
-   Update AP helpers, improve preferredUsername validation ([21218c79](https://github.com/pixelfed/pixelfed/commit/21218c79))
-   Update delete pipelines, properly invoke StatusHashtag delete events ([ce54d29c](https://github.com/pixelfed/pixelfed/commit/ce54d29c))
-   Update mail config ([0e431271](https://github.com/pixelfed/pixelfed/commit/0e431271))
-   Update hashtag following ([015b1b80](https://github.com/pixelfed/pixelfed/commit/015b1b80))
-   Update IncrementPostCount job, prevent overlap ([b2c9cc23](https://github.com/pixelfed/pixelfed/commit/b2c9cc23))
-   Update HashtagFollowService, fix cache invalidation bug ([84f4e885](https://github.com/pixelfed/pixelfed/commit/84f4e885))
-   Update Experimental Home Feed, fix remote posts, shares and reblogs ([c6a6b3ae](https://github.com/pixelfed/pixelfed/commit/c6a6b3ae))
-   Update HashtagService, improve count perf ([3327a008](https://github.com/pixelfed/pixelfed/commit/3327a008))
-   Update StatusHashtagService, remove problematic cache layer ([e5401f85](https://github.com/pixelfed/pixelfed/commit/e5401f85))
-   Update HomeFeedPipeline, fix tag filtering ([f105f4e8](https://github.com/pixelfed/pixelfed/commit/f105f4e8))
-   Update HashtagService, reduce cached_count cache ttl ([15f29f7d](https://github.com/pixelfed/pixelfed/commit/15f29f7d))
-   Update ApiV1Controller, fix include_reblogs param on timelines/home endpoint, and improve limit pagination logic ([287f903b](https://github.com/pixelfed/pixelfed/commit/287f903b))
-   Update StoryApiV1Controller, add self-carousel endpoint. Fixes ([#4352](https://github.com/pixelfed/pixelfed/issues/4352)) ([bcb88d5b](https://github.com/pixelfed/pixelfed/commit/bcb88d5b))
-   Update FollowServiceWarmCache, use more efficient query ([fe9b4c5a](https://github.com/pixelfed/pixelfed/commit/fe9b4c5a))
-   Update HomeFeedPipeline, observe mutes/blocks during fanout ([8548294c](https://github.com/pixelfed/pixelfed/commit/8548294c))
-   Update FederationController, add proper following/follower counts ([3204fb96](https://github.com/pixelfed/pixelfed/commit/3204fb96))
-   Update FederationController, add proper statuses counts ([3204fb96](https://github.com/pixelfed/pixelfed/commit/3204fb96))
-   Update Inbox handler, fix missing object_url and uri fields for direct statuses ([a0157fce](https://github.com/pixelfed/pixelfed/commit/a0157fce))
-   Update DirectMessageController, deliver direct delete activities to user inbox instead of sharedInbox ([d848792a](https://github.com/pixelfed/pixelfed/commit/d848792a))
-   Update DirectMessageController, dispatch deliver and delete actions to the job queue ([7f462a80](https://github.com/pixelfed/pixelfed/commit/7f462a80))
-   Update Inbox, improve story attribute collection ([06bee36c](https://github.com/pixelfed/pixelfed/commit/06bee36c))
-   Update DirectMessageController, dispatch local deletes to pipeline ([98186564](https://github.com/pixelfed/pixelfed/commit/98186564))
-   Update StatusPipeline, fix Direct and Story notification deletion ([4c95306f](https://github.com/pixelfed/pixelfed/commit/4c95306f))
-   Update Notifications.vue, fix deprecated DM action links for story activities ([4c3823b0](https://github.com/pixelfed/pixelfed/commit/4c3823b0))
-   Update ComposeModal, fix missing alttext post state ([0a068119](https://github.com/pixelfed/pixelfed/commit/0a068119))
-   Update PhotoAlbumPresenter.vue, fix fullscreen mode ([822e9888](https://github.com/pixelfed/pixelfed/commit/822e9888))
-   Update Timeline.vue, improve CHT pagination ([9c43e7e2](https://github.com/pixelfed/pixelfed/commit/9c43e7e2))
-   Update HomeFeedPipeline, fix StatusService validation ([041c0135](https://github.com/pixelfed/pixelfed/commit/041c0135))
-   Update Inbox, improve tombstone query efficiency ([759a4393](https://github.com/pixelfed/pixelfed/commit/759a4393))
-   Update AccountService, add setLastActive method ([ebbd98e7](https://github.com/pixelfed/pixelfed/commit/ebbd98e7))
-   Update ApiV1Controller, set last_active_at ([b6419545](https://github.com/pixelfed/pixelfed/commit/b6419545))
-   Update AdminShadowFilter, fix deleted profile bug ([a492a95a](https://github.com/pixelfed/pixelfed/commit/a492a95a))
-   Update FollowerService, add $silent param to remove method to more efficently purge relationships ([1664a5bc](https://github.com/pixelfed/pixelfed/commit/1664a5bc))
-   Update AP ProfileTransformer, add published attribute ([adfaa2b1](https://github.com/pixelfed/pixelfed/commit/adfaa2b1))
-   Update meta tags, improve descriptions and seo/og tags ([fd44c80c](https://github.com/pixelfed/pixelfed/commit/fd44c80c))
-   Update login view, add email prefill logic ([d76f0168](https://github.com/pixelfed/pixelfed/commit/d76f0168))
-   Update LoginController, fix captcha validation error message ([0325e171](https://github.com/pixelfed/pixelfed/commit/0325e171))
-   Update ApiV1Controller, properly cast boolean sensitive parameter. Fixes #4888 ([0aff126a](https://github.com/pixelfed/pixelfed/commit/0aff126a))
-   Update AccountImport.vue, fix new IG export format ([59aa6a4b](https://github.com/pixelfed/pixelfed/commit/59aa6a4b))
-   Update TransformImports command, fix import service condition ([32c59f04](https://github.com/pixelfed/pixelfed/commit/32c59f04))
-   Update AP helpers, more efficently update post count ([7caed381](https://github.com/pixelfed/pixelfed/commit/7caed381))
-   Update AP helpers, refactor post count decrement logic ([b81ae577](https://github.com/pixelfed/pixelfed/commit/b81ae577))
-   Update AP helpers, fix sensitive bug ([00ed330c](https://github.com/pixelfed/pixelfed/commit/00ed330c))
-   Update NotificationEpochUpdatePipeline, use more efficient query ([4d401389](https://github.com/pixelfed/pixelfed/commit/4d401389))
-   Update notification pipelines, fix non-local saving ([fa97a1f3](https://github.com/pixelfed/pixelfed/commit/fa97a1f3))
-   Update NodeinfoService, disable redirects ([240e6bbe](https://github.com/pixelfed/pixelfed/commit/240e6bbe))
-   Update Instance model, add entity casts ([289cad47](https://github.com/pixelfed/pixelfed/commit/289cad47))
-   Update FetchNodeinfoPipeline, use more efficient dispatch ([ac01f51a](https://github.com/pixelfed/pixelfed/commit/ac01f51a))
-   Update horizon.php config ([1e3acade](https://github.com/pixelfed/pixelfed/commit/1e3acade))
-   Update PublicApiController, consume InstanceService blocked domains for account and statuses endpoints ([01b33fb3](https://github.com/pixelfed/pixelfed/commit/01b33fb3))
-   Update ApiV1Controller, enforce blocked instance domain logic ([5b284cac](https://github.com/pixelfed/pixelfed/commit/5b284cac))
-   Update ApiV2Controller, add vapid key to instance object. Thanks thisismissem! ([4d02d6f1](https://github.com/pixelfed/pixelfed/commit/4d02d6f1))

## [v0.11.9 (2023-08-21)](https://github.com/pixelfed/pixelfed/compare/v0.11.8...v0.11.9)

### Added

-   Import from Instagram ([#4466](https://github.com/pixelfed/pixelfed/pull/4466)) ([cf3078c5](https://github.com/pixelfed/pixelfed/commit/cf3078c5))
-   Sign-in with Mastodon ([#4545](https://github.com/pixelfed/pixelfed/pull/4545)) ([45b9404e](https://github.com/pixelfed/pixelfed/commit/45b9404e))
-   Health check endpoint at /api/service/health-check ([ff58f970](https://github.com/pixelfed/pixelfed/commit/ff58f970))
-   Reblogs in home feed ([#4563](https://github.com/pixelfed/pixelfed/pull/4563)) ([b86d47bf](https://github.com/pixelfed/pixelfed/commit/b86d47bf))
-   Account Migrations ([#4578](https://github.com/pixelfed/pixelfed/pull/4578)) ([a9220e4e](https://github.com/pixelfed/pixelfed/commit/a9220e4e))

### Updates

-   Update Notifications.vue component, fix filtering logic to prevent endless spinner ([3df9b53f](https://github.com/pixelfed/pixelfed/commit/3df9b53f))
-   Update Direct Messages, fix api endpoint ([fe8728c0](https://github.com/pixelfed/pixelfed/commit/fe8728c0))
-   Update nginx config ([fbdc6358](https://github.com/pixelfed/pixelfed/commit/fbdc6358))
-   Update api routes, add DeprecatedEndpoint middleware. For more info, visit [pixelfed.org/kb/10404](https://pixelfed.org/kb/10404) ([a8453e77](https://github.com/pixelfed/pixelfed/commit/a8453e77))
-   Update admin dashboard, improve users section ([36b6bf48](https://github.com/pixelfed/pixelfed/commit/36b6bf48))
-   Update AdminApiController, add instance stats endpoint ([89c3710d](https://github.com/pixelfed/pixelfed/commit/89c3710d))
-   Update config, re-add `PF_MAX_USERS` .env variable to limit max users to 1000 by default ([a6d10f03](https://github.com/pixelfed/pixelfed/commit/a6d10f03))
-   Update AdminApiController, fix stats ([5c5541fc](https://github.com/pixelfed/pixelfed/commit/5c5541fc))
-   Update AdminApiController, include more data for getUser method ([4f850e54](https://github.com/pixelfed/pixelfed/commit/4f850e54))
-   Update AdminApiController, improve admin moderation tools ([763ce19a](https://github.com/pixelfed/pixelfed/commit/763ce19a))
-   Update ActivityPubFetchService, fix authorized_fetch compatibility. Closes #1850, #2713, #2935 ([63a7879c](https://github.com/pixelfed/pixelfed/commit/63a7879c))
-   Update IG Import commands, fix stalled import queue ([b18f3fba](https://github.com/pixelfed/pixelfed/commit/b18f3fba))
-   Update TransformImports command, improve handling of imported posts that already exist or are from deleted accounts ([892907d5](https://github.com/pixelfed/pixelfed/commit/892907d5))
-   Update console kernel, add import upload gc ([afe6948d](https://github.com/pixelfed/pixelfed/commit/afe6948d))
-   Update ImportService, filter deleted posts from getImportedPosts endpoint ([10dd348c](https://github.com/pixelfed/pixelfed/commit/10dd348c))
-   Update FixStatusCount, improve command and support remote count resync ([04f4f8ba](https://github.com/pixelfed/pixelfed/commit/04f4f8ba))
-   Update StatusRemoteUpdatePipeline, fix missing mime and size attributes that cause empty media previews on our mobile app ([ea54413e](https://github.com/pixelfed/pixelfed/commit/ea54413e))
-   Update ComposeModal.vue, fix scroll issue and dont hide scrollbar ([2d959fb3](https://github.com/pixelfed/pixelfed/commit/2d959fb3))
-   Update AccountImport, add select first 100 posts button ([625a76a5](https://github.com/pixelfed/pixelfed/commit/625a76a5))
-   Update ApiV1Controller, add include_reblogs attribute to home timeline ([37fd0342](https://github.com/pixelfed/pixelfed/commit/37fd0342))
-   Update rate limits, fixes #4537 ([1cc6274a](https://github.com/pixelfed/pixelfed/commit/1cc6274a))
-   Update Services, use zpopmin on predis ([4b2c66f5](https://github.com/pixelfed/pixelfed/commit/4b2c66f5))
-   Update Inbox, allow storing Create->Note activities without any local followers, disabled by default ([9fa6b3f7](https://github.com/pixelfed/pixelfed/commit/9fa6b3f7))
-   Update AP Helpers, preserve admin unlisted state before adding to NetworkTimelineService ([0704c7e0](https://github.com/pixelfed/pixelfed/commit/0704c7e0))
-   Update SearchApiV2Service, improve resolve query logic to better handle remote posts/profiles and local posts/profiles ([c61d0b91](https://github.com/pixelfed/pixelfed/commit/c61d0b91))
-   Update FollowPipeline, improve follower/following count calculation ([0b515767](https://github.com/pixelfed/pixelfed/commit/0b515767))
-   Update TransformImports command, increment status_count on profile model ([ba7551d8](https://github.com/pixelfed/pixelfed/commit/ba7551d8))
-   Update AP Helpers, improve url validation and add optional dns verification, disabled by default ([2bef3e41](https://github.com/pixelfed/pixelfed/commit/2bef3e41))
-   Update admin users blade view, show last_active_at and other info ([e0b48b29](https://github.com/pixelfed/pixelfed/commit/e0b48b29))
-   Update MediaStorageService, improve head header handling ([3590adbd](https://github.com/pixelfed/pixelfed/commit/3590adbd))
-   Update admin user view, improve previews ([ff2c16fe](https://github.com/pixelfed/pixelfed/commit/ff2c16fe))
-   Update FanoutDeletePipeline, fix AP object ([0d802c31](https://github.com/pixelfed/pixelfed/commit/0d802c31))
-   Update Remote Auth feature, fix custom domain bug and enforce banned domains ([acabf603](https://github.com/pixelfed/pixelfed/commit/acabf603))
-   Update StatusService, reduce cache ttl from 7 days to 6 hours ([59b64378](https://github.com/pixelfed/pixelfed/commit/59b64378))
-   Update ProfileController, allow albums in atom feed. Closes #4561. Fixes #4526 ([1c105a6c](https://github.com/pixelfed/pixelfed/commit/1c105a6c))
-   Update admin users view, fix website value. Closes #4557 ([c469d475](https://github.com/pixelfed/pixelfed/commit/c469d475))
-   Update StatusStatelessTransformer, allow unlisted reblogs ([1c13b518](https://github.com/pixelfed/pixelfed/commit/1c13b518))
-   Update ApiV1Controller, hydrate reblog state in home timeline ([13bdaa2e](https://github.com/pixelfed/pixelfed/commit/13bdaa2e))
-   Update Timeline component, improve reblog support ([29de91e5](https://github.com/pixelfed/pixelfed/commit/29de91e5))
-   Update timeline settings, add photo reblogs only option ([e2705b9a](https://github.com/pixelfed/pixelfed/commit/e2705b9a))
-   Update PostContent, add text cw warning ([911504fa](https://github.com/pixelfed/pixelfed/commit/911504fa))
-   Update ActivityPubFetchService, add validateUrl parameter to bypass url validation to fetch content from blocked instances ([3d1b6516](https://github.com/pixelfed/pixelfed/commit/3d1b6516))
-   Update RemoteStatusDelete pipeline ([71e92261](https://github.com/pixelfed/pixelfed/commit/71e92261))
-   Update RemoteStatusDelete pipeline ([fab8f25e](https://github.com/pixelfed/pixelfed/commit/fab8f25e))
-   Update RemoteStatusPipeline, fix reply check ([618b6727](https://github.com/pixelfed/pixelfed/commit/618b6727))
-   Update ApiV1Controller, add bookmarked to timeline entities ([ca746717](https://github.com/pixelfed/pixelfed/commit/ca746717))

## [v0.11.8 (2023-05-29)](https://github.com/pixelfed/pixelfed/compare/v0.11.7...v0.11.8)

### API Changes

-   Added `following_since` attribute to `/api/v1/accounts/relationships` endpoint when `_pe=1` (pixelfed entity) parameter is present ([992d910b](https://github.com/pixelfed/pixelfed/commit/992d910b))
-   Added `/api/v1.1/accounts/app/settings` endpoint and UserAppSettings model to store app specific settings ([a2305d5f](https://github.com/pixelfed/pixelfed/commit/a2305d5f))

### Added

-   Post edits ([#4416](https://github.com/pixelfed/pixelfed/pull/4416)) ([98cf8f3](https://github.com/pixelfed/pixelfed/commit/98cf8f3))

### Updates

-   Update StatusService, fix bug in getFull method ([4d8b4dcf](https://github.com/pixelfed/pixelfed/commit/4d8b4dcf))
-   Update Config, bump version for post edit support without having to clear cache ([c0190d84](https://github.com/pixelfed/pixelfed/commit/c0190d84))
-   Update EditHistoryModal, fix caption rendering ([0f803446](https://github.com/pixelfed/pixelfed/commit/0f803446))
-   Update StatusRemoteUpdatePipeline, fix typo ([109d0419](https://github.com/pixelfed/pixelfed/commit/109d0419))
-   Update StatusActivityPubDeliver, fix delivery addressing ([1f2183ee](https://github.com/pixelfed/pixelfed/commit/1f2183ee))
-   Update UpdateStatusService, fix formatting issue. Fixes #4423 ([4479055e](https://github.com/pixelfed/pixelfed/commit/4479055e))
-   Update nginx config ([ee3b6e09](https://github.com/pixelfed/pixelfed/commit/ee3b6e09))
-   Update Status model, increase max mentions, hashtags and links ([1430f532](https://github.com/pixelfed/pixelfed/commit/1430f532))

## [v0.11.7 (2023-05-24)](https://github.com/pixelfed/pixelfed/compare/v0.11.6...v0.11.7)

### API Changes

-   Added [/api/v1/followed_tags](https://docs.joinmastodon.org/methods/followed_tags/) api endpoint ([175a8486](https://github.com/pixelfed/pixelfed/commit/175a8486))
-   Added [/api/v1/tags/:id/follow](https://docs.joinmastodon.org/methods/tags/#follow) and [/api/v1/tags/:id/unfollow](https://docs.joinmastodon.org/methods/tags/#unfollow) api endpoints ([4d997bb9](https://github.com/pixelfed/pixelfed/commit/4d997bb9))
-   Added [/api/v1/tags/:id](https://docs.joinmastodon.org/methods/tags/) api endpoint ([521b3b4c](https://github.com/pixelfed/pixelfed/commit/521b3b4c))
-   Added `only_media` support to /api/v1/timelines/tag/:id api endpoint ([b5fe956a](https://github.com/pixelfed/pixelfed/commit/b5fe956a))
-   Added /api/v2/instance api endpoint ([167dbcdd](https://github.com/pixelfed/pixelfed/commit/167dbcdd))
-   Removed api endpoint cloud ip block logic ([6a2daf1f](https://github.com/pixelfed/pixelfed/commit/6a2daf1f))
-   Added idempotency-key support to /api/v1/statuses endpoint ([c54cdd3e](https://github.com/pixelfed/pixelfed/commit/c54cdd3e))

### Added

-   Added store remote media on S3 config setting, disabled by default ([51768083](https://github.com/pixelfed/pixelfed/commit/51768083))
-   Added Autospam Advanced Detection ([132a58de](https://github.com/pixelfed/pixelfed/commit/132a58de))

### Updates

-   Update admin dashboard, fix search and dropdown menu ([dac0d083](https://github.com/pixelfed/pixelfed/commit/dac0d083))
-   Update sudo mode view, fix trusted device checkbox ([8ef900bf](https://github.com/pixelfed/pixelfed/commit/8ef900bf))
-   Update SearchApiV2Service, improve postgres support ([666e5732](https://github.com/pixelfed/pixelfed/commit/666e5732))
-   Update StoryController, show active self stories on home timeline ([633351f6](https://github.com/pixelfed/pixelfed/commit/633351f6))
-   Update ApiV1Controller, fix trending accounts format. Closes #4356 ([37bd2ee5](https://github.com/pixelfed/pixelfed/commit/37bd2ee5))
-   Update instance config, enable config cache by default ([970f77b0](https://github.com/pixelfed/pixelfed/commit/970f77b0))
-   Update Admin Dashboard, allow admins to designate an admin account for the landing page and instance api endpoint ([6ea2bdc7](https://github.com/pixelfed/pixelfed/commit/6ea2bdc7))
-   Update config, enable oauth by default ([6a2e9e8f](https://github.com/pixelfed/pixelfed/commit/6a2e9e8f))
-   Update StatusService, fix missing account condition ([f48daab3](https://github.com/pixelfed/pixelfed/commit/f48daab3))
-   Update ProfileService, add softFail param ([6bc20a37](https://github.com/pixelfed/pixelfed/commit/6bc20a37))
-   Update MediaTagService, fix ProfileService to soft fail on missing or deleted accounts ([df444851](https://github.com/pixelfed/pixelfed/commit/df444851))
-   Update LikeService, improve likedBy logic to soft fail on missing or deleted accounts ([91ba1398](https://github.com/pixelfed/pixelfed/commit/91ba1398))
-   Update StatusTransformers, fix ProfileService to soft fail on missing or deleted accounts ([43d3aa2b](https://github.com/pixelfed/pixelfed/commit/43d3aa2b))
-   Update ApiV1Controller, fix hashtag timeline ([fc1a385c](https://github.com/pixelfed/pixelfed/commit/fc1a385c))
-   Update settings view, add fallback avatar ([1a83c585](https://github.com/pixelfed/pixelfed/commit/1a83c585))
-   Update HashtagFollow model, add MAX_LIMIT of 250 tags per account ([ed352141](https://github.com/pixelfed/pixelfed/commit/ed352141))
-   Update Notification logic, remove message and rendered fields ([6cdb5bc6](https://github.com/pixelfed/pixelfed/commit/6cdb5bc6))
-   Update InstanceService, fix banner blurhash memory bug ([3aad75ab](https://github.com/pixelfed/pixelfed/commit/3aad75ab))
-   Update models, remove deprecated toText and toHtml method ([ea943333](https://github.com/pixelfed/pixelfed/commit/ea943333))
-   Update Notification components, add autospam notification support ([0d3b4bc2](https://github.com/pixelfed/pixelfed/commit/0d3b4bc2))
-   Update AutoSpam Bouncer, generate notification on positive detections ([d5f63f8a](https://github.com/pixelfed/pixelfed/commit/d5f63f8a))
-   Update admin autospam apis, remove autospam warning notifications when appropriate ([588ca653](https://github.com/pixelfed/pixelfed/commit/588ca653))
-   Update StatusEntityLexer, stop saving entities ([a91a5e48](https://github.com/pixelfed/pixelfed/commit/a91a5e48))
-   Update UserCreate command, fix is_admin flag ([ad25ed67](https://github.com/pixelfed/pixelfed/commit/ad25ed67))
-   Update Bouncer, adjust advanced Autospam logic ([18cddd43](https://github.com/pixelfed/pixelfed/commit/18cddd43))
-   Update atom view, fix atom feed bug ([63b72c42](https://github.com/pixelfed/pixelfed/commit/63b72c42))
-   Update StatusController, disable post embeds from spam accounts ([c167af43](https://github.com/pixelfed/pixelfed/commit/c167af43))
-   Update ProfileController, require login to view spam accounts, and disable profile embeds and atom feeds for spam accounts ([dd2f5bb9](https://github.com/pixelfed/pixelfed/commit/dd2f5bb9))
-   Update Settings, allow users to disable atom feeds ([3662d3de](https://github.com/pixelfed/pixelfed/commit/3662d3de))
-   Update ApiV1Controller, filter muted/blocked accounts from tag timeline ([f42c1140](https://github.com/pixelfed/pixelfed/commit/f42c1140))
-   Update admin moderation logic, only re-add top level posts ([c6ffda96](https://github.com/pixelfed/pixelfed/commit/c6ffda96))
-   Update admin dashboard, add mass account deletes ([b8426cce](https://github.com/pixelfed/pixelfed/commit/b8426cce))
-   Update scheduler, fix S3 media garbage collection not being executed when cloud storage is enabled via dashboard without .env/config being enabled ([adb070f1](https://github.com/pixelfed/pixelfed/commit/adb070f1))
-   Update MediaController, add fallback for local files that are later stored on S3 but still are referenced in cached objects remotely ([4973cb46](https://github.com/pixelfed/pixelfed/commit/4973cb46))
-   Update PublicTimelineService, improve warmCache query ([9f901d65](https://github.com/pixelfed/pixelfed/commit/9f901d65))
-   Update AP Inbox, fix delete handling ([2800c888](https://github.com/pixelfed/pixelfed/commit/2800c888))
-   Update login/register views and captcha config, enable login or register captchas or both ([c071c719](https://github.com/pixelfed/pixelfed/commit/c071c719))
-   Update login form, allow admins to enable captcha after X failed attempts. Admins can set the number of attempts before captcha is shown, default is 2 attempts before captcha is required ([221ddce0](https://github.com/pixelfed/pixelfed/commit/221ddce0))

## [v0.11.6 (2023-05-03)](https://github.com/pixelfed/pixelfed/compare/v0.11.5...v0.11.6)

### Added

-   Add php 8.2 support. Bump laravel version, v9 => v10 ([fb4ac4eb](https://github.com/pixelfed/pixelfed/commit/fb4ac4eb))
-   New media:fix-nonlocal-driver command. Fixes s3 media created with invalid FILESYSTEM_DRIVER=s3 configuration ([672cccd4](https://github.com/pixelfed/pixelfed/commit/672cccd4))
-   New landing page design ([09c0032b](https://github.com/pixelfed/pixelfed/commit/09c0032b))
-   Add cloud ip bans to BouncerService (disabled by default) ([50ab2e20](https://github.com/pixelfed/pixelfed/commit/50ab2e20))
-   Redesigned Admin Dashboard Reports/Moderation ([c6cc6327](https://github.com/pixelfed/pixelfed/commit/c6cc6327))

### Fixes

-   Fixed `violates check constraint "statuses_visibility_check"` bug affecting postgres instances + various api endpoints ([79b6a17e](https://github.com/pixelfed/pixelfed/commit/79b6a17e))
-   Fixed duplicate hashtags on postgres ([64059cb4](https://github.com/pixelfed/pixelfed/commit/64059cb4))
-   Fixed custom emoji domain search on postgres. Closes #4333 ([3dac45f3](https://github.com/pixelfed/pixelfed/commit/3dac45f3))

### Updates

-   Update ApiV1Controller, fix blocking remote accounts. Closes #4256 ([8e71e0c0](https://github.com/pixelfed/pixelfed/commit/8e71e0c0))
-   Update ComposeController, fix postgres location search. Closes #4242 and #4239 ([64a4a006](https://github.com/pixelfed/pixelfed/commit/64a4a006))
-   Update app.js, add title attribute to iframe embeds to comply with accessibility requirements ([4d72b9e3](https://github.com/pixelfed/pixelfed/commit/4d72b9e3))
-   Update MediaPathService, fix story path ([aebbad96](https://github.com/pixelfed/pixelfed/commit/aebbad96))
-   Update Story v1.1 api endpoints ([855e9626](https://github.com/pixelfed/pixelfed/commit/855e9626))
-   Update ApiV1Controller, filter mute/blocks on statuses/context and statuses/replies endpoints ([73aa01e8](https://github.com/pixelfed/pixelfed/commit/73aa01e8))
-   Update filesystems, store all files as public by default and add default permissions. Fixes #4273, #4275. Closes #3825 ([22da2647](https://github.com/pixelfed/pixelfed/commit/22da2647))
-   Update Profile model, fix avatar url path generation. Fixes #4041, Fixes #4031, Fixes #3523 ([28bf8649](https://github.com/pixelfed/pixelfed/commit/28bf8649))
-   Update filesystem config, change FILESYSTEM_DRIVER env variable to DANGEROUSLY_SET_FILESYSTEM_DRIVER and remove from default env configs. Changing the default filesystem should be avoided, use FILESYSTEM_CLOUD for s3 support, otherwise you can break things ([573c88d7](https://github.com/pixelfed/pixelfed/commit/573c88d7))
-   Update MediaS3GarbageCollector, fix handle ([2eee36cf](https://github.com/pixelfed/pixelfed/commit/2eee36cf))
-   Update StatusController, allow users to delete replies to posts ([738925c2](https://github.com/pixelfed/pixelfed/commit/738925c2))
-   Update admin autospam/report email templates, remove image previews ([76be49ac](https://github.com/pixelfed/pixelfed/commit/76be49ac))
-   Update LandingService, enable landing directory/explore feed by default and move configuration to config/instance.php file ([780f2507](https://github.com/pixelfed/pixelfed/commit/780f2507))
-   Update ImageOptimizePipeline, improve support for disabling image optimizations ([e76289e4](https://github.com/pixelfed/pixelfed/commit/e76289e4))
-   Update LandingController, fix config variable names ([b716926b](https://github.com/pixelfed/pixelfed/commit/b716926b))
-   Update Privacy Settings, add Directory setting ([634c15e4](https://github.com/pixelfed/pixelfed/commit/634c15e4))
-   Update site config ([6d59dc8e](https://github.com/pixelfed/pixelfed/commit/6d59dc8e))
-   Update db:raw queries to support laravel v10 ([849e5103](https://github.com/pixelfed/pixelfed/commit/849e5103))
-   Update RegisterController, store client ip during registration ([d4c967de](https://github.com/pixelfed/pixelfed/commit/d4c967de))
-   Update ApiV1Controller, fix account blocks. Closes #4304 ([98739139](https://github.com/pixelfed/pixelfed/commit/98739139))
-   Update RegisterController, improve max_users calculation and add kb page to redirect to if conditions are met ([1bbee6d0](https://github.com/pixelfed/pixelfed/commit/1bbee6d0))
-   Update SecuritySettings, remove imagick depdency for 2FA qr code generation image ([506f95c6](https://github.com/pixelfed/pixelfed/commit/506f95c6))
-   Update 2fa checkpoint view design ([86c472ac](https://github.com/pixelfed/pixelfed/commit/86c472ac))
-   Update sudo mode checkpoint view design ([091e0b2c](https://github.com/pixelfed/pixelfed/commit/091e0b2c))
-   Update ForgotPasswordController, add captcha support, improve security and a new redesigned view ([f6e7ff64](https://github.com/pixelfed/pixelfed/commit/f6e7ff64))
-   Update ResetPasswordController, add captcha support, improve security and a new redesigned view ([0ab5b96a](https://github.com/pixelfed/pixelfed/commit/0ab5b96a))
-   Update Inbox, remove handleCreateActivity logic that rejected posts from accounts without followers ([a93a3efd](https://github.com/pixelfed/pixelfed/commit/a93a3efd))
-   Update ApiV1Controller and DiscoverController, fix postgres hashtag search ([055aa6b3](https://github.com/pixelfed/pixelfed/commit/055aa6b3))
-   Update StatusTagsPipeline, deduplicate hashtags on postgres ([867cbc75](https://github.com/pixelfed/pixelfed/commit/867cbc75))
-   Update SearchApiV2Service, fix postgres hashtag search and prepend wildcard operator to improve results ([6e20d0a6](https://github.com/pixelfed/pixelfed/commit/6e20d0a6))

## [v0.11.5 (2023-03-25)](https://github.com/pixelfed/pixelfed/compare/v0.11.4...v0.11.5)

### New Features

-   Mobile App Registration ([#3829](https://github.com/pixelfed/pixelfed/pull/3829))
-   Portfolios ([#3705](https://github.com/pixelfed/pixelfed/pull/3705))
-   Server Directory ([#3762](https://github.com/pixelfed/pixelfed/pull/3762))
-   Manually verify email address (php artisan user:verifyemail) ([682f5f0f](https://github.com/pixelfed/pixelfed/commit/682f5f0f))
-   Manually generate in-app registration confirmation links (php artisan user:app-magic-link) ([73eb9e36](https://github.com/pixelfed/pixelfed/commit/73eb9e36))
-   Optional home feed caching ([3328b367](https://github.com/pixelfed/pixelfed/commit/3328b367))
-   Admin Invites ([b73ca9a1](https://github.com/pixelfed/pixelfed/commit/b73ca9a1))
-   Hashtag administration ([84872311](https://github.com/pixelfed/pixelfed/commit/84872311))
-   Admin report email notifications ([4e1d0ed5](https://github.com/pixelfed/pixelfed/commit/4e1d0ed5))
-   Add Licenses help page, fixes #4238 ([3c712a70](https://github.com/pixelfed/pixelfed/commit/3c712a70))

### Updates

-   Update ApiV1Controller, include self likes in favourited_by endpoint ([58b331d2](https://github.com/pixelfed/pixelfed/commit/58b331d2))
-   Update PublicApiController, remove expensive and unused relationships ([2ecc3144](https://github.com/pixelfed/pixelfed/commit/2ecc3144))
-   Update status deletion, fix database lock issues and side effects ([04e8c96a](https://github.com/pixelfed/pixelfed/commit/04e8c96a))
-   Fix remote profile avatar urls when storing locally ([b0422d4f](https://github.com/pixelfed/pixelfed/commit/b0422d4f))
-   Enable network timeline caching by default ([c990ac2a](https://github.com/pixelfed/pixelfed/commit/c990ac2a))
-   Redirect /home to / ([97032997](https://github.com/pixelfed/pixelfed/commit/97032997))
-   Fix 2FA backup code bug ([a231b3c5](https://github.com/pixelfed/pixelfed/commit/a231b3c5))
-   Update federation config, enable remote follows by default ([59702d40](https://github.com/pixelfed/pixelfed/commit/59702d40))
-   Update ApiV1Controller, fix followAccountById with firstOrCreate() ([1d52ad0b](https://github.com/pixelfed/pixelfed/commit/1d52ad0b))
-   Update AccountService, fix delete status ([8b7121f9](https://github.com/pixelfed/pixelfed/commit/8b7121f9))
-   Update ap helpers, fix duplicate entry bug ([85cfa1ba](https://github.com/pixelfed/pixelfed/commit/85cfa1ba))
-   Update Inbox, fix handleUndoActivity ([d660e46b](https://github.com/pixelfed/pixelfed/commit/d660e46b))
-   Update HomeSettings controller, bail earlier when attempting to update email that already exists ([399bf5f8](https://github.com/pixelfed/pixelfed/commit/399bf5f8))
-   Update ProfileController, cache actor object and atom feed ([8665eab1](https://github.com/pixelfed/pixelfed/commit/8665eab1))
-   Update NotificationTransformer, fix mediaTag and modLog types ([b6c06c4b](https://github.com/pixelfed/pixelfed/commit/b6c06c4b))
-   Update landing view, add `app.name` and `app.short_description` for better customizability ([bda9d16b](https://github.com/pixelfed/pixelfed/commit/bda9d16b))
-   Update Profile, fix avatarUrl paths. Fixes #3559 #3634 ([989e4249](https://github.com/pixelfed/pixelfed/commit/989e4249))
-   Update InboxPipeline, bump request timeout from 5s to 60s ([bb120019](https://github.com/pixelfed/pixelfed/commit/bb120019))
-   Update web routes, fix missing home route ([a9f4ddfc](https://github.com/pixelfed/pixelfed/commit/a9f4ddfc))
-   Allow forceHttps to be disabled, fixes #3710 ([a31bdec7](https://github.com/pixelfed/pixelfed/commit/a31bdec7))
-   Update MediaStorageService, fix size check bug ([319f0ba5](https://github.com/pixelfed/pixelfed/commit/319f0ba5))
-   Update AvatarSync, fix sync skipping recently fetched avatars by setting last_fetched_at to null before refetching ([a83fc798](https://github.com/pixelfed/pixelfed/commit/a83fc798))
-   Refactor AvatarStorage to support migrating avatars to cloud storage, fix remote avatar refetching and merge AvatarSync commands and add deprecation notice to avatar:sync command ([223aea47](https://github.com/pixelfed/pixelfed/commit/223aea47))
-   Update AvatarStorage, improve overview calculations ([733b9fd0](https://github.com/pixelfed/pixelfed/commit/733b9fd0))
-   Update filesystem config, fix DO Spaces root default ([720b6eb3](https://github.com/pixelfed/pixelfed/commit/720b6eb3))
-   Update Avatar pipeline, fix cloud storage media_path ([02edd19d](https://github.com/pixelfed/pixelfed/commit/02edd19d))
-   Update FederationController, add instance actor profile to webfinger ([6e3c8097](https://github.com/pixelfed/pixelfed/commit/6e3c8097))
-   Update MediaService, add summary attribute for better alt text federation ([a12712cc](https://github.com/pixelfed/pixelfed/commit/a12712cc))
-   Update AvatarObserver, fix cloud delete bug by checking if cloud storage is enabled ([9f7672f5](https://github.com/pixelfed/pixelfed/commit/9f7672f5))
-   Update DeleteAccountPipeline, dispatch on low queue ([6eabe07c](https://github.com/pixelfed/pixelfed/commit/6eabe07c))
-   Update DeleteAccountPipeline, handle flysystem v3 changes by checking files exist before attempting to delete ([23e2998f](https://github.com/pixelfed/pixelfed/commit/23e2998f))
-   Update FollowerService, use redis sorted sets for follower relations ([356cc277](https://github.com/pixelfed/pixelfed/commit/356cc277))
-   Update FollowerService, use redis sorted sets for following relations ([f46b01af](https://github.com/pixelfed/pixelfed/commit/f46b01af))
-   Update PublicApiController, refactor follower/following api endpoints to consume FollowerService instead of querying database ([b39f91b4](https://github.com/pixelfed/pixelfed/commit/b39f91b4))
-   Update follower/following profile layout, optimized for mobile devices and use FollowerService ([78a5575d](https://github.com/pixelfed/pixelfed/commit/78a5575d))
-   Update sidebar menu, when clicking on the active feed/timeline buttons force a reload and scroll to top of feed ([78a5575d](https://github.com/pixelfed/pixelfed/commit/78a5575d))
-   Update InboxPipeline, increase timeout from 60s to 300s ([d1b888b5](https://github.com/pixelfed/pixelfed/commit/d1b888b5))
-   Update backup config, fixes #3793, #3920, #3931 ([b0c4cc30](https://github.com/pixelfed/pixelfed/commit/b0c4cc30))
-   Update FederationController, add two new queues (follow, shared) to prioritize follow request handling ([8ba33864](https://github.com/pixelfed/pixelfed/commit/8ba33864))
-   Dispatch follow accept/reject pipeline jobs to follow queue ([aaed2bf6](https://github.com/pixelfed/pixelfed/commit/aaed2bf6))
-   Update MediaStorageService, improve support for pleroma .blob avatars ([66226658](https://github.com/pixelfed/pixelfed/commit/66226658))
-   Update ApiV1Controller, remove min avatar size limit, fixes #3715 ([2b0db812](https://github.com/pixelfed/pixelfed/commit/2b0db812))
-   Update InboxPipeline, add inbox job queue and separate http sig validation from activity handling ([e6c1604d](https://github.com/pixelfed/pixelfed/commit/e6c1604d))
-   Update InboxPipeline, dispatch Follow/Accept Follow jobs to follow queue ([f62d2494](https://github.com/pixelfed/pixelfed/commit/f62d2494))
-   Add MediaS3GarbageCollector command to clear local media after uploaded to S3 disks after 12 hours ([b8c3f153](https://github.com/pixelfed/pixelfed/commit/b8c3f153))
-   Update MediaS3GarbageCollector command, disable logging by default and optimize huge invocations ([a14af93b](https://github.com/pixelfed/pixelfed/commit/a14af93b))
-   Update MediaStorageService, clear MediaService and StatusService caches after localToCloud ([de56b0f0](https://github.com/pixelfed/pixelfed/commit/de56b0f0))
-   Add CloudMediaMigrate command to migrate older local media to cloud storage ([382d00d9](https://github.com/pixelfed/pixelfed/commit/382d00d9))
-   Update MediaS3GarbageCollector command, handle thumbnail deletion ([95bbcc38](https://github.com/pixelfed/pixelfed/commit/95bbcc38))
-   Update StatusReplyPipeline, remove expensive reply count re-calculation query ([a2f8aad1](https://github.com/pixelfed/pixelfed/commit/a2f8aad1))
-   Update CommentPipeline, remove expensive reply count re-calculation query ([b457a446](https://github.com/pixelfed/pixelfed/commit/b457a446))
-   Update FederationController, improve inbox/sharedInbox delete handling ([2180a2de](https://github.com/pixelfed/pixelfed/commit/2180a2de))
-   Update HashtagController, improve trending hashtag endpoint ([4873c7dd](https://github.com/pixelfed/pixelfed/commit/4873c7dd))
-   Fix CustomEmoji, properly handle shortcode updates and delete old copy in case the extension changes ([bc29073a](https://github.com/pixelfed/pixelfed/commit/bc29073a))
-   Update reply pipelines, restore reply_count logic ([0d780ffb](https://github.com/pixelfed/pixelfed/commit/0d780ffb))
-   Update StatusTagsPipeline, reject if `type` not set ([91085c45](https://github.com/pixelfed/pixelfed/commit/91085c45))
-   Update ReplyPipelines, use more efficent reply count calculation ([d4dfa95c](https://github.com/pixelfed/pixelfed/commit/d4dfa95c))
-   Update StatusDelete pipeline, dispatch async ([257c0949](https://github.com/pixelfed/pixelfed/commit/257c0949))
-   Update lexer/extractor to handle banned hashtags ([909a8a5a](https://github.com/pixelfed/pixelfed/commit/909a8a5a))
-   Update FederationController, fix double lock bug ([9fcccca9](https://github.com/pixelfed/pixelfed/commit/9fcccca9))
-   Update AdminInvite component, fix email regex ([2aea77d3](https://github.com/pixelfed/pixelfed/commit/2aea77d3))
-   Update database config, use single transaction and skip lock tables for mysql dump ([936f1e7a](https://github.com/pixelfed/pixelfed/commit/936f1e7a))
-   Update database config, add sticky flag https://laravel.com/docs/9.x/database#the-sticky-option ([10b65980](https://github.com/pixelfed/pixelfed/commit/10b65980))
-   Update profile audience to filter blocked instances ([e0c3dae3](https://github.com/pixelfed/pixelfed/commit/e0c3dae3))
-   Update SearchApiV2Service, improve query performance ([4d1f2811](https://github.com/pixelfed/pixelfed/commit/4d1f2811))
-   Update InstanceService, improve unlisted/banned network post filtering ([a0da6ec3](https://github.com/pixelfed/pixelfed/commit/a0da6ec3))
-   Update ApiV1DotController, fix inAppRegistrationConfirm logic ([6cfbedd9](https://github.com/pixelfed/pixelfed/commit/6cfbedd9))
-   Update ApiV1Controller, allow description (alt text) updates after status is published ([869c3ed1](https://github.com/pixelfed/pixelfed/commit/869c3ed1))
-   Update AdminApiController, fix postgres support ([84fb59d0](https://github.com/pixelfed/pixelfed/commit/84fb59d0))
-   Update StatusReplyPipeline, fix comment counts ([164aa577](https://github.com/pixelfed/pixelfed/commit/164aa577))
-   Update ComposeModal, add Alt Text button to caption screen ([4db48188](https://github.com/pixelfed/pixelfed/commit/4db48188))
-   Update AccountService, fix actor cache invalidation ([498b46f7](https://github.com/pixelfed/pixelfed/commit/498b46f7))
-   Update SharePipeline, fix share handling and notification generation ([83e1e203](https://github.com/pixelfed/pixelfed/commit/83e1e203))
-   Update SharePipeline, fix ReblogService and undo handling ([016c6e41](https://github.com/pixelfed/pixelfed/commit/016c6e41))
-   Update AP Helpers, fix media validation bug that would reject media with alttext/name longer than 255 chars and store remote alt text if set ([a7f58349](https://github.com/pixelfed/pixelfed/commit/a7f58349))
-   Update MentionPipeline, store non-local mentions ([17149230](https://github.com/pixelfed/pixelfed/commit/17149230))
-   Update Like model, increase rate limit to 500 likes per day ([ab7676f9](https://github.com/pixelfed/pixelfed/commit/ab7676f9))
-   Update ComposeController, fix validation issue ([80e6a5a9](https://github.com/pixelfed/pixelfed/commit/80e6a5a9))
-   Update reply view, fix visibility filtering ([d419af4b](https://github.com/pixelfed/pixelfed/commit/d419af4b))
-   Update AP helpers, ingest attachments in replies ([c504e643](https://github.com/pixelfed/pixelfed/commit/c504e643))
-   Update Media model, use cloud filesystem url if enabled instead of cdn_url to easily update S3 media urls ([e6bc57d7](https://github.com/pixelfed/pixelfed/commit/e6bc57d7))
-   Update ap helpers, fix unset media name bug ([083f506b](https://github.com/pixelfed/pixelfed/commit/083f506b))
-   Update MediaStorageService, fix improper path ([964c62da](https://github.com/pixelfed/pixelfed/commit/964c62da))
-   Update ApiV1Controller, fix account statuses and bookmark pagination ([9f66d6b6](https://github.com/pixelfed/pixelfed/commit/9f66d6b6))
-   Update SearchApiV2Service, improve account search results ([f6a588f9](https://github.com/pixelfed/pixelfed/commit/f6a588f9))
-   Update profile model, improve avatarUrl fallback ([620ee826](https://github.com/pixelfed/pixelfed/commit/620ee826))
-   Update ApiV1Controller, use cursor pagination for favourited_by and reblogged_by endpoints ([e1c7e701](https://github.com/pixelfed/pixelfed/commit/e1c7e701))
-   Update ApiV1Controller, fix favourited_by and reblogged_by follows attribute ([1a130f3e](https://github.com/pixelfed/pixelfed/commit/1a130f3e))
-   Update notifications component, improve UX with exponential retry and loading state ([937e6d07](https://github.com/pixelfed/pixelfed/commit/937e6d07))
-   Update likeModal and shareModal components, use new pagination logic and re-add Follow/Unfollow buttons ([b565ead6](https://github.com/pixelfed/pixelfed/commit/b565ead6))
-   Update profileFeed component, fix pagination ([7cf41628](https://github.com/pixelfed/pixelfed/commit/7cf41628))
-   Update ApiV1Controller, add BookmarkService logic to bookmark endpoints ([29b1af10](https://github.com/pixelfed/pixelfed/commit/29b1af10))
-   Update ApiV1Controller, filter conversations without last_status ([e8a6a8c7](https://github.com/pixelfed/pixelfed/commit/e8a6a8c7))
-   Update ApiV1Controller and BookmarkController, fix api differences and allow unbookmarking regardless of relationship ([e343061a](https://github.com/pixelfed/pixelfed/commit/e343061a))
-   Update ApiV1Controller, add pixelfed entity support to bookmarks endpoint ([94069db9](https://github.com/pixelfed/pixelfed/commit/94069db9))
-   Update PostReactions, reduce bookmark timeout to 2s from 5s ([a8094e6c](https://github.com/pixelfed/pixelfed/commit/a8094e6c))
-   Update CollectionController, fixes #3946 ([abd52f4d](https://github.com/pixelfed/pixelfed/commit/abd52f4d))
-   Update ComposeController, fix add to collection logic ([9f8957b9](https://github.com/pixelfed/pixelfed/commit/9f8957b9))
-   Update v1.1 api, add post moderation endpoint ([9bbd6dcd](https://github.com/pixelfed/pixelfed/commit/9bbd6dcd))
-   Update StatusService, on purge remove from NetworkTimelineService cache ([18940cb2](https://github.com/pixelfed/pixelfed/commit/18940cb2))
-   Update mute/block logic with admin defined limits and improved filtering to skip deleted accounts ([5b879f01](https://github.com/pixelfed/pixelfed/commit/5b879f01))
-   Update FollowPipeline, fix followers_count and following_count counters ([6153b620](https://github.com/pixelfed/pixelfed/commit/6153b620))
-   Update ApiV1Controller, fix media update. Fixes #4196 ([f3164650](https://github.com/pixelfed/pixelfed/commit/f3164650))
-   Update SearchApiV2Service, fix hashtag search. ([1992b5bc](https://github.com/pixelfed/pixelfed/commit/1992b5bc))
-   Update ApiV1Controller, allow optional mastodonMode on v2/search endpoint. ([f4a69631](https://github.com/pixelfed/pixelfed/commit/f4a69631))
-   Update ApiV1Controller, add cursor pagination and pagination link headers to account/{id}/followers and account/{id}/following endpoints with legacy support for `page=` simple pagination ([713aa5fd](https://github.com/pixelfed/pixelfed/commit/713aa5fd))
-   Update legacy Profile component to use new cursor pagination for following/follower modals ([7a1495e6](https://github.com/pixelfed/pixelfed/commit/7a1495e6))
-   Update ApiV1Controller, fix link header pagination in /api/v1/statuses/{id}/favourited_by ([adc82eca](https://github.com/pixelfed/pixelfed/commit/adc82eca))
-   Update ApiV1Controller, fix link header pagination in /api/v1/statuses/{id}/reblogged_by ([e346b675](https://github.com/pixelfed/pixelfed/commit/e346b675))
-   Update ApiV1Controller, fix following/follower entities, use masto schema by default and update components accordingly ([4716c280](https://github.com/pixelfed/pixelfed/commit/4716c280))
-   Update FollowerController, remove deprecated /i/follow endpoint ([4739d614](https://github.com/pixelfed/pixelfed/commit/4739d614))
-   Update queue config, set "after_commit" to true ([304ea956](https://github.com/pixelfed/pixelfed/commit/304ea956))
-   Update ApiV1Controller, fix home timeline bug ([a8ec8445](https://github.com/pixelfed/pixelfed/commit/a8ec8445))
-   Update ApiV1Controller, increase home timeline max limit to 100 to fix compatibility with mastoapi ([5cf9ba78](https://github.com/pixelfed/pixelfed/commit/5cf9ba78))
-   Update ApiV1Controller, preserve album order. Fixes #3708 ([deb26971](https://github.com/pixelfed/pixelfed/commit/deb26971))
-   Update site config endpoint ([f9be48d6](https://github.com/pixelfed/pixelfed/commit/f9be48d6))
-   Update Portfolios, add ActivityPub + RSS support, light mode, style customization and more ([5ad0d883](https://github.com/pixelfed/pixelfed/commit/5ad0d883))
-   Update atom feed, improve cache expiry and fix double encoding bug. Fixes #4121 ([467c9d75](https://github.com/pixelfed/pixelfed/commit/467c9d75))
-   Update email settings, add dangerzone middleware to prompt for password before you can change your email address. Fixes #4101 ([186ba7f0](https://github.com/pixelfed/pixelfed/commit/186ba7f0))
-   Update InboxPipelines, improve handling of missing signature validation headers ([419c0fb0](https://github.com/pixelfed/pixelfed/commit/419c0fb0))
-   Update admin instances dashboard ([ecfc0766](https://github.com/pixelfed/pixelfed/commit/ecfc0766))
-   Update ap helpers, fix album order bug by setting media order ([871f798c](https://github.com/pixelfed/pixelfed/commit/871f798c))
-   Update image pipeline, dispatch jobs to mmo queue and add "replace_id" param to v2/media endpoint to dispatch delayed MediaDeletePipeline job for original media id to improve media gc on supported clients ([5a67e9f9](https://github.com/pixelfed/pixelfed/commit/5a67e9f9))
-   Update admin instance management, improve filtering/sorting and add import/export support ([d5d9500d](https://github.com/pixelfed/pixelfed/commit/d5d9500d))
-   Update Post component, show state error when status account is null or missing ([e6dc6234](https://github.com/pixelfed/pixelfed/commit/e6dc6234))
-   Update private profile view, add rel=me support, hide avatar/bio when not logged in and add robots meta tag to block search engine indexing on private profiles ([ab4bb9a0](https://github.com/pixelfed/pixelfed/commit/ab4bb9a0))
-   Update settings, set maxlength on name and bio inputs. Fixes #4248 ([558700fc](https://github.com/pixelfed/pixelfed/commit/558700fc))
-   Update api routes, add post method support to /api/v1/accounts/update_credentials to properly handle binary form data (avatars). Fixes #4250 ([1ae19ea5](https://github.com/pixelfed/pixelfed/commit/1ae19ea5))
-   Update ApiV1Controller, improve timeline account hydration ([4e79c772](https://github.com/pixelfed/pixelfed/commit/4e79c772))

## [v0.11.4 (2022-10-04)](https://github.com/pixelfed/pixelfed/compare/v0.11.3...v0.11.4)

### New Features

-   Custom content warnings/spoiler text ([d4864213](https://github.com/pixelfed/pixelfed/commit/d4864213))
-   Add NetworkTimelineService cache ([1310d95c](https://github.com/pixelfed/pixelfed/commit/1310d95c))
-   Customizable Legal Notice page ([0b7d0a96](https://github.com/pixelfed/pixelfed/commit/0b7d0a96))

### Breaking

-   Replaced `predis` with `phpredis` as default redis driver due to predis being deprecated, install [phpredis](https://github.com/phpredis/phpredis/blob/develop/INSTALL.markdown) if you're still using predis.

### Updates

-   Improve S3 support by removing `ListObjects` call in media deletion ([#3438](https://github.com/pixelfed/pixelfed/pull/3438))
-   Enforce UTC in incoming activities ([18931a1f](https://github.com/pixelfed/pixelfed/commit/18931a1f))
-   Add storage flags to admin dashboard diagnostics ([#3444](https://github.com/pixelfed/pixelfed/pull/3444))
-   Hardcode UTC application timezone to prevent timezone issues ([b0d2c5e1](https://github.com/pixelfed/pixelfed/commit/b0d2c5e1))
-   Remove arbitrary metro url redirect timeout ([84209c24](https://github.com/pixelfed/pixelfed/commit/84209c24))
-   Fix JSON-LD contexts ([#3464](https://github.com/pixelfed/pixelfed/pull/3464))
-   Fix json-ld attributes, fixes #3423 ([95f902b1](https://github.com/pixelfed/pixelfed/commit/95f902b1))
-   Add trusted proxies flag to admin dashboard diagnostics ([#3450](https://github.com/pixelfed/pixelfed/pull/3450))
-   Fix json-ld attributes, fixes #3423 ([95f902b1](https://github.com/pixelfed/pixelfed/commit/95f902b1))
-   Update exp config, enforce mastoapi compatibility by default ([a160b233](https://github.com/pixelfed/pixelfed/commit/a160b233))
-   Update home timeline, redirect to /i/web unless force_old_ui is present ([5ff4730f](https://github.com/pixelfed/pixelfed/commit/5ff4730f))
-   Update adminReportController, fix mail verification request 500 bug by changing filter precedence to catch deleted users that may still be cached in AccountService ([3f322e29](https://github.com/pixelfed/pixelfed/commit/3f322e29))
-   Update AP Helpers, fix getSensitive and getScope missing parameters ([657c66c1](https://github.com/pixelfed/pixelfed/commit/657c66c1))
-   Fix mastodon api compatibility ([#3499](https://github.com/pixelfed/pixelfed/pull/3499))
-   Add ffmpeg config, disable logging by default ([108e3803](https://github.com/pixelfed/pixelfed/commit/108e3803))
-   Refactor AP profileFetch logic to fix race conditions and improve updating fields and avatars ([505261da](https://github.com/pixelfed/pixelfed/commit/505261da))
-   Update network timeline api, limit falloff to 2 days ([13a66303](https://github.com/pixelfed/pixelfed/commit/13a66303))
-   Update Inbox, store follow request activity ([c82f2085](https://github.com/pixelfed/pixelfed/commit/c82f2085))
-   Update UserFilterService, improve cache strategy by using in-memory state via UserFilterObserver for empty lists with a ttl of 90 days ([9c17def4](https://github.com/pixelfed/pixelfed/commit/9c17def4))
-   Update ApiV1Controller, add network timeline support via NetworkTimelineService ([f54fd6e9](https://github.com/pixelfed/pixelfed/commit/f54fd6e9))
-   Bump max_collection_length default to 100 from 18 ([65cf9cca](https://github.com/pixelfed/pixelfed/commit/65cf9cca))
-   Improve follow request flow, federate rejections and delete rejections from database to properly handle future follow requests from same actor ([4470981a](https://github.com/pixelfed/pixelfed/commit/4470981a))
-   Update follower counts on follow_request approval ([e97900a0](https://github.com/pixelfed/pixelfed/commit/e97900a0))
-   Update ApiV1Controller, improve local/remote logic in public timeline endpoint ([4ff179ad](https://github.com/pixelfed/pixelfed/commit/4ff179ad))
-   Update ApiV1Controller, fix network timeline ([11e99d78](https://github.com/pixelfed/pixelfed/commit/11e99d78))
-   Update ApiV1Controller, fix public timeline min/max id pagination ([a7613bae](https://github.com/pixelfed/pixelfed/commit/a7613bae))
-   Improve CollectionService cache invalidation, fixes [#3548](https://github.com/pixelfed/pixelfed/issues/3548) ([44f4a9ed](https://github.com/pixelfed/pixelfed/commit/44f4a9ed))
-   Improve inbox status deletion cache invalidation ([1eba7f81](https://github.com/pixelfed/pixelfed/commit/1eba7f81))
-   Update MediaDeletePipeline, fix async media deletion ([bb1cccbe](https://github.com/pixelfed/pixelfed/commit/bb1cccbe))
-   Fix timeline infinite scroll ([03a85460](https://github.com/pixelfed/pixelfed/commit/03a85460))
-   Fix remote avatar urls when not using cloud storage ([672f7c8c](https://github.com/pixelfed/pixelfed/commit/672f7c8c))
-   Update ResetPasswordController redirectTo path to /i/web as /home is deprecated ([8803c6de](https://github.com/pixelfed/pixelfed/commit/8803c6de))
-   Fix v1 api block/mute endpoints, refresh RelationshipService cache after relationship changes ([54a5c3be](https://github.com/pixelfed/pixelfed/commit/54a5c3be))
-   Fix NotificationService bug returning html response on /api/v1/notifications endpoint when a notification id belonging to a deleted account is rendered by checking AccountService before NotificationTransformer. ([734b30e5](https://github.com/pixelfed/pixelfed/commit/734b30e5))
-   Hydrate `favourited` and `reblogged` state on v1 context endpoint ([abb4f7e1](https://github.com/pixelfed/pixelfed/commit/abb4f7e1))
-   Improve admin dashboard by moving expensive stats to its page and loading stats and recent data async on the dashboard home page ([9d52b9c2](https://github.com/pixelfed/pixelfed/commit/9d52b9c2))
-   Update unfollow api endpoint to only decrement when appropriate, fixes #3539 ([44de1ad7](https://github.com/pixelfed/pixelfed/commit/44de1ad7))
-   Improve cache invalidation after processing VideoThumbnail to eliminate "No Preview Available" on grid feeds ([47571887](https://github.com/pixelfed/pixelfed/commit/47571887))
-   Use poster in VideoPresenter component ([a3cc90b0](https://github.com/pixelfed/pixelfed/commit/a3cc90b0))
-   Fix mastoapi notification type casting to include comment and share (mention and reblog) notifications ([eba84530](https://github.com/pixelfed/pixelfed/commit/eba84530))
-   Fix email verification requests filtering to gracefully handle deleted accounts and accounts already verified ([b57066d1](https://github.com/pixelfed/pixelfed/commit/b57066d1))
-   Add configuration to v1/instance endpoint. Fixes #3605 ([2fb18b7d](https://github.com/pixelfed/pixelfed/commit/2fb18b7d))
-   Fix remote account post counts ([149cf9dc](https://github.com/pixelfed/pixelfed/commit/149cf9dc))
-   Enforce blocks on incoming likes, shares, replies and follows on all endpoints ([1545e37c](https://github.com/pixelfed/pixelfed/commit/1545e37c))
-   Fix unlisted post web redirect and api response ([6033d837](https://github.com/pixelfed/pixelfed/commit/6033d837))
-   Remove quilljs from admin page editor, fixes #3616 ([75fbd373](https://github.com/pixelfed/pixelfed/commit/75fbd373))
-   Fix AdminStatService cache key, fixes #3612 ([d1dbed89](https://github.com/pixelfed/pixelfed/commit/d1dbed89))
-   Improve mute/block v1 api endpoints, fixes #3540 ([c3e8a0e4](https://github.com/pixelfed/pixelfed/commit/c3e8a0e4))
-   Set Last-Modified header for atom feeds, fixes #2988 ([c18dcde3](https://github.com/pixelfed/pixelfed/commit/c18dcde3))
-   Add instance post/profile embed config setting ([7734dc03](https://github.com/pixelfed/pixelfed/commit/7734dc03))
-   Remove remote posts from NetworkTimelineService when processing Tombstones ([2e4f2377](https://github.com/pixelfed/pixelfed/commit/2e4f2377))
-   Limit NotificationService to 400 items ([f6ed560e](https://github.com/pixelfed/pixelfed/commit/f6ed560e))
-   Refactor discover accounts endpoint, cache popular accounts and remove following check as most invocations are from new accounts ([016b11f3](https://github.com/pixelfed/pixelfed/commit/016b11f3))
-   Fix cache invalidation in AdminSettingsController when updating rules ([fe6787f7](https://github.com/pixelfed/pixelfed/commit/fe6787f7))
-   Update SearchApiService, improve account/webfinger results ([533f7165](https://github.com/pixelfed/pixelfed/commit/533f7165))
-   Update NotificationService, fix account attribute ([949b7bb6](https://github.com/pixelfed/pixelfed/commit/949b7bb6))
-   Update DeleteWorker, remove cache lock ([6d6a033a](https://github.com/pixelfed/pixelfed/commit/6d6a033a))
-   Fix SearchApiV2Service, improve webfinger condition ([9d31f73b](https://github.com/pixelfed/pixelfed/commit/9d31f73b))
-   Update inbox handler, upsert statuses to fix duplicate bug. Fixes #2670, #2961, #3556 ([2c20d9e3](https://github.com/pixelfed/pixelfed/commit/2c20d9e3))
-   Update AP helpers, remove cache lock from profileUpdateOrCreate method and move webfinger + key_id to unique constraints to fix sql duplicate errors ([bc2bbc14](https://github.com/pixelfed/pixelfed/commit/bc2bbc14))
-   Add migrations to fix webfinger profiles ([66aa8bf9](https://github.com/pixelfed/pixelfed/commit/66aa8bf9))
-   Update ap helpers, move remote_url constraint ([acd8f5bb](https://github.com/pixelfed/pixelfed/commit/acd8f5bb))
-   Update ApiV1Controller, fix typo in statavouriteById method ([c91a6a75](https://github.com/pixelfed/pixelfed/commit/c91a6a75))
-   Update InboxPipeline, fix peertube attributedTo parsing ([99fb80bf](https://github.com/pixelfed/pixelfed/commit/99fb80bf))
-   Update Collection components, fix addId bug #3230 ([62c05665](https://github.com/pixelfed/pixelfed/commit/62c05665))
-   Update DirectMessageController, include account entity in lookup endpoint ([9e223a6b](https://github.com/pixelfed/pixelfed/commit/9e223a6b))
-   Update ApiV1Controller update_credentials endpoint to support app response ([61d26e85](https://github.com/pixelfed/pixelfed/commit/61d26e85))
-   Update PronounService, fix json_decode null parameter ([d72cd819](https://github.com/pixelfed/pixelfed/commit/d72cd819))
-   Update ApiV1Controller, normalize profile id comparison ([374bfdae](https://github.com/pixelfed/pixelfed/commit/374bfdae))
-   Update ApiV1Controller, fix pagination header. Fixes #3354 ([4fe07e6f](https://github.com/pixelfed/pixelfed/commit/4fe07e6f))
-   Update ApiV1Controller, add optional place_id parameter to POST /api/v1/statuses endpoint ([ef0d1f84](https://github.com/pixelfed/pixelfed/commit/ef0d1f84))
-   Update SettingsController, fix double json encoding and cache settings for 7 days ([4514ab1d](https://github.com/pixelfed/pixelfed/commit/4514ab1d))
-   Update ApiV1Controller, fix mute/block entities ([364adb43](https://github.com/pixelfed/pixelfed/commit/364adb43))
-   Update atom feed, remove invalid entities ([e362ef9e](https://github.com/pixelfed/pixelfed/commit/e362ef9e))
-   Update StatusObserver, handle events after all transactions are committed ([805a014e](https://github.com/pixelfed/pixelfed/commit/805a014e))
-   Update ApiV1Controller, add collection_ids parameter to /api/v1/statuses endpoint ([7ae21fc3](https://github.com/pixelfed/pixelfed/commit/7ae21fc3))
-   Update ApiV1Controller, add comments_disabled param to /api/v1/statuses endpoint ([95b58610](https://github.com/pixelfed/pixelfed/commit/95b58610))
-   Update ap helpers to handle disabled comments ([92f56c9b](https://github.com/pixelfed/pixelfed/commit/92f56c9b))
-   Update CollectionController, limit max title and description length ([6e76cf4b](https://github.com/pixelfed/pixelfed/commit/6e76cf4b))
-   Update collection components, fix title/description padding/overflow bug and add title/description limit and input counter ([6e4272a8](https://github.com/pixelfed/pixelfed/commit/6e4272a8))
-   Update Media model, fix thumbnail cdn paths ([9888af12](https://github.com/pixelfed/pixelfed/commit/9888af12))

## [v0.11.3 (2022-05-09)](https://github.com/pixelfed/pixelfed/compare/v0.11.2...v0.11.3)

### Added

-   Custom Emoji ([#3166](https://github.com/pixelfed/pixelfed/pull/3166))
-   LDAP Authentication ([#3296](https://github.com/pixelfed/pixelfed/pull/3296))

### Metro 2.0 UI

-   Dark Mode ([cb540373](https://github.com/pixelfed/pixelfed/commit/cb540373))
-   Added Hovercards ([16ced7b4](https://github.com/pixelfed/pixelfed/commit/16ced7b4))
-   Fix word-break on statuses ([16ced7b4](https://github.com/pixelfed/pixelfed/commit/16ced7b4))
-   Add pronouns to hovercards ([33f863e8](https://github.com/pixelfed/pixelfed/commit/33f863e8))
-   Improved onboarding ([042c5b6c](https://github.com/pixelfed/pixelfed/commit/042c5b6c))
-   Add Hide Counts & Stats setting ([01af7d80](https://github.com/pixelfed/pixelfed/commit/01af7d80))
-   Fix nsfw videos not displaying sensitive warning ([01af7d80](https://github.com/pixelfed/pixelfed/commit/01af7d80))
-   Easy Avatar updates - update from timelines with drag-n-drop support ([f37d3798](https://github.com/pixelfed/pixelfed/commit/f37d3798))
-   Comment hovercards ([f37d3798](https://github.com/pixelfed/pixelfed/commit/f37d3798))
-   Mod tools button on posts for admins ([f37d3798](https://github.com/pixelfed/pixelfed/commit/f37d3798))
-   Improved Media Previews - disable to restore original preview aspect ratios ([c55eeac8](https://github.com/pixelfed/pixelfed/commit/c55eeac8))
-   Moved media license to post header ([390f3ab0](https://github.com/pixelfed/pixelfed/commit/390f3ab0))
-   Mobile app drawer menu ([7b4318fd](https://github.com/pixelfed/pixelfed/commit/7b4318fd))
-   Add Preferred Profile Layout UI setting ([a816ea66](https://github.com/pixelfed/pixelfed/commit/a816ea66))
-   Fix profile masonry layout on mobile. Fixes #3203 ([fdf90f2d](https://github.com/pixelfed/pixelfed/commit/fdf90f2d))
-   Add search bar to mobile breakpoints and adjust avatar size when necessary ([77b9b6bd](https://github.com/pixelfed/pixelfed/commit/77b9b6bd))
-   Improved profile layout on mobile breakpoints ([77b9b6bd](https://github.com/pixelfed/pixelfed/commit/77b9b6bd))
-   New Discover layout with My Hashtags, My Memories, Account Insights, Find Friends and Server Timelines ([0b680099](https://github.com/pixelfed/pixelfed/commit/0b680099))
-   Fix private profile feed not loading for owner ([e950b3b2](https://github.com/pixelfed/pixelfed/commit/e950b3b2))
-   Add "Shared by" link to posts that opens a list of accounts that reblogged the post ([e4b4bfc1](https://github.com/pixelfed/pixelfed/commit/e4b4bfc1))
-   Notification filters ([537af6df](https://github.com/pixelfed/pixelfed/commit/537af6df))
-   Full screen preview on photo albums ([ac40fde1](https://github.com/pixelfed/pixelfed/commit/ac40fde1))

### Updated

-   Updated MediaStorageService, fix remote avatar bug. ([1c20d696](https://github.com/pixelfed/pixelfed/commit/1c20d696))
-   Updated WebfingerService. Fixes #3167. ([aff74566](https://github.com/pixelfed/pixelfed/commit/aff74566))
-   Updated ComposeModal, add max file size and allowed mime types. Fixes #3162. ([879281cc](https://github.com/pixelfed/pixelfed/commit/879281cc))
-   Updated profile embeds, fix NaN bug and improve performance. ([3bd211d7](https://github.com/pixelfed/pixelfed/commit/3bd211d7))
-   Updated ApiV1Controller, improve follow count cache invalidation. ([4b6effb9](https://github.com/pixelfed/pixelfed/commit/4b6effb9))
-   Updated web routes, fix atom feeds for account usernames containing a dot. ([8c54ab57](https://github.com/pixelfed/pixelfed/commit/8c54ab57))
-   Updated atom feeds, include media alt text. Fixes #3184. ([5d9b6863](https://github.com/pixelfed/pixelfed/commit/5d9b6863))
-   Updated ApiV1Controller, add custom_emoji endpoint. ([16e72518](https://github.com/pixelfed/pixelfed/commit/16e72518))
-   Updated InternalApiController, redirect remote post and profiles to Metro 2.0. ([3c35158e](https://github.com/pixelfed/pixelfed/commit/3c35158e))
-   Updated BaseApiController, improve favourites endpoint. ([f063cb01](https://github.com/pixelfed/pixelfed/commit/f063cb01))
-   Updated ApiV1Controller, invalidate status reply cache on new reply. ([3c261bbf](https://github.com/pixelfed/pixelfed/commit/3c261bbf))
-   Updated PublicApiController, add bookmark state to timeline endpoints. ([c0b1e042](https://github.com/pixelfed/pixelfed/commit/c0b1e042))
-   Updated ApiV1Controller, fix private status replies returning 404. ([73226360](https://github.com/pixelfed/pixelfed/commit/73226360))
-   Updated StatusService, use BookmarkService for bookmarked state. ([a7d71551](https://github.com/pixelfed/pixelfed/commit/a7d71551))
-   Updated Apis, added ReblogService to improve reblogged state for api entities ([6cfd6be5](https://github.com/pixelfed/pixelfed/commit/6cfd6be5))
-   Updated InstanceActorController, fix content-type header. ([21792246](https://github.com/pixelfed/pixelfed/commit/21792246))
-   Updated Exception handler to report validation message bag errors. ([74905ba1](https://github.com/pixelfed/pixelfed/commit/74905ba1))
-   Updated ApiV1Controller, add validation messages to update_credentials endpoint. ([cd785601](https://github.com/pixelfed/pixelfed/commit/cd785601))
-   Updated ComposeController, improve location search results ordering by use frequency. ([29c4bd25](https://github.com/pixelfed/pixelfed/commit/29c4bd25))
-   Updated AvatarController, fix mimetype bug. ([7fa9d4dc](https://github.com/pixelfed/pixelfed/commit/7fa9d4dc))
-   Updated PostComponent.vue, filter out non-text comments. ([a7346f21](https://github.com/pixelfed/pixelfed/commit/a7346f21))
-   Updated Profile.vue component, fix v-once bug. ([4d003d00](https://github.com/pixelfed/pixelfed/commit/4d003d00))
-   Updated filesystems config, set S3 visibility to public by default. Fixes #2913. ([49a53c27](https://github.com/pixelfed/pixelfed/commit/49a53c27))
-   Updated CommentPipeline, improve parent reply_count calculation. ([ccc94802](https://github.com/pixelfed/pixelfed/commit/ccc94802))
-   Updated StatusTagsPipeline, process federated hashtags and mentions ([a84b1736](https://github.com/pixelfed/pixelfed/commit/a84b1736))
-   Updated Inbox, fix undo announce. ([cf286fb0](https://github.com/pixelfed/pixelfed/commit/cf286fb0))
-   Updated ApiV1Controller, improve favourites endpoint. ([151dc17c](https://github.com/pixelfed/pixelfed/commit/151dc17c))
-   Updated StatusController, set missing reblog/share type. ([548a12a4](https://github.com/pixelfed/pixelfed/commit/548a12a4))
-   Updated index view, remove shortcut from favicon meta tag. Fixes #3196. ([6e2cb3cd](https://github.com/pixelfed/pixelfed/commit/6e2cb3cd))
-   Updated CollectionController, fix broken unauthenticated access. Fixes #3242. ([bd249f0c](https://github.com/pixelfed/pixelfed/commit/bd249f0c))
-   Updated ComposeController, add collection support to compose endpoint. ([ec2cfaf5](https://github.com/pixelfed/pixelfed/commit/ec2cfaf5))
-   Updated instance config, match default oauth settings in AuthServiceProvider. ([52f25ff1](https://github.com/pixelfed/pixelfed/commit/52f25ff1))
-   Updated ComposeModal.vue, fix redirect after posting. Fixes #3254. ([5db64e94](https://github.com/pixelfed/pixelfed/commit/5db64e94))
-   Updated StatusController, redirect status view for authed users to Metro 2.0 UI. ([71dff472](https://github.com/pixelfed/pixelfed/commit/71dff472))
-   Updated ProfileController, redirect profile view for authed users to Metro 2.0 UI. ([7f8129a7](https://github.com/pixelfed/pixelfed/commit/7f8129a7))
-   Updated SpaController, fix variable typo. Fixes #3268. ([8d1af1d6](https://github.com/pixelfed/pixelfed/commit/8d1af1d6))
-   Updated ComposeModal, fix post redirect on old UI. ([160e32a5](https://github.com/pixelfed/pixelfed/commit/160e32a5))
-   Updated LikeService, improve caching logic and add profile id to likedBy method to fix #3271. ([6af842eb](https://github.com/pixelfed/pixelfed/commit/6af842eb))
-   Updated admin diagnostics, add more configuration data to help diagnose potential issues. ([eab96fc3](https://github.com/pixelfed/pixelfed/commit/eab96fc3))
-   Updated ConfigCacheService, fix discover features. ([ad48521a](https://github.com/pixelfed/pixelfed/commit/ad48521a))
-   Updated MediaTransformer, fix type case bug. Fixes #3281. ([c1669253](https://github.com/pixelfed/pixelfed/commit/c1669253))
-   Updated SpaController, redirect web ui hashtags to legacy page for unauthenticated users. ([a44b812b](https://github.com/pixelfed/pixelfed/commit/a44b812b))
-   Updated ApiV1Controller, fixes #3288. ([3e670774](https://github.com/pixelfed/pixelfed/commit/3e670774))
-   Updated AP Helpers, fixes #3287. ([b78bff72](https://github.com/pixelfed/pixelfed/commit/b78bff72))
-   Updated AP Helpers, fixes #3290. ([53975206](https://github.com/pixelfed/pixelfed/commit/53975206))
-   Updated AccountController, refresh relationship after handling follow request. ([fe768785](https://github.com/pixelfed/pixelfed/commit/fe768785))
-   Updated CollectionController, fixes #3289. ([c7e1e473](https://github.com/pixelfed/pixelfed/commit/c7e1e473))
-   Updated SpaController, handle web redirects. ([b6c6c85b](https://github.com/pixelfed/pixelfed/commit/b6c6c85b))
-   Updated presenter components, remove video poster attribute. ([4d612dfa](https://github.com/pixelfed/pixelfed/commit/4d612dfa))
-   Improved reblog api performance ([3ef6c9fe](https://github.com/pixelfed/pixelfed/commit/3ef6c9fe))
-   Updated ApiV1Controller, fix unlisted replies. ([c13bca76](https://github.com/pixelfed/pixelfed/commit/c13bca76))
-   Updated SearchApiV2Service, filter banned instances. ([281443d7](https://github.com/pixelfed/pixelfed/commit/281443d7))
-   Updated DiscoverController, fix favourited state on memories. ([b91747b4](https://github.com/pixelfed/pixelfed/commit/b91747b4))
-   Updated InboxPipeline, fixes #3306. ([20710f4d](https://github.com/pixelfed/pixelfed/commit/20710f4d))
-   Updated inbox workers, fixes #3304. ([cd4f73be](https://github.com/pixelfed/pixelfed/commit/cd4f73be))
-   Updated Inbox, fixes #3305. ([14231632](https://github.com/pixelfed/pixelfed/commit/14231632))
-   Updated Inbox, fixes #3313. ([1c3e72c0](https://github.com/pixelfed/pixelfed/commit/1c3e72c0))
-   Updated Inbox, fixes #3314. ([dfcd2e6d](https://github.com/pixelfed/pixelfed/commit/dfcd2e6d))
-   Updated search service, fix banned instance edge case. ([74018e9c](https://github.com/pixelfed/pixelfed/commit/74018e9c))
-   Updated inbox, fixes #3315. ([c3c3ce18](https://github.com/pixelfed/pixelfed/commit/c3c3ce18))
-   Updated ApiV1Controller, fix instance endpoint. ([c383f100](https://github.com/pixelfed/pixelfed/commit/c383f100))
-   Updated ApiV1Controller, marshal json without escaped slashes. ([89303fa4](https://github.com/pixelfed/pixelfed/commit/89303fa4))
-   Updated ApiV1Controller, fix statusCreate validator. ([b6b15b0c](https://github.com/pixelfed/pixelfed/commit/b6b15b0c))
-   Updated ApiV1Controller, fix notification entities. ([afe903c3](https://github.com/pixelfed/pixelfed/commit/afe903c3))
-   Updated FederationController, fix webfinger endpoint. ([a0e15d89](https://github.com/pixelfed/pixelfed/commit/a0e15d89))
-   Updated ApiV1Controller, fix context entities. ([b1ab41e0](https://github.com/pixelfed/pixelfed/commit/b1ab41e0))
-   Updated ApiV1Controller, fix timeline default limit. ([a87f8301](https://github.com/pixelfed/pixelfed/commit/a87f8301))
-   Updated ApiV1Controller, fix search v2 entities. ([9dac861e](https://github.com/pixelfed/pixelfed/commit/9dac861e))
-   Updated ApiV1Controller, fix apps endpoint. ([50baae52](https://github.com/pixelfed/pixelfed/commit/50baae52))
-   Updated ApiV1Controller, add apps/verify_credentials endpoint. ([c4d38c20](https://github.com/pixelfed/pixelfed/commit/c4d38c20))
-   Updated ApiV1Controller, increase max limion timelines. ([df22f2e4](https://github.com/pixelfed/pixelfed/commit/df22f2e4))
-   Updated ApiV1Controller, add preferences endpoint. ([c3e56b87](https://github.com/pixelfed/pixelfed/commit/c3e56b87))
-   Updated ApiV1Controller, fix tag timeline limits and remove has(media) constraint. ([8c65d60b](https://github.com/pixelfed/pixelfed/commit/8c65d60b))
-   Updated ApiV1Controller, add trends endpoint. ([d40a8453](https://github.com/pixelfed/pixelfed/commit/d40a8453))
-   Updated ApiV1Controller, add announcements endpoint. ([fbe07c51](https://github.com/pixelfed/pixelfed/commit/fbe07c51))
-   Updated ApiV1Controller, add markers endpoint. ([93a9769e](https://github.com/pixelfed/pixelfed/commit/93a9769e))
-   Updated ApiV1Controller, increase limits from 80 to 100. ([15eccd44](https://github.com/pixelfed/pixelfed/commit/15eccd44))
-   Updated ApiV1Controller, fix accountStatusesById endpoint. ([db7b1af3](https://github.com/pixelfed/pixelfed/commit/db7b1af3))
-   Updated ApiV1Controller, update statusCreate entity. ([a84ab6ea](https://github.com/pixelfed/pixelfed/commit/a84ab6ea))
-   Updated ApiV1Controller, remove pinned attribute to match MastoAPI Status entity. ([6057de30](https://github.com/pixelfed/pixelfed/commit/6057de30))
-   Updated controller signatures, fix mysql 8 support. ([72e3d891](https://github.com/pixelfed/pixelfed/commit/72e3d891))
-   Updated ApiV1Controller, remove no-preview image from media urls. ([37dfb101](https://github.com/pixelfed/pixelfed/commit/37dfb101))
-   Updated DeleteAccountPipeline, fix perf issues. ([a9edd93f](https://github.com/pixelfed/pixelfed/commit/a9edd93f))
-   Updated DeleteAccountPipeline, improve coverage. ([4870cc3b](https://github.com/pixelfed/pixelfed/commit/4870cc3b))
-   Updated media model, use original photo url for non-existent thumbnails. ([9b04b9d8](https://github.com/pixelfed/pixelfed/commit/9b04b9d8))
-   Updated PlaceController, require authentication. ([e7783af6](https://github.com/pixelfed/pixelfed/commit/e7783af6))
-   Updated PublicApiController, disable legacy public access to local timeline. ([6ba7d433](https://github.com/pixelfed/pixelfed/commit/6ba7d433))
-   Updated DiscoverController, cache public tag feed and only include local posts for unauthenticated users. ([0541aed5](https://github.com/pixelfed/pixelfed/commit/0541aed5))
-   Updated DiscoverController, improve tag feed performance. ([d8ff40eb](https://github.com/pixelfed/pixelfed/commit/d8ff40eb))
-   Updated ApiV1Controller, fix timeline pagination. ([a5cdc28b](https://github.com/pixelfed/pixelfed/commit/a5cdc28b))
-   Updated ApiV1Controller, add missing pagination header. ([5649873a](https://github.com/pixelfed/pixelfed/commit/5649873a))
-   Updated CollectionController, limit unpublished collections to owner. ([a0061eb5](https://github.com/pixelfed/pixelfed/commit/a0061eb5))
-   Updated AP Inbox, fixes #3332. ([f8931dc7](https://github.com/pixelfed/pixelfed/commit/f8931dc7))
-   Updated AdminReportController, add account delete button. ([563817a9](https://github.com/pixelfed/pixelfed/commit/563817a9))
-   Updated ApiV1Controller, added /api/v2/media endpoint, fixes #3405. ([f07cc14c](https://github.com/pixelfed/pixelfed/commit/f07cc14c))
-   Updated AP fanout, added Content-Type and User-Agent for activity delivery. ([@noellabo](https://github.com/noellabo)) ([209c125](https://github.com/pixelfed/pixelfed/commit/209c125))
-   Updated DirectMessageController to support new Metro 2.0 UI DMs. ([a4659fd2](https://github.com/pixelfed/pixelfed/commit/a4659fd2))
-   Updated Like model, bump max likes per day from 100 to 200. ([71ba5fed](https://github.com/pixelfed/pixelfed/commit/71ba5fed))
-   Updated HashtagService, use sorted set for followed tags. ([153eb6ba](https://github.com/pixelfed/pixelfed/commit/153eb6ba))
-   Updated Discover component, fixed post side effects (fixes #3409). ([fe5a92b2](https://github.com/pixelfed/pixelfed/commit/fe5a92b2))

## [v0.11.2 (2022-01-09)](https://github.com/pixelfed/pixelfed/compare/v0.11.1...v0.11.2)

### Breaking

-   Dropped support for PHP 7.3 [#3041](https://github.com/pixelfed/pixelfed/pull/3041)

### Metro 2.0 UI

-   Added UI Settings modal and fixed height media previews setting ([f2467e71](https://github.com/pixelfed/pixelfed/commit/f2467e71))
-   Set max-width of 1440px for larger screens ([af68872a](https://github.com/pixelfed/pixelfed/commit/af68872a))
-   Add link to sidebar profile card ([85964510](https://github.com/pixelfed/pixelfed/commit/85964510))
-   Improved search bar, now resolves (and imports) remote accounts and posts, including webfinger addresses ([c8a667f2](https://github.com/pixelfed/pixelfed/commit/c8a667f2))
-   Added user facing changelog at `/i/web/whats-new` ([e61dc66a](https://github.com/pixelfed/pixelfed/commit/e61dc66a))

### Configuration

-   Enable network timeline by default ([b95aec12](https://github.com/pixelfed/pixelfed/commit/b95aec12))

### Postgres Compatibility

-   Fix Story recent endpoint on postgres instances ([ddf41dc3](https://github.com/pixelfed/pixelfed/commit/ddf41dc3))
-   Fix Direct Message conversations endpoint on postgres instances ([fcabc9be](https://github.com/pixelfed/pixelfed/commit/fcabc9be))

### Added

-   Manual email verification requests. ([bc659387](https://github.com/pixelfed/pixelfed/commit/bc659387))
-   Added StatusMentionService, fixes #3026. ([e5387d67](https://github.com/pixelfed/pixelfed/commit/e5387d67))
-   Cloud Backups, a command to store backups on S3 or compatible filesystems. [#3037](https://github.com/pixelfed/pixelfed/pull/3037) ([3515a98e](https://github.com/pixelfed/pixelfed/commit/3515a98e))
-   Web UI Localizations + Crowdin integration. ([f7d9b40b](https://github.com/pixelfed/pixelfed/commit/f7d9b40b)) ([7ff120c9](https://github.com/pixelfed/pixelfed/commit/7ff120c9))
-   Store remote avatars locally if S3 not enabled. ([b4bd0400](https://github.com/pixelfed/pixelfed/commit/b4bd0400))

### Updated

-   Updated NotificationService, fix 500 bug. ([4a609dc3](https://github.com/pixelfed/pixelfed/commit/4a609dc3))
-   Updated HttpSignatures, update instance actor headers. Fixes #2935. ([a900de21](https://github.com/pixelfed/pixelfed/commit/a900de21))
-   Updated NoteTransformer, fix tag array. ([7b3e672d](https://github.com/pixelfed/pixelfed/commit/7b3e672d))
-   Updated video presenters, add playsinline attribute to video tags. ([0299aa5b](https://github.com/pixelfed/pixelfed/commit/0299aa5b))
-   Updated RemotePost, RemoteProfile components, add fallback avatars. ([754151dc](https://github.com/pixelfed/pixelfed/commit/754151dc))
-   Updated FederationController, move well-known to api middleware and cache webfinger lookups. ([4505d1f0](https://github.com/pixelfed/pixelfed/commit/4505d1f0))
-   Updated InstanceActorController, improve json seralization by not escaping slashes. ([0a8eb81b](https://github.com/pixelfed/pixelfed/commit/0a8eb81b))
-   Refactor following & relationship logic. Replace FollowerObserver with FollowerService and added RelationshipService to cache results. Removed NotificationTransformer includes and replaced with cached services to improve performance and reduce database queries. ([80d9b939](https://github.com/pixelfed/pixelfed/commit/80d9b939))
-   Updated PublicApiController, use AccountService in accountStatuses method. ([bef959f4](https://github.com/pixelfed/pixelfed/commit/bef959f4))
-   Updated auth config, add throttle limit to password resets. ([2609c86a](https://github.com/pixelfed/pixelfed/commit/2609c86a))
-   Updated StatusCard component, add relationship state button. ([0436b124](https://github.com/pixelfed/pixelfed/commit/0436b124))
-   Updated Timeline component, cascade relationship state change. ([f4bd5672](https://github.com/pixelfed/pixelfed/commit/f4bd5672))
-   Updated Activity component, only show context button for actionable activities. ([7886fd59](https://github.com/pixelfed/pixelfed/commit/7886fd59))
-   Updated Autospam service, use silent classification for better user experience. ([f0d4c172](https://github.com/pixelfed/pixelfed/commit/f0d4c172))
-   Updated Profile component, improve error messages when block/mute limit reached. ([02237845](https://github.com/pixelfed/pixelfed/commit/02237845))
-   Updated Activity component, fix missing types. ([5167c68d](https://github.com/pixelfed/pixelfed/commit/5167c68d))
-   Updated Timeline component, apply block/mute filters client side for local and network timelines. ([be194b8a](https://github.com/pixelfed/pixelfed/commit/be194b8a))
-   Updated public timeline api, use cached sorted set and client side block/mute filtering. ([37abcf38](https://github.com/pixelfed/pixelfed/commit/37abcf38))
-   Updated public timeline api, add experimental cache. ([192553ff](https://github.com/pixelfed/pixelfed/commit/192553ff))
-   Updated dark mode styles, fix black box on stories. Closes #2982. ([3169f68e](https://github.com/pixelfed/pixelfed/commit/3169f68e))
-   Updated verify_credentials api endpoint to improve performance. ([7df3540b](https://github.com/pixelfed/pixelfed/commit/7df3540b))
-   Updated Localization util, filter out .DS_Store. ([0107e8fd](https://github.com/pixelfed/pixelfed/commit/0107e8fd))
-   Updated PublicApiController, fix private account statuses api. Closes #2995. ([aa2dd26c](https://github.com/pixelfed/pixelfed/commit/aa2dd26c))
-   Updated Status model, use AccountService to generate urls instead of loading profile relation. ([2ae527c0](https://github.com/pixelfed/pixelfed/commit/2ae527c0))
-   Updated Autospam service, add mark all as read and mark all as not spam options and filter active, spam and not spam reports. ([ae8c7517](https://github.com/pixelfed/pixelfed/commit/ae8c7517))
-   Updated UserInviteController, fixes #3017. ([b8e9056e](https://github.com/pixelfed/pixelfed/commit/b8e9056e))
-   Updated AccountService, add dynamic user settings methods. ([2aa73c1f](https://github.com/pixelfed/pixelfed/commit/2aa73c1f))
-   Updated MediaStorageService, improve header parsing. ([9d9e9ce7](https://github.com/pixelfed/pixelfed/commit/9d9e9ce7))
-   Updated SearchApiV2Service, improve performance and include hashtag post counts when applicable ([fbaed93e](https://github.com/pixelfed/pixelfed/commit/fbaed93e))
-   Updated AccountTransformer, add note_text and location fields. ([98f76abb](https://github.com/pixelfed/pixelfed/commit/98f76abb))
-   Updated UserSetting model, cast compose_settings and other as json. ([03420278](https://github.com/pixelfed/pixelfed/commit/03420278))
-   Updated ApiV1Controller, improve settings and add discoverPosts endpoint. ([079804e6](https://github.com/pixelfed/pixelfed/commit/079804e6))
-   Updated LikePipeline jobs, fix likes_count calculation. ([fe64e187](https://github.com/pixelfed/pixelfed/commit/fe64e187))
-   Updated InternalApiController, prevent moderation actions against admin accounts. ([945a7e49](https://github.com/pixelfed/pixelfed/commit/945a7e49))
-   Updated CommentPipeline, move reply_count calculation to comment pipeline job and improve count calculation. ([b6b0837f](https://github.com/pixelfed/pixelfed/commit/b6b0837f))
-   Updated ApiV1Controller, improve statusesById perf and dispatch CommentPipeline job when applicable. ([466286af](https://github.com/pixelfed/pixelfed/commit/466286af))
-   Updated MediaService, return empty array if cant find status. ([c2910e5d](https://github.com/pixelfed/pixelfed/commit/c2910e5d))
-   Updated StatusService, improve cache invalidation. ([83b48b56](https://github.com/pixelfed/pixelfed/commit/83b48b56))
-   Updated Hashtag component, fix spinner. ([fefbc44a](https://github.com/pixelfed/pixelfed/commit/fefbc44a))
-   Updated NotificationCard, update api endpoint and add group notification types. ([e09a14d8](https://github.com/pixelfed/pixelfed/commit/e09a14d8))
-   Updated ContextMenu component, fix account url paths. ([01ca1edd](https://github.com/pixelfed/pixelfed/commit/01ca1edd))
-   Updated PollCard component, add showBorder prop. ([0c8fffbd](https://github.com/pixelfed/pixelfed/commit/0c8fffbd))
-   Updated PhotoPresenter component, add lightbox toggle. ([0cc1365f](https://github.com/pixelfed/pixelfed/commit/0cc1365f))
-   Updated console kernel, add db session garbage collector that runs twice daily. ([03b0a62a](https://github.com/pixelfed/pixelfed/commit/03b0a62a))
-   Updated ComposeController, refactor compose_settings. ([edc2958b](https://github.com/pixelfed/pixelfed/commit/edc2958b))
-   Updated StatusEntityLexer, prevent boosts and replies from being added to PublicTimelineService. ([32707372](https://github.com/pixelfed/pixelfed/commit/32707372))
-   Updated SpaController, persist web language changes. ([7bc684e5](https://github.com/pixelfed/pixelfed/commit/7bc684e5))
-   Updated LoginController, bump decayMinutes from 1 to 60. ([6bf92bed](https://github.com/pixelfed/pixelfed/commit/6bf92bed))
-   Updated SPA, rewrite autolink urls to SPA when applicable. ([0837b410](https://github.com/pixelfed/pixelfed/commit/0837b410))
-   Updated site config, increase ttl and enable SPA by default. ([469d49d8](https://github.com/pixelfed/pixelfed/commit/469d49d8))
-   Updated Webfinger, fixes #3050. ([ff7ee3bd](https://github.com/pixelfed/pixelfed/commit/ff7ee3bd))
-   Updated status api, autolink caption before returning response. ([b00a453b](https://github.com/pixelfed/pixelfed/commit/b00a453b))
-   Updated Timeline, add new ui promo in timelines that can be hidden using localstorage. ([e13959ae](https://github.com/pixelfed/pixelfed/commit/e13959ae))
-   Updated FederationController, increase webfinger cache ttl from 12 hours to 14 days. ([745c3580](https://github.com/pixelfed/pixelfed/commit/745c3580))
-   Updated DiscoverController, add yearly option and increase limit from 15 to 30 posts. ([10b6058c](https://github.com/pixelfed/pixelfed/commit/10b6058c))
-   Updated RemoteAvatarFetch job, fixed bug preventing new avatars from being stored. ([92bc2845](https://github.com/pixelfed/pixelfed/commit/92bc2845))
-   Updated AccountService, fix json casting. ([e5f8f344](https://github.com/pixelfed/pixelfed/commit/e5f8f344))
-   Updated ApiV1Controller, fix illegal operator bug by setting default min_id. ([415826f2](https://github.com/pixelfed/pixelfed/commit/415826f2))
-   Updated StatusService, add getMastodon method for mastoapi compatibility. ([36a129fe](https://github.com/pixelfed/pixelfed/commit/36a129fe))
-   Updated PublicApiController, fix accountStatuses pagination operator. ([85fc9dd0](https://github.com/pixelfed/pixelfed/commit/85fc9dd0))
-   Updated PublicApiController, enforce only_media on accountStatuses method. Fixes #3105. ([861a2d36](https://github.com/pixelfed/pixelfed/commit/861a2d36))
-   Updated ApiV1Controller, add mastoapi strict mode. ([46485426](https://github.com/pixelfed/pixelfed/commit/46485426))
-   Updated AccountController, refresh RelationshipService on mute/block. ([6f1b0245](https://github.com/pixelfed/pixelfed/commit/6f1b0245))
-   Updated ApiV1Controller, fix version on instance endpoint. ([a6261221](https://github.com/pixelfed/pixelfed/commit/a6261221))
-   Updated components, fix api endpoints. Fixes #3138. ([e724633e](https://github.com/pixelfed/pixelfed/commit/e724633e))
-   Updated ApiV1Controller, fix public timeline endpoint. ([80c7def3](https://github.com/pixelfed/pixelfed/commit/80c7def3))
-   Updated PublicApiController, fix public timeline endpoint. ([dcb7ba9c](https://github.com/pixelfed/pixelfed/commit/dcb7ba9c))
-   Updated ApiV1Controller, fix home timeline entities. ([6fc0dcb3](https://github.com/pixelfed/pixelfed/commit/6fc0dcb3))
-   Updated ApiV1Controller, fix favourites endpoints ([d6d99385](https://github.com/pixelfed/pixelfed/commit/d6d99385))
-   Updated ApiV1Controller, fix reblogs endpoints ([de42d84c](https://github.com/pixelfed/pixelfed/commit/de42d84c))
-   Updated SearchApiV2Service, resolve remote queries. ([c8a667f2](https://github.com/pixelfed/pixelfed/commit/c8a667f2))

## [v0.11.1 (2021-09-07)](https://github.com/pixelfed/pixelfed/compare/v0.11.0...v0.11.1)

### Added

-   WebP Support ([069a0e4a](https://github.com/pixelfed/pixelfed/commit/069a0e4a))
-   Auto Following support for admins ([68aa2540](https://github.com/pixelfed/pixelfed/commit/68aa2540))
-   Mark as spammer mod tool, unlists and applies content warning to existing and future post ([6d956a86](https://github.com/pixelfed/pixelfed/commit/6d956a86))
-   Diagnostics for error page and admin dashboard ([64725ecc](https://github.com/pixelfed/pixelfed/commit/64725ecc))
-   Default media licenses and media license sync ([ea0fc90c](https://github.com/pixelfed/pixelfed/commit/ea0fc90c))
-   Customize media description/alt-text length limit ([072d55d1](https://github.com/pixelfed/pixelfed/commit/072d55d1))
-   Federate Media Licenses ([14a1367a](https://github.com/pixelfed/pixelfed/commit/14a1367a))
-   Archive Posts ([e9ef0c88](https://github.com/pixelfed/pixelfed/commit/e9ef0c88))
-   Polls ([77092200](https://github.com/pixelfed/pixelfed/commit/77092200))
-   Federated Stories (#2895)

### Updated

-   Updated PrettyNumber, fix deprecated warning. ([20ec870b](https://github.com/pixelfed/pixelfed/commit/20ec870b))
-   Updated landing page, use config_cache. ([54920294](https://github.com/pixelfed/pixelfed/commit/54920294))
-   Updated Timeline, implement suggested post opt out. ([66750d34](https://github.com/pixelfed/pixelfed/commit/66750d34))
-   Updated Notification component, add at (@) symbol for remote profiles and local urls for remote posts and profile. ([aafd6a21](https://github.com/pixelfed/pixelfed/commit/aafd6a21))
-   Updated Activity component, add at (@) symbol for remote profiles and local urls for remote posts and profile. ([a2211815](https://github.com/pixelfed/pixelfed/commit/a2211815))
-   Updated Profile, add linkified bio, joined date, follows you label and improved website handling. ([8ee10436](https://github.com/pixelfed/pixelfed/commit/8ee10436))
-   Updated routes, add legacy webfinger profile redirect. ([93c7af74](https://github.com/pixelfed/pixelfed/commit/93c7af74))
-   Updated StoryController, fix expiration time bug. ([39e57f95](https://github.com/pixelfed/pixelfed/commit/39e57f95))
-   Updated Profile component, fix remote urls. ([6e56dbed](https://github.com/pixelfed/pixelfed/commit/6e56dbed))
-   Updated verify email screen, add contact admin link. ([f37952d6](https://github.com/pixelfed/pixelfed/commit/f37952d6))
-   Updated RemoteProfile component, implement pagination. ([02b04a4b](https://github.com/pixelfed/pixelfed/commit/02b04a4b))
-   Updated AP Helpers, generate notification for remote replies. ([8edd8294](https://github.com/pixelfed/pixelfed/commit/8edd8294))
-   Updated like api, store status_profile_id and is_comment. ([c8c6b983](https://github.com/pixelfed/pixelfed/commit/c8c6b983))
-   Updated Remote Post + Profile hashtag to redirect to local urls. ([1fa08644](https://github.com/pixelfed/pixelfed/commit/1fa08644))
-   Updated Inbox, delete notifications on tombstone. ([ef63124d](https://github.com/pixelfed/pixelfed/commit/ef63124d))
-   Updated NotificationCard, fix missing status bug. ([a3a86d46](https://github.com/pixelfed/pixelfed/commit/a3a86d46))
-   Updated Activity component, fix comment bug. ([9a2db8eb](https://github.com/pixelfed/pixelfed/commit/9a2db8eb))
-   Updated Inbox, fix tombstone bug. ([929ff5eb](https://github.com/pixelfed/pixelfed/commit/929ff5eb))
-   Updated LikeService, skip self likes. ([3741c76d](https://github.com/pixelfed/pixelfed/commit/3741c76d))
-   Updated StatusController, improve share api perf (11s to 72ms). ([d48ebb82](https://github.com/pixelfed/pixelfed/commit/d48ebb82))
-   Updated ApiController, fix nulls in hashtag endpoint. ([f1208de0](https://github.com/pixelfed/pixelfed/commit/f1208de0))
-   Updated SharePipeline, add Undo->Announce support. ([c8e40e0f](https://github.com/pixelfed/pixelfed/commit/c8e40e0f))
-   Updated NetworkTimeline, fix remote comment urls. ([308acc91](https://github.com/pixelfed/pixelfed/commit/308acc91))
-   Updated Timeline component, abstracted reusable partials. ([858f3f9e](https://github.com/pixelfed/pixelfed/commit/858f3f9e))
-   Updated Timeline, fix suggested posts. ([3ba5c88c](https://github.com/pixelfed/pixelfed/commit/3ba5c88c))
-   Updated Timeline, disable new post update checker and hide reaction bar on network timeline. ([1e3d3a69](https://github.com/pixelfed/pixelfed/commit/1e3d3a69))
-   Updated PublicApiController, improve network timeline perf. ([e5f683fd](https://github.com/pixelfed/pixelfed/commit/e5f683fd))
-   Updated Network Timeline, use existing Timeline component. ([0deaafc0](https://github.com/pixelfed/pixelfed/commit/0deaafc0))
-   Updated PostComponent, show like count to owner using MomentUI. ([e9c46bab](https://github.com/pixelfed/pixelfed/commit/e9c46bab))
-   Updated ContextMenu, add missing statusUrl method. ([3cffdb11](https://github.com/pixelfed/pixelfed/commit/3cffdb11))
-   Updated PublicApiController, add LikeService to Network timeline. ([82895591](https://github.com/pixelfed/pixelfed/commit/82895591))
-   Updated moderator api, expire cached status in StatusService. ([f215ee26](https://github.com/pixelfed/pixelfed/commit/f215ee26))
-   Updated StatusHashtagService, fix null status bug. ([51a277e1](https://github.com/pixelfed/pixelfed/commit/51a277e1))
-   Updated NotificationService, use zrevrangebyscore for api. ([d43e6d8d](https://github.com/pixelfed/pixelfed/commit/d43e6d8d))
-   Updated ApiV1Controller, use PublicTimelineService. ([f67c67bc](https://github.com/pixelfed/pixelfed/commit/f67c67bc))
-   Updated ApiV1Controller, use ProfileService for verify_credentials. ([352aa573](https://github.com/pixelfed/pixelfed/commit/352aa573))
-   Updated RemotePost.vue, fix content warning button. ([7647e724](https://github.com/pixelfed/pixelfed/commit/7647e724))
-   Updated AdminMediaController, improve perf and use simple pagination. ([f2686cac](https://github.com/pixelfed/pixelfed/commit/f2686cac))
-   Updated PostComponent, fix MomentUI like counter. ([42c6121a](https://github.com/pixelfed/pixelfed/commit/42c6121a))
-   Updated status views, remove like counts from status embed. ([1a2e41b1](https://github.com/pixelfed/pixelfed/commit/1a2e41b1))
-   Updated Profile, fix unauthenticated private profiles. ([9017f7c4](https://github.com/pixelfed/pixelfed/commit/9017f7c4))
-   Updated PublicApiController, impr home timeline perf. ([4fe42e5b](https://github.com/pixelfed/pixelfed/commit/4fe42e5b))
-   Updated Timeline.vue, fix comment button. ([b6b5ce7c](https://github.com/pixelfed/pixelfed/commit/b6b5ce7c))
-   Updated StatusEntityLexer, only add specific status types to PublicTimelineService. ([1fdcbe5b](https://github.com/pixelfed/pixelfed/commit/1fdcbe5b))
-   Updated ActivityPub helpers, fix comment threading in statusFetch() method ([26b9c140](https://github.com/pixelfed/pixelfed/commit/26b9c140))
-   Updated NotificationCard, fix typo in mention, share and comments. Fixes #2848. ([b37bb426](https://github.com/pixelfed/pixelfed/commit/b37bb426))
-   Updated StatusCard.vue, add togglecw events to other presenters. ([9607243f](https://github.com/pixelfed/pixelfed/commit/9607243f))
-   Updated presenters, fix content warning layout. ([fc56acb8](https://github.com/pixelfed/pixelfed/commit/fc56acb8))
-   Updated reply blade view, fix missing avatar and media images. ([5fb33772](https://github.com/pixelfed/pixelfed/commit/5fb33772))
-   Updated components, add fallback default avatar. ([726553f5](https://github.com/pixelfed/pixelfed/commit/726553f5))
-   Updated job queue, separate deletes into their own queue. ([7f421392](https://github.com/pixelfed/pixelfed/commit/7f421392))
-   Updated DiscoverController, use UserFilterService on trendingApi. ([135474ae](https://github.com/pixelfed/pixelfed/commit/135474ae))
-   Updated PublicApiController, use UserFilterService in public timeline endpoint. ([ca6e491c](https://github.com/pixelfed/pixelfed/commit/ca6e491c))
-   Updated ContextMenu, add View Profile link. ([8544bcbd](https://github.com/pixelfed/pixelfed/commit/8544bcbd))
-   Updated presenters, improve content warnings. ([86422c81](https://github.com/pixelfed/pixelfed/commit/86422c81))
-   Updated Timeline.vue, increase pagination limit from 3 to 12 and add empty feed placeholder. ([916e8f71](https://github.com/pixelfed/pixelfed/commit/916e8f71))
-   Updated Timeline.vue, improve followed hashtags. ([728f10d7](https://github.com/pixelfed/pixelfed/commit/728f10d7))
-   Updated PostComponent, use profileUrl method for comments. ([7ed65fc9](https://github.com/pixelfed/pixelfed/commit/7ed65fc9))
-   Updated Timeline, fix empty timeline card. ([11eb6acd](https://github.com/pixelfed/pixelfed/commit/11eb6acd))
-   Updated ap helpers, set text type when appropriate. ([9f4f983f](https://github.com/pixelfed/pixelfed/commit/9f4f983f))
-   Updated StatusCard, add text support. ([ed14ee48](https://github.com/pixelfed/pixelfed/commit/ed14ee48))
-   Updated PublicApiController, filter out text replies on home timeline. ([86219b57](https://github.com/pixelfed/pixelfed/commit/86219b57))
-   Updated RemotePost.vue, improve text only post UI. ([b0257be2](https://github.com/pixelfed/pixelfed/commit/b0257be2))
-   Updated Timeline, make text-only posts opt-in by default. ([0153ed6d](https://github.com/pixelfed/pixelfed/commit/0153ed6d))
-   Updated LikeController, add UndoLikePipeline and federate Undo Like activities. ([8ac8fcad](https://github.com/pixelfed/pixelfed/commit/8ac8fcad))
-   Updated Settings, add default license and enforced media descriptions. ([67e3f604](https://github.com/pixelfed/pixelfed/commit/67e3f604))
-   Updated Compose Apis, make media descriptions/alt text length limit configurable. Default length: 1000. ([072d55d1](https://github.com/pixelfed/pixelfed/commit/072d55d1))
-   Updated ApiV1Controller, add default license support. ([2a791f19](https://github.com/pixelfed/pixelfed/commit/2a791f19))
-   Updated StatusTransformers, remove includes and use cached services. ([09d5198c](https://github.com/pixelfed/pixelfed/commit/09d5198c))
-   Updated RemotePost component, update likes reaction bar. ([1060dd23](https://github.com/pixelfed/pixelfed/commit/1060dd23))
-   Updated FollowPipeline, fix cache invalidation bug. ([c1f14f89](https://github.com/pixelfed/pixelfed/commit/c1f14f89))
-   Updated PublicApiController, improve accountStatuses api perf. ([bce8edd9](https://github.com/pixelfed/pixelfed/commit/bce8edd9))
-   Updated ApiControllers, use NotificationService. ([f9516ac3](https://github.com/pixelfed/pixelfed/commit/f9516ac3))
-   Updated Notification components, fix old notifications with missing attributes. ([b6e226ae](https://github.com/pixelfed/pixelfed/commit/b6e226ae))
-   Updated LikeController, improve query perf. ([f3d6023e](https://github.com/pixelfed/pixelfed/commit/f3d6023e))
-   Updated License util, add nameToId method. ([f6131ed7](https://github.com/pixelfed/pixelfed/commit/f6131ed7))
-   Updated RemoteProfile, add warning about potentially out of date information. ([7274574c](https://github.com/pixelfed/pixelfed/commit/7274574c))
-   Updated NotifcationCard.vue component, add refresh button for cold notification cache. ([0e178a33](https://github.com/pixelfed/pixelfed/commit/0e178a33))
-   Updated RemoteProfile component, add follower modals. ([c4146a30](https://github.com/pixelfed/pixelfed/commit/c4146a30))
-   Updated FollowerService, cache audience. ([22257cc2](https://github.com/pixelfed/pixelfed/commit/22257cc2))
-   Updated StatusService, add non-public option and improve cache invalidation. ([15c4fdd9](https://github.com/pixelfed/pixelfed/commit/15c4fdd9))
-   Updated ContactAdmin mail, set New Support Message subject. ([bc3add05](https://github.com/pixelfed/pixelfed/commit/bc3add05))
-   Updated StatusTransformer, prioritize scope over deprecated visibility attribute. ([6e45021f](https://github.com/pixelfed/pixelfed/commit/6e45021f))
-   Updated StatusService, invalidate profile embed cache on deletion. ([acaf630d](https://github.com/pixelfed/pixelfed/commit/acaf630d))
-   Updated status.reply view, fix archived post leakage. ([4fb3d1fa](https://github.com/pixelfed/pixelfed/commit/4fb3d1fa))
-   Updated PostComponents, re-add time to timestamp. ([c5281dcd](https://github.com/pixelfed/pixelfed/commit/c5281dcd))
-   Updated follow intent, fix follower count leak. ([03199e2f](https://github.com/pixelfed/pixelfed/commit/03199e2f))
-   Updated Status model, add poll relation and allow up to 2 urls to autolink. ([2593cdee](https://github.com/pixelfed/pixelfed/commit/2593cdee))
-   Updated snowflake id generation to improve randomness. ([e5aea490](https://github.com/pixelfed/pixelfed/commit/e5aea490))
-   Updated Timeline, remove recent posts. ([7641b731](https://github.com/pixelfed/pixelfed/commit/7641b731))
-   Updated InstanceCrawlPipeline, remove unused variable. ([e73cf531](https://github.com/pixelfed/pixelfed/commit/e73cf531))
-   Updated StoryComposeController, fix expiry bug. ([7dee8f58](https://github.com/pixelfed/pixelfed/commit/7dee8f58))
-   Updated Profile, fix following count bug. ([ee9f0795](https://github.com/pixelfed/pixelfed/commit/ee9f0795))
-   Updated DirectMessageController, fix autocomplete bug. ([0f00be4d](https://github.com/pixelfed/pixelfed/commit/0f00be4d))
-   Updated StoryService, fix division by zero bug. ([6ae1ba0a](https://github.com/pixelfed/pixelfed/commit/6ae1ba0a))
-   Updated ApiV1Controller, fix empty public timeline bug. ([0584f9ee](https://github.com/pixelfed/pixelfed/commit/0584f9ee))

## [v0.11.0 (2021-06-01)](https://github.com/pixelfed/pixelfed/compare/v0.10.10...v0.11.0)

### Added

-   Autocomplete Support (hashtags + mentions) ([de514f7d](https://github.com/pixelfed/pixelfed/commit/de514f7d))
-   Creative Commons Licenses ([552e950](https://github.com/pixelfed/pixelfed/commit/552e950))
-   Network Timeline ([af7face4](https://github.com/pixelfed/pixelfed/commit/af7face4))
-   Admin config settings ([f2066b74](https://github.com/pixelfed/pixelfed/commit/f2066b74))
-   Profile pronouns ([fabb57a9](https://github.com/pixelfed/pixelfed/commit/fabb57a9))
-   Hashtag timeline api support ([241ae036](https://github.com/pixelfed/pixelfed/commit/241ae036))
-   New admin dashboard layout ([eb7d5a4e](https://github.com/pixelfed/pixelfed/commit/eb7d5a4e))
-   Fresh about page layout ([92dc7af6](https://github.com/pixelfed/pixelfed/commit/92dc7af6))
-   Instance Rules ([a4efbb75](https://github.com/pixelfed/pixelfed/commit/a4efbb75))
-   New Home Timeline ([56215be7](https://github.com/pixelfed/pixelfed/commit/56215be7))

### Updated

-   Updated AdminController, fix variable name in updateSpam method. ([6edaf940](https://github.com/pixelfed/pixelfed/commit/6edaf940))
-   Updated RemoteAvatarFetch, only dispatch jobs if cloud storage is enabled. ([4f40f6f5](https://github.com/pixelfed/pixelfed/commit/4f40f6f5))
-   Updated StatusService, add ttl of 7 days. ([6e44ae0b](https://github.com/pixelfed/pixelfed/commit/6e44ae0b))
-   Updated StatusHashtagService, use StatusService for statuses. ([0355b567](https://github.com/pixelfed/pixelfed/commit/0355b567))
-   Updated StatusHashtagService, remove deprecated methods. ([aa4c718d](https://github.com/pixelfed/pixelfed/commit/aa4c718d))
-   Updated ApiV1Controller, add StatusService del calls to update likes_count, reblogs_count and reply_count. ([05b9445c](https://github.com/pixelfed/pixelfed/commit/05b9445c))
-   Updated Like, Status and Comment controllers to add StatusService del() method to update counts. ([eab4370c](https://github.com/pixelfed/pixelfed/commit/eab4370c))
-   Updated ComposeController, use placeholder image for video media. Fixes #2595. ([789ed4b4](https://github.com/pixelfed/pixelfed/commit/789ed4b4))
-   Updated DiscoverController, change api schema. ([2eea0409](https://github.com/pixelfed/pixelfed/commit/2eea0409))
-   Updated StatusDelete pipeline, call StatusService::del() to remove status from cache. ([3f772ff8](https://github.com/pixelfed/pixelfed/commit/3f772ff8))
-   Updated StatusHashtagTransformer, add blurhash attribute. ([899bbeba](https://github.com/pixelfed/pixelfed/commit/899bbeba))
-   Updated status square previews, add blurhash and improved content warnings. ([39e389dd](https://github.com/pixelfed/pixelfed/commit/39e389dd))
-   Updated Blurhash util, add default hash for invalid media. ([38a37c15](https://github.com/pixelfed/pixelfed/commit/38a37c15))
-   Updated VideoThumbnail job, generate blurhash for videos. ([896452c7](https://github.com/pixelfed/pixelfed/commit/896452c7))
-   Updated MediaTransformers, add default blurhash attribute. ([3f14a4c4](https://github.com/pixelfed/pixelfed/commit/3f14a4c4))
-   Updated Timeline.vue, fix hashtag status previews. ([7768e844](https://github.com/pixelfed/pixelfed/commit/7768e844))
-   Updated AP helpers, fix statusFetch 404s. ([3419379a](https://github.com/pixelfed/pixelfed/commit/3419379a))
-   Updated InternalApiController, update discoverPosts method to improve performance. ([9862a855](https://github.com/pixelfed/pixelfed/commit/9862a855))
-   Updated DiscoverComponent, add blurhash and like/comment counts. ([a8ebdd2e](https://github.com/pixelfed/pixelfed/commit/a8ebdd2e))
-   Updated DiscoverComponent, add spinner loaders and remove deprecated sections. ([34869247](https://github.com/pixelfed/pixelfed/commit/34869247))
-   Updated AccountController, add mutes and blocks endpoint to pixelfed api. ([1fb7e2b2](https://github.com/pixelfed/pixelfed/commit/1fb7e2b2))
-   Updated AccountService, cache object and observe changes. ([b299da93](https://github.com/pixelfed/pixelfed/commit/b299da93))
-   Updated webfinger util, fail on invalid webfinger url. Fixes ([#2613](https://github.com/pixelfed/pixelfed/issues/2613)) ([2d11317c](https://github.com/pixelfed/pixelfed/commit/2d11317c))
-   Updated MediaStorageService, dispatch deletes to MediaDeletePipeline. ([37dbb3de](https://github.com/pixelfed/pixelfed/commit/37dbb3de))
-   Updated ComposeController, use MediaStorageService for media deletes. ([ab5469ff](https://github.com/pixelfed/pixelfed/commit/ab5469ff))
-   Updated StatusDeletePipeline, use MediaStorageService for media deletes. ([9fd90e17](https://github.com/pixelfed/pixelfed/commit/9fd90e17))
-   Updated Discover, allow public discover access. ([1404ac6e](https://github.com/pixelfed/pixelfed/commit/1404ac6e))
-   Updated pixelfed config, add media_fast_process setting. ([6bee5072](https://github.com/pixelfed/pixelfed/commit/6bee5072))
-   Updated ComposeController, add mediaProcessingCheck method. ([33b625f5](https://github.com/pixelfed/pixelfed/commit/33b625f5))
-   Updated ComposeModal, add processing step disabled by default. ([e6e76e80](https://github.com/pixelfed/pixelfed/commit/e6e76e80))
-   Updated DiscoverComponent, allow unauthenticated if enabled. ([a1059a6e](https://github.com/pixelfed/pixelfed/commit/a1059a6e))
-   Updated components, improve content warnings. ([a9e98965](https://github.com/pixelfed/pixelfed/commit/a9e98965))
-   Updated ComposeModal, prevent tagging empty users. Fixes #2633. ([ceae664c](https://github.com/pixelfed/pixelfed/commit/ceae664c))
-   Updated ComposeModal, show filter warning for unsupported browsers. ([12ce7602](https://github.com/pixelfed/pixelfed/commit/12ce7602))
-   Updated Hashtag component, fix null infinite loading bug. Fixes #2637. ([55136518](https://github.com/pixelfed/pixelfed/commit/55136518))
-   Updated filesystems config, add backup driver to store backups on other filesystems. ([ae90eef9](https://github.com/pixelfed/pixelfed/commit/ae90eef9))
-   Updated Embeds. Fix Profile + Status embeds, remove following count and improve cache invalidation and hidden follower counts. ([5ac9d0e8](https://github.com/pixelfed/pixelfed/commit/5ac9d0e8))
-   Updated FederationController, return 404 for invalid webfinger addresses. Fixes ([#2647](https://github.com/pixelfed/pixelfed/issues/2647)). ([deb6f115](https://github.com/pixelfed/pixelfed/commit/deb6f115))
-   Updated InboxPipeline, fail earlier for invalid public keys. Fixes ([#2648](https://github.com/pixelfed/pixelfed/issues/2648)). ([d1c5e9b8](https://github.com/pixelfed/pixelfed/commit/d1c5e9b8))
-   Updated Status model, refactor liked and shared methods to fix cache invalidation bug. ([f05c3b66](https://github.com/pixelfed/pixelfed/commit/f05c3b66))
-   Updated Timeline component, add inline reports modal. ([e64b4bd3](https://github.com/pixelfed/pixelfed/commit/e64b4bd3))
-   Updated federation pipeline, add locks. ([ddc76887](https://github.com/pixelfed/pixelfed/commit/ddc76887))
-   Updated MediaStorageService, improve head checks to fix failed jobs. ([1769cdfd](https://github.com/pixelfed/pixelfed/commit/1769cdfd))
-   Updated user admin, remove expensive db query and add search. ([8feeadbf](https://github.com/pixelfed/pixelfed/commit/8feeadbf))
-   Updated Compose apis, prevent private accounts from posting public or unlisted scopes. ([f53bfa6f](https://github.com/pixelfed/pixelfed/commit/f53bfa6f))
-   Updated font icons, use font-display:swap. ([77d4353a](https://github.com/pixelfed/pixelfed/commit/77d4353a))
-   Updated ComposeModal, limit visibility scope for private accounts. ([001d4105](https://github.com/pixelfed/pixelfed/commit/001d4105))
-   Updated ComposeController, add autocomplete apis for hashtags and mentions. ([f0e48a09](https://github.com/pixelfed/pixelfed/commit/f0e48a09))
-   Updated StatusController, invalidate profile embed cache on status delete. ([9c8a87c3](https://github.com/pixelfed/pixelfed/commit/9c8a87c3))
-   Updated moderation api, invalidate profile embed. ([b2501bfc](https://github.com/pixelfed/pixelfed/commit/b2501bfc))
-   Updated Nodeinfo util, use last_active_at for monthly active user count. ([d200c12c](https://github.com/pixelfed/pixelfed/commit/d200c12c))
-   Updated PhotoPresenter, add width and height to images. ([3f8202e2](https://github.com/pixelfed/pixelfed/commit/3f8202e2))
-   Updated Compose Apis, refactor rate limits. ([42375b3d](https://github.com/pixelfed/pixelfed/commit/42375b3d))
-   Updated PublicApiController, show unlisted comments. ([e1c6297e](https://github.com/pixelfed/pixelfed/commit/e1c6297e))
-   Updated ApiV1Controller, add missing variable. ([886ea617](https://github.com/pixelfed/pixelfed/commit/886ea617))
-   Updated PublicApiController, limit network pagination to 3 months. ([10119bbb](https://github.com/pixelfed/pixelfed/commit/10119bbb))
-   Updated admin instance page, add search and improve performance. ([f5829373](https://github.com/pixelfed/pixelfed/commit/f5829373))
-   Updated AdminInstanceController, invalidate banned domain cache when updated. ([35393edf](https://github.com/pixelfed/pixelfed/commit/35393edf))
-   Updated AP Helpers, use instance filtering. ([66b4f8c7](https://github.com/pixelfed/pixelfed/commit/66b4f8c7))
-   Updated ApiV1Controller, add missing instance api attributes. ([64b86546](https://github.com/pixelfed/pixelfed/commit/64b86546))
-   Updated story garbage collection, handle non active stories and new ephemeral story media directory. ([c43f8bcc](https://github.com/pixelfed/pixelfed/commit/c43f8bcc))
-   Updated Stories, add crop and duration settings to composer. ([c8edca69](https://github.com/pixelfed/pixelfed/commit/c8edca69))
-   Updated instance endpoint, add custom description. ([668e936e](https://github.com/pixelfed/pixelfed/commit/668e936e))
-   Updated StoryCompose component, improve full screen preview. ([39a76103](https://github.com/pixelfed/pixelfed/commit/39a76103))
-   Updated Helpers, fix broken tests. ([22dddaa0](https://github.com/pixelfed/pixelfed/commit/22dddaa0))
-   Updated StoryController, fix cache crop bug. ([c2f8faae](https://github.com/pixelfed/pixelfed/commit/c2f8faae))
-   Updated StoryController, optimize photo size by resizing to 9:16 aspect. ([e66ed9a2](https://github.com/pixelfed/pixelfed/commit/e66ed9a2))
-   Updated StoryCompose crop logic. ([2ead622c](https://github.com/pixelfed/pixelfed/commit/2ead622c))
-   Updated StatusController, allow license edits without 24 hour limit. ([c799a01a](https://github.com/pixelfed/pixelfed/commit/c799a01a))
-   Updated Settings, remove reports page. ([9cf962ff](https://github.com/pixelfed/pixelfed/commit/9cf962ff))
-   Updated ProfileService, use account transformer. ([391b1287](https://github.com/pixelfed/pixelfed/commit/391b1287))
-   Updated LikeController, hide like counts. ([ea687240](https://github.com/pixelfed/pixelfed/commit/ea687240))
-   Updated StatusTransformers, add liked_by attribute. ([372bacb0](https://github.com/pixelfed/pixelfed/commit/372bacb0))
-   Updated PostComponent, change like logic. ([0a35f5d6](https://github.com/pixelfed/pixelfed/commit/0a35f5d6))
-   Updated Timeline component, change like logic. ([7bcbf96b](https://github.com/pixelfed/pixelfed/commit/7bcbf96b))
-   Updated LikeService, fix likedBy method. ([a5e64da6](https://github.com/pixelfed/pixelfed/commit/a5e64da6))
-   Updated PublicApiController, increase public timeline to 6 months from 3. ([8a736432](https://github.com/pixelfed/pixelfed/commit/8a736432))
-   Updated LikeService, show like count to status owner. ([4408e2ef](https://github.com/pixelfed/pixelfed/commit/4408e2ef))
-   Updated admin settings, add rules. ([a4efbb75](https://github.com/pixelfed/pixelfed/commit/a4efbb75))
-   Updated LikeService, fix authentication bug. ([c9abd70e](https://github.com/pixelfed/pixelfed/commit/c9abd70e))
-   Updated StatusTransformer, fix missing tags attribute. ([dac326e9](https://github.com/pixelfed/pixelfed/commit/dac326e9))
-   Updated ComposeController, bail on empty attachments. ([061b145b](https://github.com/pixelfed/pixelfed/commit/061b145b))
-   Updated landing and about page. ([92dc7af6](https://github.com/pixelfed/pixelfed/commit/92dc7af6))
-   Updated AdminStatsService, fix postgres bug. ([af719135](https://github.com/pixelfed/pixelfed/commit/af719135))
-   Updated api, remove auth requirement for hashtag timeline. ([c8e43c60](https://github.com/pixelfed/pixelfed/commit/c8e43c60))
-   Updated NotificationCard component, fix default value. ([78ad4e77](https://github.com/pixelfed/pixelfed/commit/78ad4e77))
-   Updated Timeline component, show counts and make sidebar footer lighter. ([0788bffa](https://github.com/pixelfed/pixelfed/commit/0788bffa))
-   Updated AuthServiceProvider, increase default token + refresh token lifetime. ([178ed63d](https://github.com/pixelfed/pixelfed/commit/178ed63d))
-   Updated liked by, fix remote username urls. ([f767d99a](https://github.com/pixelfed/pixelfed/commit/f767d99a))
-   Updated StatusController, add cache invalidation for timeline cursor. ([f3bf2fd4](https://github.com/pixelfed/pixelfed/commit/f3bf2fd4))
-   Updated PublicApiController, add recent feed support to home timeline. ([1e230e80](https://github.com/pixelfed/pixelfed/commit/1e230e80))
-   Updated Inbox, fix reply/comment bug by moving attachment validation to Note with attachments. ([28df9f7e](https://github.com/pixelfed/pixelfed/commit/28df9f7e))
-   Updated PrettyNumber, add decimal option. ([84520fe1](https://github.com/pixelfed/pixelfed/commit/84520fe1))
-   Updated app config, change default descriptions. ([7d24560d](https://github.com/pixelfed/pixelfed/commit/7d24560d))
-   Updated NotificationCard, fix loading bug. ([69567e19](https://github.com/pixelfed/pixelfed/commit/69567e19))
-   Updated DirectMessageController, disable exception logging for invalid urls. Fixes ([#2752](https://github.com/pixelfed/pixelfed/issues/2752)). ([2d0a253e](https://github.com/pixelfed/pixelfed/commit/2d0a253e))

## [v0.10.10 (2021-01-28)](https://github.com/pixelfed/pixelfed/compare/v0.10.9...v0.10.10)

### Added

-   Direct Messages ([d63569c](https://github.com/pixelfed/pixelfed/commit/d63569c))
-   ActivityPubFetchService for signed GET requests ([8763bfc5](https://github.com/pixelfed/pixelfed/commit/8763bfc5)) ([3ee1215a](https://github.com/pixelfed/pixelfed/commit/3ee1215a))
-   Custom content warnings for remote posts ([6afc61a4](https://github.com/pixelfed/pixelfed/commit/6afc61a4))
-   Thai translations ([74cd536](https://github.com/pixelfed/pixelfed/commit/74cd536))
-   Added Bookmarks to v1 api ([99cb48c5](https://github.com/pixelfed/pixelfed/commit/99cb48c5))
-   Added New Post notification to Timeline ([a0e7c4d5](https://github.com/pixelfed/pixelfed/commit/a0e7c4d5))
-   Add Instagram Import ([e2a6bdd0](https://github.com/pixelfed/pixelfed/commit/e2a6bdd0))
-   Add notification preview to NotificationCard ([28445e27](https://github.com/pixelfed/pixelfed/commit/28445e27))
-   Add MediaPathService ([c54b29c5](https://github.com/pixelfed/pixelfed/commit/c54b29c5))
-   Add Media Tags ([711fc020](https://github.com/pixelfed/pixelfed/commit/711fc020))
-   Add MediaTagService ([524c6d45](https://github.com/pixelfed/pixelfed/commit/524c6d45))
-   Add MediaBlocklist feature ([ba1f7e7e](https://github.com/pixelfed/pixelfed/commit/ba1f7e7e))
-   New Discover Layout, add trending hashtags, places and posts ([c251d41b](https://github.com/pixelfed/pixelfed/commit/c251d41b))
-   Add Password change email notification ([de1cca4f](https://github.com/pixelfed/pixelfed/commit/de1cca4f))
-   Add shared inbox ([4733ca9f](https://github.com/pixelfed/pixelfed/commit/4733ca9f))
-   Add federated photo filters ([0a5a0e86](https://github.com/pixelfed/pixelfed/commit/0a5a0e86))
-   Add AccountInterstitial model and controller ([8766ccfe](https://github.com/pixelfed/pixelfed/commit/8766ccfe))
-   Add Blurhash encoder ([fad102bf](https://github.com/pixelfed/pixelfed/commit/fad102bf))
-   Add autospam feature ([b892bcf0](https://github.com/pixelfed/pixelfed/commit/b892bcf0))
-   Add hCaptcha ([082c1ccb](https://github.com/pixelfed/pixelfed/commit/082c1ccb))
-   Add StatusView model to store views for discover algorithm ([7a68ee94](https://github.com/pixelfed/pixelfed/commit/7a68ee94))
-   Add Year in Review feature (mysql only) ([f32072a3](https://github.com/pixelfed/pixelfed/commit/f32072a3))

### Updated

-   Updated PostComponent, fix remote urls ([42716ccc](https://github.com/pixelfed/pixelfed/commit/42716ccc))
-   Updated PostComponent, fix missing like button on comments ([132c1dce](https://github.com/pixelfed/pixelfed/commit/132c1dce))
-   Updated PostComponent.vue, fix load more comments button ([847599ad](https://github.com/pixelfed/pixelfed/commit/847599ad))
-   Updated 2FA Checkpoint, add username + logout button and numeric inputmode ([26affb11](https://github.com/pixelfed/pixelfed/commit/26affb11))
-   Updated RemoteProfile, fix missing content warnings ([e487527a](https://github.com/pixelfed/pixelfed/commit/e487527a))
-   Updated RemotePost component, fix missing like button on comments ([7ef90565](https://github.com/pixelfed/pixelfed/commit/7ef90565))
-   Updated PublicApiControllers, fix block/mutes filtering on public timeline ([08383dd4](https://github.com/pixelfed/pixelfed/commit/08383dd4))
-   Updated FixUsernames command, fixes remote username search ([0f943f67](https://github.com/pixelfed/pixelfed/commit/0f943f67))
-   Updated Timeline component, fix mod tools ([b1d5eb05](https://github.com/pixelfed/pixelfed/commit/b1d5eb05))
-   Updated Profile.vue component, fix pagination bug ([46767810](https://github.com/pixelfed/pixelfed/commit/46767810))
-   Updated purify config, fix microformats support ([877023fb](https://github.com/pixelfed/pixelfed/commit/877023fb))
-   Updated LikeController, fix likes_count bug ([996866cb](https://github.com/pixelfed/pixelfed/commit/996866cb))
-   Updated AccountController, added followRequestJson method ([483548e2](https://github.com/pixelfed/pixelfed/commit/483548e2))
-   Updated UserInvite model, added sender relation ([591a1929](https://github.com/pixelfed/pixelfed/commit/591a1929))
-   Updated migrations, added UIKit ([fcab5010](https://github.com/pixelfed/pixelfed/commit/fcab5010))
-   Updated AccountTransformer, added last_fetched_at attribute ([38b0233e](https://github.com/pixelfed/pixelfed/commit/38b0233e))
-   Updated StoryItemTransformer, increase story length to 5 seconds ([924e424c](https://github.com/pixelfed/pixelfed/commit/924e424c))
-   Updated StatusController, fix reblog_count bug ([1dc65e93](https://github.com/pixelfed/pixelfed/commit/1dc65e93))
-   Updated NotificationCard.vue component, add follow requests at top of card, remove card-header ([5e48ffca](https://github.com/pixelfed/pixelfed/commit/5e48ffca))
-   Updated RemoteProfile.vue component, add warning for empty profiles and last_fetched_at ([66f44a9d](https://github.com/pixelfed/pixelfed/commit/66f44a9d))
-   Updated ApiV1Controller, enforce public timeline setting ([285bd485](https://github.com/pixelfed/pixelfed/commit/285bd485))
-   Updated SearchController, fix self search bug and rank local matches higher ([f67fada2](https://github.com/pixelfed/pixelfed/commit/f67fada2))
-   Updated FederationController, improve webfinger logic, fixes ([#2180](https://github.com/pixelfed/pixelfed/issues/2180)) ([302ff874](https://github.com/pixelfed/pixelfed/commit/302ff874))
-   Updated ApiV1Controller, fix broken auth check on public timelines. Fixes ([#2168](https://github.com/pixelfed/pixelfed/issues/2168)) ([aa49afc7](https://github.com/pixelfed/pixelfed/commit/aa49afc7))
-   Updated SearchApiV2Service, fix offset bug ([#2116](https://github.com/pixelfed/pixelfed/issues/2116)) ([a0c0c84d](https://github.com/pixelfed/pixelfed/commit/a0c0c84d))
-   Updated api routes, fixes ([#2114](https://github.com/pixelfed/pixelfed/issues/2114)) ([50bbeddd](https://github.com/pixelfed/pixelfed/commit/50bbeddd))
-   Updated SiteController, add legacy profile/webfinger redirect ([cfaa248c](https://github.com/pixelfed/pixelfed/commit/cfaa248c))
-   Updated checkpoint view, fix recovery code bug ([3385583f](https://github.com/pixelfed/pixelfed/commit/3385583f))
-   Updated Inbox, move expensive HTTP Signature validation to job queue ([f2ae45e5a](https://github.com/pixelfed/pixelfed/commit/f2ae45e5a))
-   Updated MomentUI, fix bugs and improve UI ([90b89cb8](https://github.com/pixelfed/pixelfed/commit/90b89cb8))
-   Updated PostComponent, improve embed model. Fixes ([#2189](https://github.com/pixelfed/pixelfed/issues/2189)) ([b12e504e](https://github.com/pixelfed/pixelfed/commit/b12e504e))
-   Updated PostComponent, hide edit button after 24 hours. Fixes ([#2188](https://github.com/pixelfed/pixelfed/issues/2188)) ([a1fee6a2](https://github.com/pixelfed/pixelfed/commit/a1fee6a2))
-   Updated AP Inbox, add follow notifications ([b8819fbb](https://github.com/pixelfed/pixelfed/commit/b8819fbb))
-   Updated Api Transformers, fixes ([#2234](https://github.com/pixelfed/pixelfed/issues/2234)) ([63007891](https://github.com/pixelfed/pixelfed/commit/63007891))
-   Updated ApiV1Controller, fix instance endpoint ([#2233](https://github.com/pixelfed/pixelfed/issues/2233)) ([b7ee9981](https://github.com/pixelfed/pixelfed/commit/b7ee9981))
-   Updated AP Inbox, remove trailing comma ([5c443548](https://github.com/pixelfed/pixelfed/commit/5c443548))
-   Updated AP Helpers, update bio + name ([4bee8397](https://github.com/pixelfed/pixelfed/commit/4bee8397))
-   Updated Profile component, add bookmark loader ([c8d5edc9](https://github.com/pixelfed/pixelfed/commit/c8d5edc9))
-   Updated PostComponent, add recent posts ([b289f2f6](https://github.com/pixelfed/pixelfed/commit/b289f2f6))
-   Updated ApiV1Controller, add status ancestor and descendant context ([a0bde855](https://github.com/pixelfed/pixelfed/commit/a0bde855))
-   Updated NotificationCard, improve popover image scaling ([0153e596](https://github.com/pixelfed/pixelfed/commit/0153e596))
-   Updated StoryController, fix deprecated getClientSize() use ([725fc6c6](https://github.com/pixelfed/pixelfed/commit/725fc6c6))
-   Updated ComposeModal, fix rotate icon direction. Fixes ([#2241](https://github.com/pixelfed/pixelfed/issues/2241)) ([e8a14640](https://github.com/pixelfed/pixelfed/commit/e8a14640))
-   Updated Timeline.vue, add profile links to grid mode ([fa40f51b](https://github.com/pixelfed/pixelfed/commit/fa40f51b))
-   Updated Timeline.vue, hide like counts on grid mode. Fixes ([#2293](https://github.com/pixelfed/pixelfed/issues/2293)) ([cc18159f](https://github.com/pixelfed/pixelfed/commit/cc18159f))
-   Updated Timeline.vue, make grid mode photos clickable. Fixes ([#2292](https://github.com/pixelfed/pixelfed/issues/2292)) ([6db68184](https://github.com/pixelfed/pixelfed/commit/6db68184))
-   Updated ComposeModal.vue, use vue tooltips. Fixes ([#2142](https://github.com/pixelfed/pixelfed/issues/2142)) ([2b753123](https://github.com/pixelfed/pixelfed/commit/2b753123))
-   Updated AccountController, prevent blocking admins. ([2c440b48](https://github.com/pixelfed/pixelfed/commit/2c440b48))
-   Updated Api controllers to use MediaPathService. ([58864212](https://github.com/pixelfed/pixelfed/commit/58864212))
-   Updated notification components, add modlog and tagged notification types ([51862b8b](https://github.com/pixelfed/pixelfed/commit/51862b8b))
-   Updated StoryController, allow video stories. ([b3b220b9](https://github.com/pixelfed/pixelfed/commit/b3b220b9))
-   Updated InternalApiController, add media tags. ([ee93f459](https://github.com/pixelfed/pixelfed/commit/ee93f459))
-   Updated ComposeModal.vue, add media tagging. ([421ea022](https://github.com/pixelfed/pixelfed/commit/421ea022))
-   Updated NotificationTransformer, add modlog and tagged types. ([49dab6fb](https://github.com/pixelfed/pixelfed/commit/49dab6fb))
-   Updated comments, fix remote reply bug. ([f330616](https://github.com/pixelfed/pixelfed/commit/f330616))
-   Updated PostComponent, add tagged people to mobile layout. ([7a2c2e78](https://github.com/pixelfed/pixelfed/commit/7a2c2e78))
-   Updated Tag People, allow untagging yourself. ([c9452639](https://github.com/pixelfed/pixelfed/commit/c9452639))
-   Updated ComposeModal.vue, add 451 http code warning. ([b213dcda](https://github.com/pixelfed/pixelfed/commit/b213dcda))
-   Updated Profile.vue, add empty follower modal placeholder. ([b542a3c5](https://github.com/pixelfed/pixelfed/commit/b542a3c5))
-   Updated private profiles, add context menu to mute, block or report. ([487c4ffc](https://github.com/pixelfed/pixelfed/commit/487c4ffc))
-   Updated webfinger util, fix bug preventing username with dots. ([c2d194af](https://github.com/pixelfed/pixelfed/commit/c2d194af))
-   Updated upload endpoints with MediaBlocklist checks. ([597378bf](https://github.com/pixelfed/pixelfed/commit/597378bf))
-   Updated Timeline.vue component, fixes ([#2352](https://github.com/pixelfed/pixelfed/issues/2352)) and ([#2343](https://github.com/pixelfed/pixelfed/issues/2343)). ([e134a9ac](https://github.com/pixelfed/pixelfed/commit/e134a9ac))
-   Updated PostComponent.vue, improve MetroUI and fixes ([#2363](https://github.com/pixelfed/pixelfed/issues/2363)). ([0c8ebf26](https://github.com/pixelfed/pixelfed/commit/0c8ebf26))
-   Updated Timeline.vue, fixes ([#2363](https://github.com/pixelfed/pixelfed/issues/2363)). ([f53f10fd](https://github.com/pixelfed/pixelfed/commit/f53f10fd))
-   Updated Profile.vue, add atom feed link to context menu. Fixes ([#2313](https://github.com/pixelfed/pixelfed/issues/2313)). ([89f29072](https://github.com/pixelfed/pixelfed/commit/89f29072))
-   Updated Hashtag.vue, add nsfw toggle. Fixes ([#2225](https://github.com/pixelfed/pixelfed/issues/2225)). ([e5aa506c](https://github.com/pixelfed/pixelfed/commit/e5aa506c))
-   Updated Timeline.vue, move compose button. ([9cad8f77](https://github.com/pixelfed/pixelfed/commit/9cad8f77))
-   Updated status embed, allow photo albums. Fixes ([#2374](https://github.com/pixelfed/pixelfed/issues/2374)). ([d11fac0d](https://github.com/pixelfed/pixelfed/commit/d11fac0d))
-   Updated DiscoverController, fixes ([#2378](https://github.com/pixelfed/pixelfed/issues/2378)). ([8e7f4f9d](https://github.com/pixelfed/pixelfed/commit/8e7f4f9d))
-   Updated SearchController, update version. ([8d923d77](https://github.com/pixelfed/pixelfed/commit/8d923d77))
-   Updated email confirmation middleware, add 2FA to allow list. Fixes ([#2385](https://github.com/pixelfed/pixelfed/issues/2385)). ([27f3b29c](https://github.com/pixelfed/pixelfed/commit/27f3b29c))
-   Updated NotificationTransformer, fixes ([#2389](https://github.com/pixelfed/pixelfed/issues/2389)). ([c4506ebd](https://github.com/pixelfed/pixelfed/commit/c4506ebd))
-   Updated Profile + Timeline components, simplify UI. ([38d28ab4](https://github.com/pixelfed/pixelfed/commit/38d28ab4))
-   Updated Profile component, make modals scrollable. ([d1c664fa](https://github.com/pixelfed/pixelfed/commit/d1c664fa))
-   Updated PostComponent, fixes #2351. ([7a62a42a](https://github.com/pixelfed/pixelfed/commit/7a62a42a))
-   Updated DirectMessageController, fix pgsql bug. ([f1c28e7d](https://github.com/pixelfed/pixelfed/commit/f1c28e7d))
-   Updated RegisterController, make the minimum user password length configurable. ([09479c02](https://github.com/pixelfed/pixelfed/commit/09479c02))
-   Updated AuthServiceProvider, added support for configurable OAuth tokens and refresh tokens lifetime. ([7cfae612](https://github.com/pixelfed/pixelfed/commit/7cfae612))
-   Updated EmailService, make case insensitive. ([1b41d664](https://github.com/pixelfed/pixelfed/commit/1b41d664))
-   Updated DiscoverController, fix trending api. ([2ab2c9a](https://github.com/pixelfed/pixelfed/commit/2ab2c9a))
-   Updated Dark Mode layout. ([d6f8170](https://github.com/pixelfed/pixelfed/commit/d6f8170))
-   Updated federation config, make sharedInbox enabled by default. ([6e3522c0](https://github.com/pixelfed/pixelfed/commit/6e3522c0))
-   Updated PostComponent, change timestamp format. ([e51665f6](https://github.com/pixelfed/pixelfed/commit/e51665f6))
-   Updated PostComponent, use proper username context for reply mentions. Fixes ([#2421](https://github.com/pixelfed/pixelfed/issues/2421)). ([dac06088](https://github.com/pixelfed/pixelfed/commit/dac06088))
-   Updated Navbar, added profile avatar. ([19abf1b4](https://github.com/pixelfed/pixelfed/commit/19abf1b4))
-   Updated package.json, add blurhash. ([cc1b081a](https://github.com/pixelfed/pixelfed/commit/cc1b081a))
-   Updated Status model, fix thumb nsfw caching. ([327ef138](https://github.com/pixelfed/pixelfed/commit/327ef138))
-   Updated User model, add interstitial relation. ([bd321a72](https://github.com/pixelfed/pixelfed/commit/bd321a72))
-   Updated StatusStatelessTransformer, add missing attributes. ([4d22426d](https://github.com/pixelfed/pixelfed/commit/4d22426d))
-   Updated media pipeline, add blurhash support. ([473e0495](https://github.com/pixelfed/pixelfed/commit/473e0495))
-   Updated DeleteAccountPipeline, add AccountInterstitial and DirectMessage purging. ([b3078f27](https://github.com/pixelfed/pixelfed/commit/b3078f27))
-   Updated ComposeModal.vue component, reuse sharedData. ([e28d022f](https://github.com/pixelfed/pixelfed/commit/e28d022f))
-   Updated ApiController, return status object after deletion. ([0718711d](https://github.com/pixelfed/pixelfed/commit/0718711d))
-   Updated InternalApiController, add interstitial logic. ([20681bcf](https://github.com/pixelfed/pixelfed/commit/20681bcf))
-   Updated PublicApiController, improve stateless object caching. ([342e7a50](https://github.com/pixelfed/pixelfed/commit/342e7a50))
-   Updated StatusController, add interstitial logic. ([003caf7e](https://github.com/pixelfed/pixelfed/commit/003caf7e))
-   Updated middleware, add AccountInterstitial support. ([19d6e7df](https://github.com/pixelfed/pixelfed/commit/19d6e7df))
-   Updated BaseApiController, add favourites method. ([76353ca9](https://github.com/pixelfed/pixelfed/commit/76353ca9))
-   Updated dockerfile, fix composer issue. ([ef45c4b21](https://github.com/pixelfed/pixelfed/commit/ef45c4b21))
-   Updated reply/comment view, improve layout and include child reply. ([2eca670e](https://github.com/pixelfed/pixelfed/commit/2eca670e))
-   Updated Collections, add custom limit. ([048642be](https://github.com/pixelfed/pixelfed/commit/048642be))
-   Updated AccountInterstitialController, add autospam type. ([c67f0c57](https://github.com/pixelfed/pixelfed/commit/c67f0c57))
-   Updated Profile model, improve counter caching. ([4a14e970](https://github.com/pixelfed/pixelfed/commit/4a14e970))
-   Updated ComposeModal, fix filter bug on safari. ([8e3e7586](https://github.com/pixelfed/pixelfed/commit/8e3e7586))
-   Updated StatusStatelessController, remove unused attributes. ([d0d46807](https://github.com/pixelfed/pixelfed/commit/d0d46807))
-   Updated Profile, fix follower counter bug. ([d06bec9c](https://github.com/pixelfed/pixelfed/commit/d06bec9c))
-   Updated NotificationTransformer, add missing types. ([3a428366](https://github.com/pixelfed/pixelfed/commit/3a428366))
-   Updated StatusService, fix json bug. ([1ea2db74](https://github.com/pixelfed/pixelfed/commit/1ea2db74))
-   Updated NotificationTransformer, handle tagged deletes. ([881fa865](https://github.com/pixelfed/pixelfed/commit/881fa865))
-   Updated horizon config, add new default values. ([90c8a721](https://github.com/pixelfed/pixelfed/commit/90c8a721))
-   Updated ComposeModal, add maxlength attribute to alt text input. Fixes ([#2490](https://github.com/pixelfed/pixelfed/issues/2490)). ([526b5531](https://github.com/pixelfed/pixelfed/commit/526b5531))
-   Updated PublicApiController, add state endpoint. ([9fc5a80c](https://github.com/pixelfed/pixelfed/commit/9fc5a80c))
-   Updated PostComponent, add reply modal. ([a10d851f](https://github.com/pixelfed/pixelfed/commit/a10d851f))
-   Updated Timeline, remove simple mode and set labs deprecation date. ([df9c3adf](https://github.com/pixelfed/pixelfed/commit/df9c3adf))
-   Updated 2FA setup, fix qrcode handler. ([cd2661fc](https://github.com/pixelfed/pixelfed/commit/cd2661fc))
-   Updated avatars, use jpeg default. ([f6528c84](https://github.com/pixelfed/pixelfed/commit/f6528c84))
-   Updated antispam bouncer, change recent from 1 week to 3 months. ([7d818197](https://github.com/pixelfed/pixelfed/commit/7d818197))
-   Updated Post components, fix remote post and profile urls. ([cfcf17f3](https://github.com/pixelfed/pixelfed/commit/cfcf17f3))
-   Updated migrations, fix broken oauth change. ([4a885c88](https://github.com/pixelfed/pixelfed/commit/4a885c88))
-   Updated LikeController, store status_profile_id and is_comment attributes. ([799a4cba](https://github.com/pixelfed/pixelfed/commit/799a4cba))
-   Updated Profile, fix status count. ([6dcd472b](https://github.com/pixelfed/pixelfed/commit/6dcd472b))
-   Updated StatusService, cast response to array. ([0fbde91e](https://github.com/pixelfed/pixelfed/commit/0fbde91e))
-   Updated status model, use scope over deprecated visibility attribute. ([f70826e1](https://github.com/pixelfed/pixelfed/commit/f70826e1))
-   Updated Follower model, increase hourly limit from 30 to 150. ([b9b84e6f](https://github.com/pixelfed/pixelfed/commit/b9b84e6f))
-   Updated StatusController, fix scope bug. ([7dc3739c](https://github.com/pixelfed/pixelfed/commit/7dc3739c))
-   Updated AP helpers, fixed federation bug. ([a52564f3](https://github.com/pixelfed/pixelfed/commit/a52564f3))
-   Updated Helpers, cache profiles. ([1f672ecf](https://github.com/pixelfed/pixelfed/commit/1f672ecf))
-   Updated DiscoverController, improve trending api performance. ([d8d3331f](https://github.com/pixelfed/pixelfed/commit/d8d3331f))
-   Updated InboxWorker, fix race condition in account deletes. ([4a4d8f00](https://github.com/pixelfed/pixelfed/commit/4a4d8f00))
-   Updated StoryItemTransformer, increase story duration from 5 seconds to 10 seconds. ([5b0b14fc](https://github.com/pixelfed/pixelfed/commit/5b0b14fc))
-   Updated StatusController, add view method. ([0cfc12c5](https://github.com/pixelfed/pixelfed/commit/0cfc12c5))
-   Updated MediaPathService, add story method. ([aac44309](https://github.com/pixelfed/pixelfed/commit/aac44309))
-   Updated StatusDelete job, handle cloud storage media deletes. ([4b1a0fd7](https://github.com/pixelfed/pixelfed/commit/4b1a0fd7))
-   Updated ImageOptimizePipeline, add skip_optimize and MediaStorageService support. ([234f72f3](https://github.com/pixelfed/pixelfed/commit/234f72f3))
-   Updated Media model, add cdn support to url and thumbnailUrl methods. ([57fa889d](https://github.com/pixelfed/pixelfed/commit/57fa889d))
-   Updated MediaController, remove deprecated endpoint. ([8132db74](https://github.com/pixelfed/pixelfed/commit/8132db74))
-   Updated api controllers, deprecate old endpoints. ([4415af1b](https://github.com/pixelfed/pixelfed/commit/4415af1b))
-   Updated mobile apis, add blurhash. ([cf40526e](https://github.com/pixelfed/pixelfed/commit/cf40526e))
-   Updated Image media util, store dimensions of media not thumbnail. ([40bd64aa](https://github.com/pixelfed/pixelfed/commit/40bd64aa))
-   Updated MediaTransformers, include meta attribute with focus and dimensions. ([f8cbe1e4](https://github.com/pixelfed/pixelfed/commit/f8cbe1e4))
-   Updated storage, add remote media cache directory. ([0eabbfdd](https://github.com/pixelfed/pixelfed/commit/0eabbfdd))
-   Updated backup config, prevents gateway timeouts for large databases using mysql. ([9cd4bd74](https://github.com/pixelfed/pixelfed/commit/9cd4bd74))
-   Updated MediaPipeline, handle cloud object storage. ([be6d12fc](https://github.com/pixelfed/pixelfed/commit/be6d12fc))
-   Updated AP Helpers, use MediaStoragePipeline. ([01a1ffd6](https://github.com/pixelfed/pixelfed/commit/01a1ffd6))
-   Updated RemoteProfile component, change thumbnail url. ([c1118956](https://github.com/pixelfed/pixelfed/commit/c1118956))
-   Updated blade views. ([9683e846](https://github.com/pixelfed/pixelfed/commit/9683e846))
-   Updated cache config, use phpredis by default. ([ed6877df](https://github.com/pixelfed/pixelfed/commit/ed6877df))
-   Updated components, fix url rewriter. Closes #2538. ([e8cc66dc](https://github.com/pixelfed/pixelfed/commit/e8cc66dc))
-   Updated UserCreate command, closes #2581. ([b2b8c9f9](https://github.com/pixelfed/pixelfed/commit/b2b8c9f9))
-   Updated AvatarController, remove deprecated thumb_path. ([889c3d87](https://github.com/pixelfed/pixelfed/commit/889c3d87))
-   Updated VideoThumbnail, add MediaStoragePipeline. ([98c44f7b](https://github.com/pixelfed/pixelfed/commit/98c44f7b))
-   Updated StatusDelete pipeline, fix object storage thumbnail deletion. ([f930c4bd](https://github.com/pixelfed/pixelfed/commit/f930c4bd))
-   Updated MediaStorageService, clear transformer cache after storing media. ([ce6ab80d](https://github.com/pixelfed/pixelfed/commit/ce6ab80d))
-   Updated MediaTransformer, remove cache busting. ([258b2729](https://github.com/pixelfed/pixelfed/commit/258b2729))
-   Updated AP helpers, only run MediaStoragePipeline if using cloud storage. ([77f21b4b](https://github.com/pixelfed/pixelfed/commit/77f21b4b))
-   Updated AvatarObserver, add logic to delete avatars stored in S3. ([9eafc31e](https://github.com/pixelfed/pixelfed/commit/9eafc31e))
-   Updated Profile model, use cdn_url for avatars. ([ea8e4261](https://github.com/pixelfed/pixelfed/commit/ea8e4261))
-   Updated ActivityPubFetchService, add url validation. ([654b08d3](https://github.com/pixelfed/pixelfed/commit/654b08d3))
-   Updated MediaStorageService, add avatar method. ([94a9f685](https://github.com/pixelfed/pixelfed/commit/94a9f685))
-   Updated AvatarPipeline, add remote avatar fetch. ([4c148055](https://github.com/pixelfed/pixelfed/commit/4c148055))
-   Updated ComposeController, update media version. ([cc2d4bf8](https://github.com/pixelfed/pixelfed/commit/cc2d4bf8))
-   Updated AP Helpers, add blurhash and RemoteAvatarFetch. ([de8828e8](https://github.com/pixelfed/pixelfed/commit/de8828e8))
-   Updated Timeline, prevent nextTick() when reloading same comment modal. Fixes #2584. ([cc84125b](https://github.com/pixelfed/pixelfed/commit/cc84125b))
-   Updated site config, add labels to config. ([abe9cb3d](https://github.com/pixelfed/pixelfed/commit/abe9cb3d))
-   Update StatusLabelService, change config key. ([4abfe76a](https://github.com/pixelfed/pixelfed/commit/4abfe76a))

## [v0.10.9 (2020-04-17)](https://github.com/pixelfed/pixelfed/compare/v0.10.8...v0.10.9)

### Added

-   Added Profile Following Search ([e3280c11](https://github.com/pixelfed/pixelfed/commit/e3280c11))
-   Added Trusted Devices to Sudo Mode ([0c82c970](https://github.com/pixelfed/pixelfed/commit/0c82c970))
-   Added reply modal to posts and timelines ([974e6bda](https://github.com/pixelfed/pixelfed/commit/974e6bda))
-   Added remote posts and profiles ([95bce31e](https://github.com/pixelfed/pixelfed/commit/95bce31e))
-   Added Labs deprecation page ([9b215001](https://github.com/pixelfed/pixelfed/commit/9b215001))
-   Added new landing page ([84e203a9](https://github.com/pixelfed/pixelfed/commit/84e203a9))

### Fixed

-   Stories on postgres instances ([5ffa71da](https://github.com/pixelfed/pixelfed/commit/5ffa71da))

### Updated

-   Updated StatusController, restrict edits to 24 hours ([ae24433b](https://github.com/pixelfed/pixelfed/commit/ae24433b))
-   Updated RateLimit, add max post edits per hour and day ([51fbfcdc](https://github.com/pixelfed/pixelfed/commit/51fbfcdc))
-   Updated Timeline.vue, move announcements from sidebar to top of timeline ([228f5044](https://github.com/pixelfed/pixelfed/commit/228f5044))
-   Updated lexer autolinker and extractor, add support for mentioned usernames containing dashes, periods and underscore characters ([f911c96d](https://github.com/pixelfed/pixelfed/commit/f911c96d))
-   Updated Story apis, move FE to v0 and add v1 for oauth clients ([92654fab](https://github.com/pixelfed/pixelfed/commit/92654fab))
-   Updated robots.txt ([25101901](https://github.com/pixelfed/pixelfed/commit/25101901))
-   Updated mail panel blade view, fix markdown bug ([cbc63b04](https://github.com/pixelfed/pixelfed/commit/cbc63b04))
-   Updated self-diagnosis checks ([03f808c7](https://github.com/pixelfed/pixelfed/commit/03f808c7))
-   Updated DiscoverController, fixes #2009 ([b04c7170](https://github.com/pixelfed/pixelfed/commit/b04c7170))
-   Updated DeleteAccountPipeline, fixes [#2016](https://github.com/pixelfed/pixelfed/issues/2016), a bug affecting account deletion.
-   Updated PlaceController, fixes [#2017](https://github.com/pixelfed/pixelfed/issues/2017), a postgres bug affecting country pagination in the places directory ([dd5fa3a4](https://github.com/pixelfed/pixelfed/commit/dd5fa3a4))
-   Updated confirm email blade view, remove html5 entity that doesn't display properly ([aa26fa1d](https://github.com/pixelfed/pixelfed/commit/aa26fa1d))
-   Updated ApiV1Controller, fix update_credentials endpoint ([a73fad75](https://github.com/pixelfed/pixelfed/commit/a73fad75))
-   Updated AdminUserController, add moderation method ([a4cf21ea](https://github.com/pixelfed/pixelfed/commit/a4cf21ea))
-   Updated BaseApiController, invalidate session after account deletion ([826978ce](https://github.com/pixelfed/pixelfed/commit/826978ce))
-   Updated AdminUserController, add account deletion handler ([9be19ad8](https://github.com/pixelfed/pixelfed/commit/9be19ad8))
-   Updated ContactController, fixes [#2042](https://github.com/pixelfed/pixelfed/issues/2042) ([c9057e87](https://github.com/pixelfed/pixelfed/commit/c9057e87))
-   Updated Media model, fix remote media preview ([9947050b](https://github.com/pixelfed/pixelfed/commit/9947050b))
-   Updated PostComponent, improve likes modal ([664fd272](https://github.com/pixelfed/pixelfed/commit/664fd272))
-   Updated StoryViewer, preload media ([336571d0](https://github.com/pixelfed/pixelfed/commit/336571d0))
-   Updated StoryCompose, add expand label for lightbox preview ([fdf59753](https://github.com/pixelfed/pixelfed/commit/fdf59753))
-   Updated session config, increase session timeout from 2 days to 60 days ([b8795271](https://github.com/pixelfed/pixelfed/commit/b8795271))
-   Updated WebfingerService, cache lookup ([8b9faf31](https://github.com/pixelfed/pixelfed/commit/8b9faf31))
-   Updated v1 notifications api, fix optional params ([4e3c952c](https://github.com/pixelfed/pixelfed/commit/4e3c952c))
-   Updated ApiV1Controller, fix unfavourite bug [#2088](https://github.com/pixelfed/pixelfed/issues/2088) ([3a828522](https://github.com/pixelfed/pixelfed/commit/3a828522))
-   Updated SharePipeline, fix item relation bug ([b5899648](https://github.com/pixelfed/pixelfed/commit/b5899648))
-   Updated Profile.vue, add v-once to thumbnails to prevent re-render ([a54685f6](https://github.com/pixelfed/pixelfed/commit/a54685f6))
-   Updated SearchResults.vue, improve layout ([7e41b4ae](https://github.com/pixelfed/pixelfed/commit/7e41b4ae))
-   Updated PostMenu.vue, fix styling of list-group ([4c3b0b7d](https://github.com/pixelfed/pixelfed/commit/4c3b0b7d))
-   Updated PostComponent.vue, update styling ([844566b9](https://github.com/pixelfed/pixelfed/commit/844566b9))
-   Updated NotificationCard.vue, fix share notifications ([3cb676b1](https://github.com/pixelfed/pixelfed/commit/3cb676b1))
-   Updated PostComponent.vue, remove like count from title, fixes [#2091](https://github.com/pixelfed/pixelfed/issues/2091) ([6026998c](https://github.com/pixelfed/pixelfed/commit/6026998c))
-   Updated SearchController, add WebfingerService support ([869b4ff7](https://github.com/pixelfed/pixelfed/commit/869b4ff7))
-   Updated Profile model, use change_count for version ([0eae9f8b](https://github.com/pixelfed/pixelfed/commit/0eae9f8b))
-   Updated Timeline.vue, add remote post/profile links ([d4147083](https://github.com/pixelfed/pixelfed/commit/d4147083))
-   Updated StoryTimelineComponent, added list prop for new timeline layout ([1692a95a](https://github.com/pixelfed/pixelfed/commit/1692a95a))
-   Updated blank layout, add sharedData js ([4a293ed9](https://github.com/pixelfed/pixelfed/commit/4a293ed9))
-   Updated oauth api, allow multiple redirect_uris. Fixes #[2106](https://github.com/pixelfed/pixelfed/issues/2106) ([0540a28a](https://github.com/pixelfed/pixelfed/commit/0540a28a))
-   Updated ActivityPub Outbox, fixes #[2100](https://github.com/pixelfed/pixelfed/issues/2100) ([c84cee5a](https://github.com/pixelfed/pixelfed/commit/c84cee5a))
-   Updated ApiV1Controller, fixes #[2112](https://github.com/pixelfed/pixelfed/issues/2112) ([324ccd0a](https://github.com/pixelfed/pixelfed/commit/324ccd0a))
-   Updated StatusTransformer, fixes #[2113](https://github.com/pixelfed/pixelfed/issues/2113) ([eefa6e0d](https://github.com/pixelfed/pixelfed/commit/eefa6e0d))
-   Updated InternalApiController, limit remote profile ui to remote profiles ([d918a68e](https://github.com/pixelfed/pixelfed/commit/d918a68e))
-   Updated NotificationCard, fix pagination bug #[2019](https://github.com/pixelfed/pixelfed/issues/2019) ([32beaad5](https://github.com/pixelfed/pixelfed/commit/32beaad5))

## [v0.10.8 (2020-01-29)](https://github.com/pixelfed/pixelfed/compare/v0.10.7...v0.10.8)

### Added

-   Added `BANNED_USERNAMES` .env var, an optional comma separated string to ban specific usernames from being used ([6cdd64c6](https://github.com/pixelfed/pixelfed/commit/6cdd64c6))
-   Added RestrictedAccess middleware for Restricted Mode ([17c1a83d](https://github.com/pixelfed/pixelfed/commit/17c1a83d))
-   Added FailedJob garbage collection ([5d424f12](https://github.com/pixelfed/pixelfed/commit/5d424f12))
-   Added Password Reset garbage collection ([829c41e1](https://github.com/pixelfed/pixelfed/commit/829c41e1))

### Fixed

-   Fixed Story Compose bug affecting postgres instances ([#1918](https://github.com/pixelfed/pixelfed/pull/1918))
-   Fixed header background bug on MomentUI profiles ([#1933](https://github.com/pixelfed/pixelfed/pull/1933))
-   Fixed TRUST_PROXIES configuration ([#1941](https://github.com/pixelfed/pixelfed/pull/1941))
-   Fixed settings page default language ([4223a11e](https://github.com/pixelfed/pixelfed/commit/4223a11e))
-   Fixed DeleteAccountPipeline bug that did not use proper media paths ([578d2f35](https://github.com/pixelfed/pixelfed/commit/578d2f35))
-   Fixed mastoapi StatusTransformer, fix in_reply_to_id cast to string instead of int ([6ed00c94](https://github.com/pixelfed/pixelfed/commit/6ed00c94))

### Updated

-   Updated presenter components, load fallback image on errors ([273170c5](https://github.com/pixelfed/pixelfed/commit/273170c5))
-   Updated Story model, hide json attribute by default ([de89403c](https://github.com/pixelfed/pixelfed/commit/de89403c))
-   Updated compose view, add deprecation notice for v3 ([57e155b9](https://github.com/pixelfed/pixelfed/commit/57e155b9))
-   Updated StoryController, orientate story media and strip exif ([07a13fcf](https://github.com/pixelfed/pixelfed/commit/07a13fcf))
-   Updated admin reports, fixed 404 bug ([dbd5c4cf](https://github.com/pixelfed/pixelfed/commit/dbd5c4cf))
-   Updated AdminController, abstracted dashboard stats to AdminStatsService ([41abe9d2](https://github.com/pixelfed/pixelfed/commit/41abe9d2))
-   Updated StoryCompose component, added upload progress page ([2de3c56f](https://github.com/pixelfed/pixelfed/commit/2de3c56f))
-   Updated instance config, cleanup and add restricted mode ([3be32597](https://github.com/pixelfed/pixelfed/commit/3be32597))
-   Update RelationshipSettings Controller, fixes #1605 ([4d2da2f1](https://github.com/pixelfed/pixelfed/commit/4d2da2f1))
-   Updated password reset, now expires after 24 hours ([829c41e1](https://github.com/pixelfed/pixelfed/commit/829c41e1))
-   Updated nav layout ([73249dc2](https://github.com/pixelfed/pixelfed/commit/73249dc2))
-   Updated views with noscript warnings ([eaca43a6](https://github.com/pixelfed/pixelfed/commit/eaca43a6))

### Changed

## [v0.10.7 (2020-01-07)](https://github.com/pixelfed/pixelfed/compare/v0.10.6...v0.10.7)

### Added

-   Added drafts API endpoint for Camera Roll ([bad2ecde](https://github.com/pixelfed/pixelfed/commit/bad2ecde))
-   Added AccountService ([885a1258](https://github.com/pixelfed/pixelfed/commit/885a1258))
-   Added post embeds ([1fecf717](https://github.com/pixelfed/pixelfed/commit/1fecf717))
-   Added profile embeds ([fb7a3cf0](https://github.com/pixelfed/pixelfed/commit/fb7a3cf0))
-   Added Force MetroUI labs experiment ([#1889](https://github.com/pixelfed/pixelfed/pull/1889))
-   Added Stories, to enable add `STORIES_ENABLED=true` to `.env` and run `php artisan config:cache && php artisan cache:clear`. If opcache is enabled you may need to reload the web server.

### Fixed

-   Fixed like and share/reblog count on profiles ([86cb7d09](https://github.com/pixelfed/pixelfed/commit/86cb7d09))
-   Fixed non federating self boosts ([0c59a55e](https://github.com/pixelfed/pixelfed/commit/0c59a55e))
-   Fixed CORS issues with API endpoints ([6d6f517d](https://github.com/pixelfed/pixelfed/commit/6d6f517d))
-   Fixed mixed albums not appearing on timelines ([e01dff45](https://github.com/pixelfed/pixelfed/commit/e01dff45))

### Changed

-   Removed `relationship` from `AccountTransformer` ([4d084ac5](https://github.com/pixelfed/pixelfed/commit/4d084ac5))
-   Updated `notification` api endpoint to use `NotificationService` ([f4039ce2](https://github.com/pixelfed/pixelfed/commit/f4039ce2)) ([6ef7597](https://github.com/pixelfed/pixelfed/commit/6ef7597))
-   Update footer to use localization for the `Places` link ([39712714](https://github.com/pixelfed/pixelfed/commit/39712714))
-   Updated ComposeModal.vue, added a caption counter. Fixes [#1722](https://github.com/pixelfed/pixelfed/issues/1722). ([009c6ee8](https://github.com/pixelfed/pixelfed/commit/009c6ee8))
-   Updated Notifications to use the NotificationService ([f4039ce2](https://github.com/pixelfed/pixelfed/commit/f4039ce218f93a5578225dfdba66f0359c8fc72c))
-   Updated PrivacySettings controller, clear cache after updating ([d8d11d7b](https://github.com/pixelfed/pixelfed/commit/d8d11d7b))
-   Updated BaseApiController, add timestamp to signed media previews for client side cache invalidation ([73c08987](https://github.com/pixelfed/pixelfed/commit/73c08987))
-   Updated AdminInstanceController, remove db transaction from instance scan ([5773434a](https://github.com/pixelfed/pixelfed/commit/5773434a))
-   Updated Help Center view, added outdated warning ([0e611d00](https://github.com/pixelfed/pixelfed/commit/0e611d00))
-   Updated language view, added English version of language names ([ebb998d2](https://github.com/pixelfed/pixelfed/commit/ebb998d2))
-   Updated app.js, added App.utils like `.format.count`, `.filters` and `.emoji` ([34c13b6e](https://github.com/pixelfed/pixelfed/commit/34c13b6e))
-   Updated CollectionCompose.vue component, fix api namespace change ([71ed965c](https://github.com/pixelfed/pixelfed/commit/71ed965c))
-   Updated PostComponent, mark caption sensitive if post is and use util.emoji ([35d51215](https://github.com/pixelfed/pixelfed/commit/35d51215))
-   Updated Profile.vue component, use formatted counts ([30f14961](https://github.com/pixelfed/pixelfed/commit/30f14961))
-   Updated Timeline.vue component, use formatted counts, util.emoji and increase pagination limit to 5 ([abfc9fe7](https://github.com/pixelfed/pixelfed/commit/abfc9fe7))
-   Updated album presenters, use better carousel ([31b114cc](https://github.com/pixelfed/pixelfed/commit/31b114cc)) ([0617fada](https://github.com/pixelfed/pixelfed/commit/0617fada)) ([767fc887](https://github.com/pixelfed/pixelfed/commit/767fc887))
-   Updated Timeline.vue component, remove tap for lightbox as it conflicts with new carousel ([96e25ad2](https://github.com/pixelfed/pixelfed/commit/96e25ad2))
-   Updated ComposeModal.vue, added album support, editing and UI tweaks ([3aaad81e](https://github.com/pixelfed/pixelfed/commit/3aaad81e))
-   Updated InternalApiController, increase license limit to 140 to match UI counter ([b3c18aec](https://github.com/pixelfed/pixelfed/commit/b3c18aec))
-   Updated album carousels, fix height bug ([8380822a](https://github.com/pixelfed/pixelfed/commit/8380822a))
-   Updated MediaController, add timestamp to signed preview url ([49efaae9](https://github.com/pixelfed/pixelfed/commit/49efaae9))
-   Updated BaseApiController, uncache verify_credentials method ([3fa9ac8b](https://github.com/pixelfed/pixelfed/commit/3fa9ac8b))
-   Updated StatusHashtagService, reduce cached hashtag count ttl from 6 hours to 5 minutes ([126886e8](https://github.com/pixelfed/pixelfed/commit/126886e8))
-   Updated Hashtag.vue component, added formatted posts count ([c71f3dd1](https://github.com/pixelfed/pixelfed/commit/c71f3dd1))
-   Updated FixLikes command, fix postgres support ([771f9c46](https://github.com/pixelfed/pixelfed/commit/771f9c46))
-   Updated Settings, hide sponsors feature until re-implemented in Profile UI ([c4dd8449](https://github.com/pixelfed/pixelfed/commit/c4dd8449))
-   Updated Status view, added `video` open graph tag support ([#1799](https://github.com/pixelfed/pixelfed/pull/1799))
-   Updated AccountTransformer, added `local` attribute ([d2a90f11](https://github.com/pixelfed/pixelfed/commit/d2a90f11))
-   Updated Laravel framework from v5.8 to v6.x ([3aff6de33](https://github.com/pixelfed/pixelfed/commit/3aff6de33))
-   Updated FollowerController to fix bug affecting private profiles ([a429d961](https://github.com/pixelfed/pixelfed/commit/a429d961))
-   Updated StatusTransformer, added `local` attribute ([484bb509](https://github.com/pixelfed/pixelfed/commit/484bb509))
-   Updated PostComponent, fix bug affecting MomentUI and non authenticated users ([7b3fe215](https://github.com/pixelfed/pixelfed/commit/7b3fe215))
-   Updated FixUsernames command to allow usernames containing `.` ([e5d77c6d](https://github.com/pixelfed/pixelfed/commit/e5d77c6d))
-   Updated landing page, add age check ([d11e82c3](https://github.com/pixelfed/pixelfed/commit/d11e82c3))
-   Updated ApiV1Controller, add `mobile_apis` to /api/v1/instance endpoint ([57407463](https://github.com/pixelfed/pixelfed/commit/57407463))
-   Updated PublicTimelineService, add video media scopes ([7b00eba3](https://github.com/pixelfed/pixelfed/commit/7b00eba3))
-   Updated PublicApiController, add AccountService ([5ebd2c8a](https://github.com/pixelfed/pixelfed/commit/5ebd2c8a))
-   Updated CommentController, fix scope bug ([45ecad2a](https://github.com/pixelfed/pixelfed/45ecad2a))
-   Updated CollectionController, increase limit from 18 to 50. ([c2826fd3](https://github.com/pixelfed/pixelfed/c2826fd3))

## Deprecated

## [v0.10.6 (2019-09-30)](https://github.com/pixelfed/pixelfed/compare/v0.10.5...v0.10.6)

### Added

-   Added `/api/v1/accounts/update_credentials` endpoint [6afd6970](https://github.com/pixelfed/pixelfed/commit/6afd6970)
-   Added `/api/v1/accounts/{id}/followers` endpoint [41c91cba](https://github.com/pixelfed/pixelfed/commit/41c91cba)
-   Added `/api/v1/accounts/{id}/following` endpoint [607eb51b](https://github.com/pixelfed/pixelfed/commit/607eb51b)
-   Added `/api/v1/accounts/{id}/statuses` endpoint [8ce6c1f2](https://github.com/pixelfed/pixelfed/commit/8ce6c1f2)
-   Added `/api/v1/accounts/{id}/follow` endpoint [f3839026](https://github.com/pixelfed/pixelfed/commit/f3839026)
-   Added `/api/v1/accounts/{id}/unfollow` endpoint [fadc96b2](https://github.com/pixelfed/pixelfed/commit/fadc96b2)
-   Added `/api/v1/accounts/relationships` endpoint [4b9f7d6b](https://github.com/pixelfed/pixelfed/commit/4b9f7d6b)
-   Added `/api/v1/accounts/search` endpoint [b1fccf6d](https://github.com/pixelfed/pixelfed/commit/b1fccf6d)
-   Added `/api/v1/blocks` endpoint [ac9f1bc0](https://github.com/pixelfed/pixelfed/commit/ac9f1bc0)
-   Added `/api/v1/accounts/{id}/block` endpoint [c6b1ed97](https://github.com/pixelfed/pixelfed/commit/c6b1ed97)
-   Added `/api/v1/accounts/{id}/unblock` endpoint [35226c99](https://github.com/pixelfed/pixelfed/commit/35226c99)
-   Added `/api/v1/custom_emojis` endpoint [6e43431a](https://github.com/pixelfed/pixelfed/commit/6e43431a)
-   Added `/api/v1/domain_blocks` endpoint [83a6313f](https://github.com/pixelfed/pixelfed/commit/83a6313f)
-   Added `/api/v1/endorsements` endpoint [1f16221e](https://github.com/pixelfed/pixelfed/commit/1f16221e)
-   Added `/api/v1/favourites` endpoint [b9cc06da](https://github.com/pixelfed/pixelfed/commit/b9cc06da)
-   Added `/api/v1/statuses/{id}/favourite` endpoint [4edeba17](https://github.com/pixelfed/pixelfed/commit/4edeba17)
-   Added `/api/v1/statuses/{id}/unfavourite` endpoint [437e18e3](https://github.com/pixelfed/pixelfed/commit/437e18e3)
-   Added `/api/v1/filters` endpoint [b3d82edd](https://github.com/pixelfed/pixelfed/commit/b3d82edd)
-   Added `/api/v1/follow_requests` endpoint [97269136](https://github.com/pixelfed/pixelfed/commit/97269136)
-   Added `/api/v1/follow_requests/{id}/authorize` endpoint [7bdd9b2a](https://github.com/pixelfed/pixelfed/commit/7bdd9b2a)
-   Added `/api/v1/follow_requests/{id}/reject` endpoint [62aa922a](https://github.com/pixelfed/pixelfed/commit/62aa922a)
-   Added `/api/v1/suggestions` endpoint [e52aeeed](https://github.com/pixelfed/pixelfed/commit/e52aeeed)
-   Added `/api/v1/lists` endpoint [2a106c4e](https://github.com/pixelfed/pixelfed/commit/2a106c4e)
-   Added `/api/v1/accounts/{id}/lists` endpoint [dba172df](https://github.com/pixelfed/pixelfed/commit/dba172df)
-   Added `/api/v1/lists/{id}/accounts` endpoint [dba172df](https://github.com/pixelfed/pixelfed/commit/dba172df)
-   Added `/api/v1/media` endpoint [39f3e313](https://github.com/pixelfed/pixelfed/commit/39f3e313)
-   Added `/api/v1/media/{id}` endpoint [fcf231f4](https://github.com/pixelfed/pixelfed/commit/fcf231f4)
-   Added `/api/v1/mutes` endpoint [b280d183](https://github.com/pixelfed/pixelfed/commit/b280d183)
-   Added `/api/v1/accounts/{id}/mute` endpoint [3e98dce4](https://github.com/pixelfed/pixelfed/commit/3e98dce4)
-   Added `/api/v1/accounts/{id}/unmute` endpoint [41c96ddd](https://github.com/pixelfed/pixelfed/commit/41c96ddd)
-   Added `/api/v1/notifications` endpoint [39449f36](https://github.com/pixelfed/pixelfed/commit/39449f36)
-   Added `/api/v1/timelines/home` endpoint [cf3405d8](https://github.com/pixelfed/pixelfed/commit/cf3405d8)
-   Added `/api/v1/conversations` endpoint [336f9069](https://github.com/pixelfed/pixelfed/commit/336f9069)
-   Added `/api/v1/timelines/public` endpoint [f3eeb9c9](https://github.com/pixelfed/pixelfed/commit/f3eeb9c9)
-   Added `/api/v1/statuses/{id}/card` endpoint [92251208](https://github.com/pixelfed/pixelfed/commit/92251208)
-   Added `/api/v1/statuses/{id}/reblogged_by` endpoint [118006ed](https://github.com/pixelfed/pixelfed/commit/118006ed)
-   Added `/api/v1/statuses/{id}/favourited_by` endpoint [5cdff57d](https://github.com/pixelfed/pixelfed/commit/5cdff57d)
-   Added POST `/api/v1/statuses` endpoint [3aa729a3](https://github.com/pixelfed/pixelfed/commit/3aa729a3)
-   Added DELETE `/api/v1/statuses` endpoint [0a20b832](https://github.com/pixelfed/pixelfed/commit/0a20b832)
-   Added POST `/api/v1/statuses/{id}/reblog` endpoint [43cef282](https://github.com/pixelfed/pixelfed/commit/43cef282)
-   Added POST `/api/v1/statuses/{id}/unreblog` endpoint [3147fe5c](https://github.com/pixelfed/pixelfed/commit/3147fe5c)
-   Added GET `/api/v1/timelines/tag/{hashtag}` endpoint [2ff53be4](https://github.com/pixelfed/pixelfed/commit/2ff53be4)

### Fixed

-   Update developer settings pages, fix vue bug [cd365ab3](https://github.com/pixelfed/pixelfed/commit/cd365ab3)
-   Update User model, fix filter relationship [5a0c295e](https://github.com/pixelfed/pixelfed/commit/5a0c295e)

### Changed

-   Updated Inbox Accept.Follow to use id of remote object [#1715](https://github.com/pixelfed/pixelfed/pull/1715)
-   Update StatusTransformer, make spoiler_text non-nullable [b66cf9cd](https://github.com/pixelfed/pixelfed/commit/b66cf9cd)
-   Update FollowerController, make follow and unfollow methods public [6237897d](https://github.com/pixelfed/pixelfed/commit/6237897d)
-   Update DiscoverComponent, change api namespace [35275572](https://github.com/pixelfed/pixelfed/commit/35275572)

## Deprecated

-   Removed deprecated AttachmentTransformer, superceeded by MediaTransformer [9b5aac4f](https://github.com/pixelfed/pixelfed/commit/9b5aac4f)

### To enable mobile app support

-   Run `php artisan passport:keys`
-   Add `OAUTH_ENABLED=true` to .env
-   Run `php artisan config:cache`

## [v0.10.5 (2019-09-24)](https://github.com/pixelfed/pixelfed/compare/v0.10.4...v0.10.5)

### Added

-   Added `software` back to AccountTransformer [93c687c7](https://github.com/pixelfed/pixelfed/commit/93c687c7)

### Fixed

-   Fixed cache bug in privacy and terms pages [#1712](https://github.com/pixelfed/pixelfed/commit/fe522da8db7a8b0d7c18d405abcb885f8678f35c)

### Changed

## [v0.10.4 (2019-09-24)](https://github.com/pixelfed/pixelfed/compare/v0.10.3...v0.10.4)

### Added

-   Added Welsh translations [#1706](https://github.com/pixelfed/pixelfed/pull/1706)
-   Added Api v1 controller [85835f5a](https://github.com/pixelfed/pixelfed/commit/85835f5a6712dea0562df4be897087de5305750f)
-   Added database migration that adds a language column to the users table [c87d8c16](https://github.com/pixelfed/pixelfed/commit/c87d8c16)
-   Added persistent preferred language [18bc9c30](https://github.com/pixelfed/pixelfed/commit/18bc9c30)

### Fixed

-   Fixed count bug in StatusHashtagService [#1694](https://github.com/pixelfed/pixelfed/pull/1694)
-   Fixed private account bug [#1699](https://github.com/pixelfed/pixelfed/pull/1699)
-   Fixed comments on MomentUI posts [#1704](https://github.com/pixelfed/pixelfed/pull/1704)

### Changed

-   Updated EmailService, added new domains [#1690](https://github.com/pixelfed/pixelfed/pull/1690)
-   Updated quill.js to v1.3.7 [#1692](https://github.com/pixelfed/pixelfed/pull/1692)
-   Cache ProfileController [#1700](https://github.com/pixelfed/pixelfed/pull/1700)
-   Updated ComposeUI v4, made cropping optional [#1702](https://github.com/pixelfed/pixelfed/pull/1702)
-   Updated DiscoverController, limit Loops to local only posts [#1703](https://github.com/pixelfed/pixelfed/pull/1703)
-   Namespaced internal apis [3c306c5e](https://github.com/pixelfed/pixelfed/commit/3c306c5e179d35dbe19a6a1bd9533350e4b96524)
-   Updated .env.example with proper remote follow variable [0697f780](https://github.com/pixelfed/pixelfed/commit/0697f780d3a5cba72148f0a767d5a35124a3d9b4)
-   Updated show all comments view [0a5eaa31](https://github.com/pixelfed/pixelfed/pull/1708/commits/0a5eaa3118cb09c61d3e5442fe3bf8439a2a12af)
-   Updated language page layout [01fb5af](https://github.com/pixelfed/pixelfed/pull/1708/commits/01fb5af19e803488c5794b545d218771f6fce6d7)
-   Updated privacy policy page layout [a4229d5](https://github.com/pixelfed/pixelfed/pull/1708/commits/a4229d5d30faea11e7a72d122c4a5762d867aaf3)
-   Updated terms page layout [4f8c5e5](https://github.com/pixelfed/pixelfed/pull/1708/commits/4f8c5e5519949c63c702c724a00d8575db4e0014)
-   Update v1 API, added /api/v1/instance endpoint [951b6fa0](https://github.com/pixelfed/pixelfed/commit/951b6fa0) [9dc2234b](https://github.com/pixelfed/pixelfed/commit/99dc2234b)

## Deprecated

-   Remove deprecated profile following/followers [#1697](https://github.com/pixelfed/pixelfed/pull/1697)
-   Remove old comment permalink [05f6598](https://github.com/pixelfed/pixelfed/pull/1708/commits/05f659896d903e1ff41dba810f125d721fa057e7)

## [v0.10.3 (2019-09-08)](https://github.com/pixelfed/pixelfed/compare/v0.10.2...v0.10.3)

### Added

-   Append `.json` to local status urls to view ActivityPub object [#1666](https://github.com/pixelfed/pixelfed/pull/1666)

### Fixed

-   Reverted `strict` Same-Site Cookies to `null` to fix 2FA/session expiry [#1667](https://github.com/pixelfed/pixelfed/pull/1667)
-   Fixed AP errors by storing ActivityPub object id and url [#1668](https://github.com/pixelfed/pixelfed/pull/1668) [#1683](https://github.com/pixelfed/pixelfed/pull/1683)
-   Fixed content warnings that had filter applied [#1669](https://github.com/pixelfed/pixelfed/pull/1669)

### Changed

-   Japanese Translations [#1673](https://github.com/pixelfed/pixelfed/pull/1673)
-   Occitan Translations [#1679](https://github.com/pixelfed/pixelfed/pull/1679)
-   Use footer partial on landing page [#1681](https://github.com/pixelfed/pixelfed/pull/1681)
-   Change admin badge so it doesn't look like a verified badge [#1684](https://github.com/pixelfed/pixelfed/pull/1684)

### Deprecated

-   Personalized Discover has been deprecated due to low use [#1670](https://github.com/pixelfed/pixelfed/pull/1670)

## [v0.10.2 (2019-09-06)](https://github.com/pixelfed/pixelfed/compare/v0.10.1...v0.10.2)

### Fixed

-   Typo in Inbox prevented proper federation support [#1664](https://github.com/pixelfed/pixelfed/pull/1664)

## [v0.10.1 (2019-09-06)](https://github.com/pixelfed/pixelfed/compare/v0.10.0...v0.10.1)

### Added

-   Remote follows! Search for an actor URI, send AP Follow, plus handle incoming AP Accept Follow
-   Compose UI v4: a rework of the v3 flow to allow basic cropping and better support future post types
-   Profile badges show if a user is following you or is an admin
-   Show confirmation message when muting or blocking a user from a post
-   Allow "read more" to be disabled on posts [#1545](https://github.com/pixelfed/pixelfed/pull/1545)
-   Loops! Discover short videos
-   Preliminary support for profile PropertyValue metadata
-   Preliminary support for Direct Messages
-   Places! Run the artisan task `import:cities`
-   Emails are now validated and banned email domains are disallowed at signup. Artisan task `email:bancheck` will validate existing users.
-   .env vars `REDIS_SCHEME` and `REDIS_PATH` allow for using Redis over a Unix socket instead of TCP [#1602](https://github.com/pixelfed/pixelfed/pull/1602)
-   .env var `IMAGE_DRIVER` allows using imagick instead of gd

### Fixed

-   Show delete button while composing video posts [#1529](https://github.com/pixelfed/pixelfed/pull/1529)
-   Show pending follow requests on private profiles
-   Allow muted users to comment on your posts [#1537](https://github.com/pixelfed/pixelfed/pull/1537)
-   Bugs with carousel cursor and tooltips
-   Collections can now be deleted from collection page
-   Compose modal now indicates album media limits
-   Unlisted and private posts are now delivered
-   Don't show Register link in navbar when registrations are closed

### Changed

-   Use vue-masonry for Moment UI layout [#1536](https://github.com/pixelfed/pixelfed/pull/1536)
-   User post limit changed from 20/hr to 50/hr
-   Better mobile profile layout
-   Dark mode is now a bit bluer
-   Sample nginx.conf in contrib/ now uses HTTPS instead of HTTP. Docs updated to reference this file
-   Updated register form
-   Allow users to edit email after registrations

## [v0.10.0 (2019-07-17)](https://github.com/pixelfed/pixelfed/compare/v0.9.6...v0.10.0)

### Added

-   Collections! Add posts to Collections, similar to categories. [#1511](https://github.com/pixelfed/pixelfed/pull/1511)
-   Profile donate links: add links to Patreon, Liberapay, and OpenCollective on your profile [#1500](https://github.com/pixelfed/pixelfed/pull/1500)

### Fixed

-   Show correct mode when viewing followers / following

### Changed

-   Profile model now uses snowflake id [#1502](https://github.com/pixelfed/pixelfed/pull/1502)

### Removed

-   OStatus legacy code has been removed [#1510](https://github.com/pixelfed/pixelfed/pull/1510)

## [v0.9.6 (2019-07-10)](https://github.com/pixelfed/pixelfed/compare/v0.9.5...v0.9.6)

### Fixed

-   Hashtag post count off-by-one [#1485](https://github.com/pixelfed/pixelfed/pull/1485)

## [v0.9.5 (2019-07-10)](https://github.com/pixelfed/pixelfed/compare/v0.9.4...v0.9.5)

### Added

-   Add StatusService [#1387](https://github.com/pixelfed/pixelfed/pull/1387), [425ec91](https://github.com/pixelfed/pixelfed/commit/425ec91)
-   Add PublicTimelineService [#1387](https://github.com/pixelfed/pixelfed/pull/1387), [734e892](https://github.com/pixelfed/pixelfed/commit/734e892)
-   Add RelationshipSettings trait [#1387](https://github.com/pixelfed/pixelfed/pull/1387), [bf8340f](https://github.com/pixelfed/pixelfed/commit/bf8340f)
-   Add Remote Follows [#1388](https://github.com/pixelfed/pixelfed/pull/1388)
-   Add Relationship Settings [#1388](https://github.com/pixelfed/pixelfed/pull/1388), [b10e03d](https://github.com/pixelfed/pixelfed/commit/b10e03d)
-   Add Configuration Editor to Admin Dashboard [#1388](https://github.com/pixelfed/pixelfed/pull/1388), [323dca1](https://github.com/pixelfed/pixelfed/commit/323dca1)
-   Add Migration, adding profile_id to users table [#1388](https://github.com/pixelfed/pixelfed/pull/1388), [bdfe633](https://github.com/pixelfed/pixelfed/commit/bdfe633)
-   Add Media configuration [#1414](https://github.com/pixelfed/pixelfed/pull/1414)
-   Add Content Warnings to comments [#1430](https://github.com/pixelfed/pixelfed/pull/1430), [42d81fc](https://github.com/pixelfed/pixelfed/commit/42d81fc) [8d4b3bd](https://github.com/pixelfed/pixelfed/commit/8d4b3bd) [73e162e4](https://github.com/pixelfed/pixelfed/commit/3e162e4)
-   Add new rate limits [#1436](https://github.com/pixelfed/pixelfed/pull/1436) [1f1df2d](https://github.com/pixelfed/pixelfed/commit/1f1df2d)
-   Add RegenerateThumbnails command to force thumbnail regeneration [#1437](https://github.com/pixelfed/pixelfed/pull/1437) [a3be4cd](https://github.com/pixelfed/pixelfed/commit/a3be4cd)
-   Add Pages Editor to Admin Dashboard [#1438](https://github.com/pixelfed/pixelfed/pull/1438) [ef3e30d](https://github.com/pixelfed/pixelfed/commit/ef3e30d) [718375a](https://github.com/pixelfed/pixelfed/commit/718375a) [79524a0](https://github.com/pixelfed/pixelfed/commit/79524a0) [13ceef0](https://github.com/pixelfed/pixelfed/commit/13ceef0) [2fbcd6d](https://github.com/pixelfed/pixelfed/commit/2fbcd6d) [bb207a4](https://github.com/pixelfed/pixelfed/commit/bb207a4) [ef07e31](https://github.com/pixelfed/pixelfed/commit/ef07e31) [aca5114](https://github.com/pixelfed/pixelfed/commit/aca5114) [59fcfc2](https://github.com/pixelfed/pixelfed/commit/59fcfc2) [e3cfd81](https://github.com/pixelfed/pixelfed/commit/e3cfd81) [7ade78b](https://github.com/pixelfed/pixelfed/commit/7ade78b) [4539afa](https://github.com/pixelfed/pixelfed/commit/4539afa) [1dbfcae](https://github.com/pixelfed/pixelfed/commit/1dbfcae)

### Changed

-   Update SearchController, fix AP verb typo [#1387](https://github.com/pixelfed/pixelfed/pull/1387), [dc8acf9](https://github.com/pixelfed/pixelfed/commit/dc8acf9)
-   Update StatusTransformer, increase media cache ttl to 14 days [#1387](https://github.com/pixelfed/pixelfed/pull/1387), [f35718b](https://github.com/pixelfed/pixelfed/commit/f35718b)
-   Update webpack config, extract vendor librarys [#1387](https://github.com/pixelfed/pixelfed/pull/1387), [b42db89](https://github.com/pixelfed/pixelfed/commit/b42db89)
-   Update admin statuses view, make table header light [#1387](https://github.com/pixelfed/pixelfed/pull/1387), [44afcc7](https://github.com/pixelfed/pixelfed/commit/44afcc7)
-   Update settings, move disable/delete to Security Settings [#1388](https://github.com/pixelfed/pixelfed/pull/1388), [ca0d638](https://github.com/pixelfed/pixelfed/commit/ca0d638)
-   Update Installer command [#1388](https://github.com/pixelfed/pixelfed/pull/1388), [506dd8b](https://github.com/pixelfed/pixelfed/commit/506dd8b)
-   Update UserObserver [#1388](https://github.com/pixelfed/pixelfed/pull/1388), [4ee3d10](https://github.com/pixelfed/pixelfed/commit/4ee3d10)
-   Update AuthLogin listener [#1388](https://github.com/pixelfed/pixelfed/pull/1388), [c27c751](https://github.com/pixelfed/pixelfed/commit/c27c751) [1e8b092](https://github.com/pixelfed/pixelfed/commit/1e8b092)
-   Update Image Optimization to not store EXIF by default [#1414](https://github.com/pixelfed/pixelfed/pull/1414)
-   Update Settings, hide OAuth/Developer pages when not enabled [#1413](https://github.com/pixelfed/pixelfed/pull/1413)
-   Update Presenter Components, move alt tag and filters to `<img>` element [#1415](https://github.com/pixelfed/pixelfed/pull/1415)
-   Update Api Controllers, add missing caption limit to `composePost()` and missing `is_nsfw` attribute to comment queries [#1429](https://github.com/pixelfed/pixelfed/pull/1429), [1cff278](https://github.com/pixelfed/pixelfed/commit/1cff278)
-   Update instances admin view, add scan button to find new instances [#1436](https://github.com/pixelfed/pixelfed/pull/1436) [a94a3ee](https://github.com/pixelfed/pixelfed/commit/a94a3ee)
-   Update registration page, add links to terms and privacy pages [#1488](https://github.com/pixelfed/pixelfed/pull/1488)

### Removed

-   Remove Classic Compose UI [#1434](https://github.com/pixelfed/pixelfed/pull/1434), [72bffd1](https://github.com/pixelfed/pixelfed/commit/72bffd1) [a2640af](https://github.com/pixelfed/pixelfed/commit/a2640af)
-

## [v0.9.4 (2019-06-03)](https://github.com/pixelfed/pixelfed/compare/v0.9.0...v0.9.4)

PSA: Due to the removal of Google Recaptcha, a one-time manual intervention is required. Please try the following after installing with composer:

```
rm -rf bootstrap/cache/*
composer dump-autoload
php artisan config:cache
```

### Added

-   Notification service
-   Notification card on timeline
-   Double-tap to like posts (no animation yet)
-   Moderator Mode for timelines
-   Emoji reaction bar
-   Like and reply to comments
-   Hello Loops! Short videos will now loop and be discoverable from the Discover page.
-   Labs: Optional profile recommendations
-   Labs: Show full caption instead of "read more" button
-   Labs: Simple "distraction-free" timeline -- no buttons, just images and captions

### Changed

-   Refactored notification view into a Vue component
-   Preparations for Circles, DMs, and other upcoming functionality
-   Default limit of 7500 follows
-   Default limit of 20 follows per hour
-   Default limit of 5 mentions per comment/caption
-   Default limit of 30 hashtags per comment/caption
-   Default limit of 2 links per comment/caption
-   Thumbnail info overlays on profiles should now scale down to small screens (#1234)
-   Moment UI containers are now properly sized (#1236)
-   Album posts now have contrast for next/prev arrows (#1238)
-   Filter previews now fit the image instead of stretching it (#1239)

### Removed

-   Google Recaptcha is no longer supported (#1231)
-   Lightbox has been deprecated in favor of double-tap-to-like; it will return as a dedicated button in the future (#1277)

## [v0.9.0 (2019-04-17)](https://github.com/pixelfed/pixelfed/compare/v0.8.6...v0.9.0)

### Added

-   Allow users to delete existing profile photos.
-   Preliminary support for managing developer tokens, as well as authorizing apps
-   Unmute and unblock users more easily. Profiles now reflect muting/blocking status.
-   Lazy-loading images with `loading="lazy"`, as supported in Blink
-   Added Network Timeline which includes non-local posts
-   Add broadcast events for real-time updates
-   Compose view now shows upload progress bar
-   You can now audit logged-in devices
-   Added WIP installer
-   Moment UI! This alternative profile view is less square and more full-width pictures.

### Changed

-   Allow admins to view reported private posts
-   Show sensitivity and privacy/audience in status views
-   Cleanup of legacy code
-   `commentsDisabled` has been replaced with preliminary support for Litepub Capability Enforcement (LiCE)
-   `rel="me"` now added to profile websites
-   Posts from locked accounts now default to followers-only

### Removed

-   Removed identicons due to SVG compatibility issues with federation. New users will instead be assigned a default avatar.

## [v0.8.6 (2019-04-06)](https://github.com/pixelfed/pixelfed/compare/v0.8.5...v0.8.6)

### Added

-   Add COSTAR - Confirm Object Sentiment Transform and Reduce

COSTAR is a filtering system that allows admins to define environment variables that will dynamically apply certain policies to posts of a defined scope, similar to Pleroma's MRF system.

Scopes:

-   Domain: apply to posts from a specific website
-   Actor: apply to posts from a specific profile/user
-   Keyword: apply to posts containing a specific string

Policies:

-   Block: Default blocks the defined scope
-   CW: Automatically rewrites the scope to apply a warning
-   Unlist: Removes the scope from public timelines
