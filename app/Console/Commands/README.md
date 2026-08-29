# Artisan Commands

This directory contains Pixelfed's custom Artisan console commands, grouped into
subfolders by purpose. Laravel auto-discovers every command in this tree, so the
subfolder is purely organizational — the command name is defined by each class's
`$signature`.

Run any command with `php artisan <command>`, and append `--help` to see its
full argument and option list.

## Folder layout

| Folder | Namespace | Purpose |
| --- | --- | --- |
| `Admin/` | `App\Console\Commands\Admin` | Instance administration and operator tooling |
| `Dev/` | `App\Console\Commands\Dev` | Local development and localization build helpers |
| `FixBugs/` | `App\Console\Commands\FixBugs` | One-off repair and data-fix utilities |
| `Install/` | `App\Console\Commands\Install` | Installation and upgrade |
| `Internal/` | `App\Console\Commands\Internal` | Scheduled/background maintenance (mostly run by the scheduler) |
| `Status/` | `App\Console\Commands\Status` | Read-only debug/diagnostic inspectors |
| `User/` | `App\Console\Commands\User` | User account management |
| `Concerns/` | `App\Console\Commands\Concerns` | Shared traits used by commands (not commands themselves) |

---

## Admin

| Command | Description |
| --- | --- |
| `admin:invite` | Create an invite link. |
| `backup:cloud` | Send backups to cloud storage. |
| `email:bancheck` | Check user emails against banned domains. |
| `app:captcha-toggle-command` | Show captcha status and optionally disable it. |
| `app:curated-onboarding` | Manage curated onboarding applications. |
| `app:delete-remote-profile` | Delete a remote profile. |
| `import:cities` | Import the cities dataset into the database. |
| `import:emojis` | Import custom emojis from a `tar.gz` archive (supports `--prefix`/`--suffix`). |
| `app:instance-manager` | Manage federated instances. |
| `admin:MediaMoveStorageCloudToCloud` | Cold-migrate media from an old S3 bucket to the current cloud bucket, verifying each copy. |
| `admin:MediaMoveStorageCloudToLocal` | Migrate cloud media back to local storage (download, verify, rewrite URLs, optionally delete cloud copy). |
| `admin:MediaMoveStorageLocalToCloud` | Migrate local media to cloud storage (upload, verify, rewrite URLs, delete local copy). |
| `admin:MigrateLocalS3MediaURL` | Rewrite stale local media cloud URLs from storage paths to the configured S3 host. Replaces the old `media:cloud-url-rewrite`. |
| `regenerate:thumbnails` | Regenerate image thumbnails for all image media. |
| `ap:update-actors` | Send Update Actor activities to known remote servers (`--force`). |
| `video:thumbnail` | Generate missing video thumbnails. |

## Dev

| Command | Description |
| --- | --- |
| `i18n:export` | Build and export the JS localization files. |
| `localization:generate` | Generate JSON files for all available localizations. |
| `seed:devusers` | Seed dev users (admin + regular) with random passwords. |
| `seed:follows` | Seed follow relationships for testing. |

## FixBugs

| Command | Description |
| --- | --- |
| `fix:avatars` | Replace old SVG identicon avatars with the default PNG avatar. |
| `avatar:storage` | Manage avatar storage. |
| `avatar:storage-deep-clean` | Clean up orphaned avatar storage. |
| `media:optimize` | Find and optimize media that has not yet been optimized. |
| `app:fetch-missing-media-mime-type` | Backfill missing MIME types on remote media by issuing HEAD requests. |
| `fix:profile:duplicates` | Fix duplicate profiles. |
| `fix:hashtags` | Fix hashtag records. |
| `fix:likes` | Recalculate like counts. |
| `media:fix-nonlocal-driver` | Repair filesystem records when `FILESYSTEM_DRIVER` is not set to local. |
| `app:fix-missing-user-profile` | Interactively create a missing profile for an affected user. |
| `admin:fixProfileCounts` | Resync a profile's cached counts (followers, following, statuses) from source tables; supports bulk `--all`/`--active`. |
| `fix:usernames` | Fix invalid usernames. |
| `app:hashtag-related-generate` | Generate related-hashtag data for a given tag. |
| `media:fix` | Repair media filter data (legacy, requires v0.10.8+). |
| `status:dedup` | Remove duplicate statuses from before the unique-URI migration. |

## Install

| Command | Description |
| --- | --- |
| `instance:actor` | Generate the instance actor. |
| `install` | CLI installer (`--dangerously-overwrite-env`, `--domain`, `--name`). |
| `update` | Run Pixelfed schema updates between versions. |

## Internal

These are primarily invoked by the scheduler (see `bootstrap/app.php`) rather than run by hand.

| Command | Description |
| --- | --- |
| `app:account-post-count-stat-update` | Update post counts from recent activity. |
| `app:cleanup-expired-app-registrations` | Delete app registrations older than 90 days. |
| `gc:sessions` | Garbage-collect database sessions. |
| `gc:failedjobs` | Delete failed jobs older than one month. |
| `app:hashtag-cached-count-update` | Update cached hashtag counters (`--limit`). |
| `app:import-remove-deleted-accounts` | Remove import data belonging to deleted accounts. |
| `app:import-upload-clean-storage` | Delete import storage directories for non-active users. |
| `app:import-upload-garbage-collection` | Garbage-collect skipped Instagram import posts. |
| `app:import-upload-media-to-cloud-storage` | Migrate imported Instagram media to S3 (`--limit`). |
| `app:instance-update-total-local-posts` | Update the total local post count. |
| `media:gc` | Delete media uploads not attached to any active status. |
| `app:notification-epoch-update` | Update the notification epoch. |
| `gc:passwordreset` | Delete password reset tokens older than 24 hours. |
| `app:push-gateway-refresh` | Refresh push-notification gateway support. |
| `app:software-update-refresh` | Refresh latest software version data. |
| `story:gc` | Clear expired stories. |
| `app:transform-imports` | Transform completed imports into statuses. |
| `app:weekly-instance-scan` | Scan instance nodeinfo weekly. |

## Status (debug/diagnostics)

Read-only inspectors for troubleshooting. They do not modify data.

| Command | Description |
| --- | --- |
| `status:post` | Show detailed metadata for a post and its media, including stored vs expected media URLs. |
| `status:profile` | Show detailed metadata for a local or remote profile (federation-aware). |
| `status:user` | Show detailed diagnostics for a user account (login & password reset), with recent account logs (`--logs`). |

## User

| Command | Description |
| --- | --- |
| `app:add-user-domain-block` | Apply a domain block for all users. |
| `app:delete-user-domain-block` | Remove a domain block for all users. |
| `app:reclaim-username` | Force-delete a user and profile to reclaim a username. |
| `app:user-account-delete` | Federate an account deletion (`--concurrency`, `--chunk`, `--attempts`, `--target`, `--dry-run`). |
| `user:admin` | Grant or remove admin privileges for a user. |
| `user:avatar-delete` | Delete a user avatar and reset to default (`--force`). |
| `user:checkpassword` | Read-only: verify a candidate password against the stored hash and diagnose login rejection. |
| `user:create` | Create a new user. |
| `user:delete` | Delete an account (`--force`). |
| `user:app-magic-link` | Get the app magic link for in-app registrations missing a confirmation email. |
| `user:setpassword` | Set/reset a user password (prompts securely, bcrypt). |
| `user:show` | Show user info. |
| `user:suspend` | Suspend a local user. |
| `user:table` | Display the latest users. |
| `user:2fa` | Disable two-factor authentication for a username. |
| `user:unsuspend` | Unsuspend a local user. |
| `user:verifyemail` | Verify a user's email address. |
