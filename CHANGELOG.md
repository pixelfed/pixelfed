# Release Notes

## [Unreleased](https://github.com/pixelfed/pixelfed/compare/v0.10.3...dev)

### Added

### Fixed
- Fixed count bug in StatusHashtagService [#1694](https://github.com/pixelfed/pixelfed/pull/1694)
- Fixed private account bug [#1699](https://github.com/pixelfed/pixelfed/pull/1699)

### Changed
- Updated EmailService, added new domains [#1690](https://github.com/pixelfed/pixelfed/pull/1690)
- Updated quill.js to v1.3.7 [#1692](https://github.com/pixelfed/pixelfed/pull/1690)

## Deprecated
- Remove deprecated profile following/followers [#1697](https://github.com/pixelfed/pixelfed/pull/1697)
    
    
## [v0.10.3 (2019-09-08)](https://github.com/pixelfed/pixelfed/compare/v0.10.2...v0.10.3)

### Added
- Append ```.json``` to local status urls to view ActivityPub object [#1666](https://github.com/pixelfed/pixelfed/pull/1666)

### Fixed
- Reverted ```strict``` Same-Site Cookies to ```null``` to fix 2FA/session expiry [#1667](https://github.com/pixelfed/pixelfed/pull/1667) 
- Fixed AP errors by storing ActivityPub object id and url [#1668](https://github.com/pixelfed/pixelfed/pull/1668) [#1683](https://github.com/pixelfed/pixelfed/pull/1683) 
- Fixed content warnings that had filter applied [#1669](https://github.com/pixelfed/pixelfed/pull/1669) 

### Changed
- Japanese Translations [#1673](https://github.com/pixelfed/pixelfed/pull/1673)
- Occitan Translations [#1679](https://github.com/pixelfed/pixelfed/pull/1679)
- Use footer partial on landing page [#1681](https://github.com/pixelfed/pixelfed/pull/1681)
- Change admin badge so it doesn't look like a verified badge [#1684](https://github.com/pixelfed/pixelfed/pull/1684)

### Deprecated
- Personalized Discover has been deprecated due to low use [#1670](https://github.com/pixelfed/pixelfed/pull/1670)
    

## [v0.10.2 (2019-09-06)](https://github.com/pixelfed/pixelfed/compare/v0.10.1...v0.10.2)

### Fixed

- Typo in Inbox prevented proper federation support


## [v0.10.1 (2019-09-06)](https://github.com/pixelfed/pixelfed/compare/v0.10.0...v0.10.1)

### Added
- Remote follows! Search for an actor URI, send AP Follow, plus handle incoming AP Accept Follow
- Compose UI v4: a rework of the v3 flow to allow basic cropping and better support future post types
- Profile badges show if a user is following you or is an admin
- Show confirmation message when muting or blocking a user from a post
- Allow "read more" to be disabled on posts
- Loops! Discover short videos
- Preliminary support for profile PropertyValue metadata
- Preliminary support for Direct Messages
- Places! Run the artisan task `import:cities` 
- Emails are now validated and banned email domains are disallowed at signup. Artisan task `email:bancheck` will validate existing users.
- .env vars `REDIS_SCHEME` and `REDIS_PATH` allow for using Redis over a Unix socket instead of TCP
- .env var `IMAGE_DRIVER` allows using imagick instead of gd

### Fixed
- Show delete button while composing video posts
- Show pending follow requests on private profiles
- Allow muted users to comment on your posts
- Bugs with carousel cursor and tooltips
- Collections can now be deleted from collection page
- Compose modal now indicates album media limits
- Unlisted and private posts are now delivered
- Don't show Register link in navbar when registrations are closed

### Changed
- Use vue-masonry for Moment UI layout
- User post limit changed from 20/hr to 50/hr
- Better mobile profile layout
- Dark mode is now a bit bluer
- Sample nginx.conf in contrib/ now uses HTTPS instead of HTTP. Docs updated to reference this file
- Updated register form
- Allow users to edit email after registrations
    

## [v0.10.0 (2019-07-17)](https://github.com/pixelfed/pixelfed/compare/v0.9.6...v0.10.0)

### Added
- Collections! Add posts to Collections, similar to categories.
- Profile donate links: add links to Patreon, Liberapay, and OpenCollective on your profile

### Fixed
- Show correct mode when viewing followers / following

### Changed
- Profile model now uses snowflake id

### Removed
- OStatus legacy code has been removed

## [v0.9.6 (2019-07-10)](https://github.com/pixelfed/pixelfed/compare/v0.9.5...v0.9.6)

### Fixed
- Hashtag post count off-by-one [#1485](https://github.com/pixelfed/pixelfed/pull/1485)
    

## [v0.9.5 (2019-07-10)](https://github.com/pixelfed/pixelfed/compare/v0.9.4...v0.9.5)

### Added
- Add StatusService [#1387](https://github.com/pixelfed/pixelfed/pull/1387), [425ec91](https://github.com/pixelfed/pixelfed/commit/425ec91)
- Add PublicTimelineService [#1387](https://github.com/pixelfed/pixelfed/pull/1387), [734e892](https://github.com/pixelfed/pixelfed/commit/734e892)
- Add RelationshipSettings trait [#1387](https://github.com/pixelfed/pixelfed/pull/1387), [bf8340f](https://github.com/pixelfed/pixelfed/commit/bf8340f)
- Add Remote Follows [#1388](https://github.com/pixelfed/pixelfed/pull/1388)
- Add Relationship Settings [#1388](https://github.com/pixelfed/pixelfed/pull/1388), [b10e03d](https://github.com/pixelfed/pixelfed/commit/b10e03d)
- Add Configuration Editor to Admin Dashboard [#1388](https://github.com/pixelfed/pixelfed/pull/1388), [323dca1](https://github.com/pixelfed/pixelfed/commit/323dca1)
- Add Migration, adding profile_id to users table [#1388](https://github.com/pixelfed/pixelfed/pull/1388), [bdfe633](https://github.com/pixelfed/pixelfed/commit/bdfe633)
- Add Media configuration [#1414](https://github.com/pixelfed/pixelfed/pull/1414)
- Add Content Warnings to comments [#1430](https://github.com/pixelfed/pixelfed/pull/1430), [42d81fc](https://github.com/pixelfed/pixelfed/commit/42d81fc) [8d4b3bd](https://github.com/pixelfed/pixelfed/commit/8d4b3bd) [73e162e4](https://github.com/pixelfed/pixelfed/commit/3e162e4)
- Add new rate limits [#1436](https://github.com/pixelfed/pixelfed/pull/1436) [1f1df2d](https://github.com/pixelfed/pixelfed/commit/1f1df2d)
- Add RegenerateThumbnails command to force thumbnail regeneration [#1437](https://github.com/pixelfed/pixelfed/pull/1437) [a3be4cd](https://github.com/pixelfed/pixelfed/commit/a3be4cd)
- Add Pages Editor to Admin Dashboard [#1438](https://github.com/pixelfed/pixelfed/pull/1438) [ef3e30d](https://github.com/pixelfed/pixelfed/commit/ef3e30d) [718375a](https://github.com/pixelfed/pixelfed/commit/718375a) [79524a0](https://github.com/pixelfed/pixelfed/commit/79524a0) [13ceef0](https://github.com/pixelfed/pixelfed/commit/13ceef0) [2fbcd6d](https://github.com/pixelfed/pixelfed/commit/2fbcd6d) [bb207a4](https://github.com/pixelfed/pixelfed/commit/bb207a4) [ef07e31](https://github.com/pixelfed/pixelfed/commit/ef07e31) [aca5114](https://github.com/pixelfed/pixelfed/commit/aca5114) [59fcfc2](https://github.com/pixelfed/pixelfed/commit/59fcfc2) [e3cfd81](https://github.com/pixelfed/pixelfed/commit/e3cfd81) [7ade78b](https://github.com/pixelfed/pixelfed/commit/7ade78b) [4539afa](https://github.com/pixelfed/pixelfed/commit/4539afa) [1dbfcae](https://github.com/pixelfed/pixelfed/commit/1dbfcae)

### Changed
- Update SearchController, fix AP verb typo [#1387](https://github.com/pixelfed/pixelfed/pull/1387), [dc8acf9](https://github.com/pixelfed/pixelfed/commit/dc8acf9)
- Update StatusTransformer, increase media cache ttl to 14 days [#1387](https://github.com/pixelfed/pixelfed/pull/1387), [f35718b](https://github.com/pixelfed/pixelfed/commit/f35718b)
- Update webpack config, extract vendor librarys [#1387](https://github.com/pixelfed/pixelfed/pull/1387), [b42db89](https://github.com/pixelfed/pixelfed/commit/b42db89)
- Update admin statuses view, make table header light [#1387](https://github.com/pixelfed/pixelfed/pull/1387), [44afcc7](https://github.com/pixelfed/pixelfed/commit/44afcc7)
- Update settings, move disable/delete to Security Settings [#1388](https://github.com/pixelfed/pixelfed/pull/1388), [ca0d638](https://github.com/pixelfed/pixelfed/commit/ca0d638)
- Update Installer command [#1388](https://github.com/pixelfed/pixelfed/pull/1388), [506dd8b](https://github.com/pixelfed/pixelfed/commit/506dd8b)
- Update UserObserver [#1388](https://github.com/pixelfed/pixelfed/pull/1388), [4ee3d10](https://github.com/pixelfed/pixelfed/commit/4ee3d10)
- Update AuthLogin listener [#1388](https://github.com/pixelfed/pixelfed/pull/1388), [c27c751](https://github.com/pixelfed/pixelfed/commit/c27c751) [1e8b092](https://github.com/pixelfed/pixelfed/commit/1e8b092)
- Update Image Optimization to not store EXIF by default [#1414](https://github.com/pixelfed/pixelfed/pull/1414)
- Update Settings, hide OAuth/Developer pages when not enabled [#1413](https://github.com/pixelfed/pixelfed/pull/1413)
- Update Presenter Components, move alt tag and filters to ```<img>``` element [#1415](https://github.com/pixelfed/pixelfed/pull/1415)
- Update Api Controllers, add missing caption limit to ```composePost()``` and missing ```is_nsfw``` attribute to comment queries [#1429](https://github.com/pixelfed/pixelfed/pull/1429), [1cff278](https://github.com/pixelfed/pixelfed/commit/1cff278)
- Update instances admin view, add scan button to find new instances [#1436](https://github.com/pixelfed/pixelfed/pull/1436) [a94a3ee](https://github.com/pixelfed/pixelfed/commit/a94a3ee)
- Update registration page, add links to terms and privacy pages [#1488](https://github.com/pixelfed/pixelfed/pull/1488)

### Removed
- Remove Classic Compose UI [#1434](https://github.com/pixelfed/pixelfed/pull/1434), [72bffd1](https://github.com/pixelfed/pixelfed/commit/72bffd1) [a2640af](https://github.com/pixelfed/pixelfed/commit/a2640af)
- 
    

## [v0.9.4 (2019-06-03)](https://github.com/pixelfed/pixelfed/compare/v0.9.0...v0.9.4)

PSA: Due to the removal of Google Recaptcha, a one-time manual intervention is required. Please try the following after installing with composer:

```
rm -rf bootstrap/cache/*
composer dump-autoload
php artisan config:cache
```

### Added
- Notification service
- Notification card on timeline
- Double-tap to like posts (no animation yet)
- Moderator Mode for timelines
- Emoji reaction bar
- Like and reply to comments
- Hello Loops! Short videos will now loop and be discoverable from the Discover page.
- Labs: Optional profile recommendations
- Labs: Show full caption instead of "read more" button
- Labs: Simple "distraction-free" timeline -- no buttons, just images and captions

### Changed
- Refactored notification view into a Vue component
- Preparations for Circles, DMs, and other upcoming functionality
- Default limit of 7500 follows
- Default limit of 20 follows per hour
- Default limit of 5 mentions per comment/caption
- Default limit of 30 hashtags per comment/caption
- Default limit of 2 links per comment/caption
- Thumbnail info overlays on profiles should now scale down to small screens (#1234)
- Moment UI containers are now properly sized (#1236)
- Album posts now have contrast for next/prev arrows (#1238)
- Filter previews now fit the image instead of stretching it (#1239)

### Removed
- Google Recaptcha is no longer supported (#1231)
- Lightbox has been deprecated in favor of double-tap-to-like; it will return as a dedicated button in the future (#1277)
    

## [v0.9.0 (2019-04-17)](https://github.com/pixelfed/pixelfed/compare/v0.8.6...v0.9.0)

### Added
- Allow users to delete existing profile photos.
- Preliminary support for managing developer tokens, as well as authorizing apps
- Unmute and unblock users more easily. Profiles now reflect muting/blocking status.
- Lazy-loading images with `loading="lazy"`, as supported in Blink
- Added Network Timeline which includes non-local posts
- Add broadcast events for real-time updates
- Compose view now shows upload progress bar
- You can now audit logged-in devices
- Added WIP installer
- Moment UI! This alternative profile view is less square and more full-width pictures.

### Changed
- Allow admins to view reported private posts
- Show sensitivity and privacy/audience in status views
- Cleanup of legacy code
- `commentsDisabled` has been replaced with preliminary support for Litepub Capability Enforcement (LiCE)
- `rel="me"` now added to profile websites
- Posts from locked accounts now default to followers-only

### Removed
- Removed identicons due to SVG compatibility issues with federation. New users will instead be assigned a default avatar.
    

## [v0.8.6 (2019-04-06)](https://github.com/pixelfed/pixelfed/compare/v0.8.5...v0.8.6)

### Added
- Add COSTAR - Confirm Object Sentiment Transform and Reduce

COSTAR is a filtering system that allows admins to define environment variables that will dynamically apply certain policies to posts of a defined scope, similar to Pleroma's MRF system.

Scopes:
- Domain: apply to posts from a specific website
- Actor: apply to posts from a specific profile/user
- Keyword: apply to posts containing a specific string

Policies:
- Block: Default blocks the defined scope
- CW: Automatically rewrites the scope to apply a warning
- Unlist: Removes the scope from public timelines



