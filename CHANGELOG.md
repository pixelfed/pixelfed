# Release Notes

## [Unreleased](https://github.com/pixelfed/pixelfed/compare/v0.10.9...dev)
### Added
- Direct Messages ([d63569c](https://github.com/pixelfed/pixelfed/commit/d63569c))
- ActivityPubFetchService for signed GET requests ([8763bfc5](https://github.com/pixelfed/pixelfed/commit/8763bfc5))
- Custom content warnings for remote posts ([6afc61a4](https://github.com/pixelfed/pixelfed/commit/6afc61a4))
- Thai translations ([74cd536](https://github.com/pixelfed/pixelfed/commit/74cd536))
- Added Bookmarks to v1 api ([99cb48c5](https://github.com/pixelfed/pixelfed/commit/99cb48c5))
- Added New Post notification to Timeline ([a0e7c4d5](https://github.com/pixelfed/pixelfed/commit/a0e7c4d5))
- Add Instagram Import ([e2a6bdd0](https://github.com/pixelfed/pixelfed/commit/e2a6bdd0))
- Add notification preview to NotificationCard ([28445e27](https://github.com/pixelfed/pixelfed/commit/28445e27))
- Add Grid Mode to Timelines ([c1853ca8](https://github.com/pixelfed/pixelfed/commit/c1853ca8))
- Add MediaPathService ([c54b29c5](https://github.com/pixelfed/pixelfed/commit/c54b29c5))
- Add Media Tags ([711fc020](https://github.com/pixelfed/pixelfed/commit/711fc020))
- Add MediaTagService ([524c6d45](https://github.com/pixelfed/pixelfed/commit/524c6d45))
- Add MediaBlocklist feature ([ba1f7e7e](https://github.com/pixelfed/pixelfed/commit/ba1f7e7e))
- New Discover Layout, add trending hashtags, places and posts ([c251d41b](https://github.com/pixelfed/pixelfed/commit/c251d41b))
- Add Password change email notification ([de1cca4f](https://github.com/pixelfed/pixelfed/commit/de1cca4f))
- Add shared inbox ([4733ca9f](https://github.com/pixelfed/pixelfed/commit/4733ca9f))
- Add federated photo filters ([0a5a0e86](https://github.com/pixelfed/pixelfed/commit/0a5a0e86))
- Add AccountInterstitial model and controller ([8766ccfe](https://github.com/pixelfed/pixelfed/commit/8766ccfe))

### Updated
- Updated PostComponent, fix remote urls ([42716ccc](https://github.com/pixelfed/pixelfed/commit/42716ccc))
- Updated PostComponent, fix missing like button on comments ([132c1dce](https://github.com/pixelfed/pixelfed/commit/132c1dce))
- Updated PostComponent.vue, fix load more comments button ([847599ad](https://github.com/pixelfed/pixelfed/commit/847599ad))
- Updated 2FA Checkpoint, add username + logout button and numeric inputmode ([26affb11](https://github.com/pixelfed/pixelfed/commit/26affb11))
- Updated RemoteProfile, fix missing content warnings ([e487527a](https://github.com/pixelfed/pixelfed/commit/e487527a))
- Updated RemotePost component, fix missing like button on comments ([7ef90565](https://github.com/pixelfed/pixelfed/commit/7ef90565))
- Updated PublicApiControllers, fix block/mutes filtering on public timeline ([08383dd4](https://github.com/pixelfed/pixelfed/commit/08383dd4))
- Updated FixUsernames command, fixes remote username search ([0f943f67](https://github.com/pixelfed/pixelfed/commit/0f943f67))
- Updated Timeline component, fix mod tools ([b1d5eb05](https://github.com/pixelfed/pixelfed/commit/b1d5eb05))
- Updated Profile.vue component, fix pagination bug ([46767810](https://github.com/pixelfed/pixelfed/commit/46767810))
- Updated purify config, fix microformats support ([877023fb](https://github.com/pixelfed/pixelfed/commit/877023fb))
- Updated LikeController, fix likes_count bug ([996866cb](https://github.com/pixelfed/pixelfed/commit/996866cb))
- Updated AccountController, added followRequestJson method ([483548e2](https://github.com/pixelfed/pixelfed/commit/483548e2))
- Updated UserInvite model, added sender relation ([591a1929](https://github.com/pixelfed/pixelfed/commit/591a1929))
- Updated migrations, added UIKit ([fcab5010](https://github.com/pixelfed/pixelfed/commit/fcab5010))
- Updated AccountTransformer, added last_fetched_at attribute ([38b0233e](https://github.com/pixelfed/pixelfed/commit/38b0233e))
- Updated StoryItemTransformer, increase story length to 5 seconds ([924e424c](https://github.com/pixelfed/pixelfed/commit/924e424c))
- Updated StatusController, fix reblog_count bug ([1dc65e93](https://github.com/pixelfed/pixelfed/commit/1dc65e93))
- Updated NotificationCard.vue component, add follow requests at top of card, remove card-header ([5e48ffca](https://github.com/pixelfed/pixelfed/commit/5e48ffca))
- Updated RemoteProfile.vue component, add warning for empty profiles and last_fetched_at ([66f44a9d](https://github.com/pixelfed/pixelfed/commit/66f44a9d))
- Updated ApiV1Controller, enforce public timeline setting ([285bd485](https://github.com/pixelfed/pixelfed/commit/285bd485))
- Updated SearchController, fix self search bug and rank local matches higher ([f67fada2](https://github.com/pixelfed/pixelfed/commit/f67fada2))
- Updated FederationController, improve webfinger logic, fixes ([#2180](https://github.com/pixelfed/pixelfed/issues/2180)) ([302ff874](https://github.com/pixelfed/pixelfed/commit/302ff874))
- Updated ApiV1Controller, fix broken auth check on public timelines. Fixes ([#2168](https://github.com/pixelfed/pixelfed/issues/2168)) ([aa49afc7](https://github.com/pixelfed/pixelfed/commit/aa49afc7))
- Updated SearchApiV2Service, fix offset bug ([#2116](https://github.com/pixelfed/pixelfed/issues/2116)) ([a0c0c84d](https://github.com/pixelfed/pixelfed/commit/a0c0c84d))
- Updated api routes, fixes ([#2114](https://github.com/pixelfed/pixelfed/issues/2114)) ([50bbeddd](https://github.com/pixelfed/pixelfed/commit/50bbeddd))
- Updated SiteController, add legacy profile/webfinger redirect ([cfaa248c](https://github.com/pixelfed/pixelfed/commit/cfaa248c))
- Updated checkpoint view, fix recovery code bug ([3385583f](https://github.com/pixelfed/pixelfed/commit/3385583f))
- Updated Inbox, move expensive HTTP Signature validation to job queue ([f2ae45e5a](https://github.com/pixelfed/pixelfed/commit/f2ae45e5a))
- Updated MomentUI, fix bugs and improve UI ([90b89cb8](https://github.com/pixelfed/pixelfed/commit/90b89cb8))
- Updated PostComponent, improve embed model. Fixes ([#2189](https://github.com/pixelfed/pixelfed/issues/2189)) ([b12e504e](https://github.com/pixelfed/pixelfed/commit/b12e504e))
- Updated PostComponent, hide edit button after 24 hours. Fixes ([#2188](https://github.com/pixelfed/pixelfed/issues/2188)) ([a1fee6a2](https://github.com/pixelfed/pixelfed/commit/a1fee6a2))
- Updated AP Inbox, add follow notifications ([b8819fbb](https://github.com/pixelfed/pixelfed/commit/b8819fbb))
- Updated Api Transformers, fixes ([#2234](https://github.com/pixelfed/pixelfed/issues/2234)) ([63007891](https://github.com/pixelfed/pixelfed/commit/63007891))
- Updated ApiV1Controller, fix instance endpoint ([#2233](https://github.com/pixelfed/pixelfed/issues/2233)) ([b7ee9981](https://github.com/pixelfed/pixelfed/commit/b7ee9981))
- Updated AP Inbox, remove trailing comma ([5c443548](https://github.com/pixelfed/pixelfed/commit/5c443548))
- Updated AP Helpers, update bio + name ([4bee8397](https://github.com/pixelfed/pixelfed/commit/4bee8397))
- Updated Profile component, add bookmark loader ([c8d5edc9](https://github.com/pixelfed/pixelfed/commit/c8d5edc9))
- Updated PostComponent, add recent posts ([b289f2f6](https://github.com/pixelfed/pixelfed/commit/b289f2f6))
- Updated ApiV1Controller, add status ancestor and descendant context ([a0bde855](https://github.com/pixelfed/pixelfed/commit/a0bde855))
- Updated NotificationCard, improve popover image scaling ([0153e596](https://github.com/pixelfed/pixelfed/commit/0153e596))
- Updated StoryController, fix deprecated getClientSize() use ([725fc6c6](https://github.com/pixelfed/pixelfed/commit/725fc6c6))
- Updated ComposeModal, fix rotate icon direction. Fixes ([#2241](https://github.com/pixelfed/pixelfed/issues/2241)) ([e8a14640](https://github.com/pixelfed/pixelfed/commit/e8a14640))
- Updated Timeline.vue, add profile links to grid mode ([fa40f51b](https://github.com/pixelfed/pixelfed/commit/fa40f51b))
- Updated Timeline.vue, hide like counts on grid mode. Fixes ([#2293](https://github.com/pixelfed/pixelfed/issues/2293)) ([cc18159f](https://github.com/pixelfed/pixelfed/commit/cc18159f))
- Updated Timeline.vue, make grid mode photos clickable. Fixes ([#2292](https://github.com/pixelfed/pixelfed/issues/2292)) ([6db68184](https://github.com/pixelfed/pixelfed/commit/6db68184))
- Updated ComposeModal.vue, use vue tooltips. Fixes ([#2142](https://github.com/pixelfed/pixelfed/issues/2142)) ([2b753123](https://github.com/pixelfed/pixelfed/commit/2b753123))
- Updated AccountController, prevent blocking admins. ([2c440b48](https://github.com/pixelfed/pixelfed/commit/2c440b48))
- Updated Api controllers to use MediaPathService. ([58864212](https://github.com/pixelfed/pixelfed/commit/58864212))
- Updated notification components, add modlog and tagged notification types ([51862b8b](https://github.com/pixelfed/pixelfed/commit/51862b8b))
- Updated StoryController, allow video stories. ([b3b220b9](https://github.com/pixelfed/pixelfed/commit/b3b220b9))
- Updated InternalApiController, add media tags. ([ee93f459](https://github.com/pixelfed/pixelfed/commit/ee93f459))
- Updated ComposeModal.vue, add media tagging. ([421ea022](https://github.com/pixelfed/pixelfed/commit/421ea022))
- Updated NotificationTransformer, add modlog and tagged types. ([49dab6fb](https://github.com/pixelfed/pixelfed/commit/49dab6fb))
- Updated comments, fix remote reply bug. ([f330616](https://github.com/pixelfed/pixelfed/commit/f330616))
- Updated PostComponent, add tagged people to mobile layout. ([7a2c2e78](https://github.com/pixelfed/pixelfed/commit/7a2c2e78))
- Updated Tag People, allow untagging yourself. ([c9452639](https://github.com/pixelfed/pixelfed/commit/c9452639))
- Updated ComposeModal.vue, add 451 http code warning. ([b213dcda](https://github.com/pixelfed/pixelfed/commit/b213dcda))
- Updated Profile.vue, add empty follower modal placeholder. ([b542a3c5](https://github.com/pixelfed/pixelfed/commit/b542a3c5))
- Updated private profiles, add context menu to mute, block or report. ([487c4ffc](https://github.com/pixelfed/pixelfed/commit/487c4ffc))
- Updated webfinger util, fix bug preventing username with dots. ([c2d194af](https://github.com/pixelfed/pixelfed/commit/c2d194af))
- Updated upload endpoints with MediaBlocklist checks. ([597378bf](https://github.com/pixelfed/pixelfed/commit/597378bf))
- Updated Timeline.vue component, fixes ([#2352](https://github.com/pixelfed/pixelfed/issues/2352)) and ([#2343](https://github.com/pixelfed/pixelfed/issues/2343)). ([e134a9ac](https://github.com/pixelfed/pixelfed/commit/e134a9ac))
- Updated PostComponent.vue, improve MetroUI and fixes ([#2363](https://github.com/pixelfed/pixelfed/issues/2363)). ([0c8ebf26](https://github.com/pixelfed/pixelfed/commit/0c8ebf26))
- Updated Timeline.vue, fixes ([#2363](https://github.com/pixelfed/pixelfed/issues/2363)). ([f53f10fd](https://github.com/pixelfed/pixelfed/commit/f53f10fd))
- Updated Profile.vue, add atom feed link to context menu. Fixes ([#2313](https://github.com/pixelfed/pixelfed/issues/2313)). ([89f29072](https://github.com/pixelfed/pixelfed/commit/89f29072))
- Updated Hashtag.vue, add nsfw toggle. Fixes ([#2225](https://github.com/pixelfed/pixelfed/issues/2225)). ([e5aa506c](https://github.com/pixelfed/pixelfed/commit/e5aa506c))
- Updated Timeline.vue, move compose button. ([9cad8f77](https://github.com/pixelfed/pixelfed/commit/9cad8f77))
- Updated status embed, allow photo albums. Fixes ([#2374](https://github.com/pixelfed/pixelfed/issues/2374)). ([d11fac0d](https://github.com/pixelfed/pixelfed/commit/d11fac0d))
- Updated DiscoverController, fixes ([#2378](https://github.com/pixelfed/pixelfed/issues/2378)). ([8e7f4f9d](https://github.com/pixelfed/pixelfed/commit/8e7f4f9d))
- Updated SearchController, update version. ([8d923d77](https://github.com/pixelfed/pixelfed/commit/8d923d77))
- Updated email confirmation middleware, add 2FA to allow list. Fixes ([#2385](https://github.com/pixelfed/pixelfed/issues/2385)). ([27f3b29c](https://github.com/pixelfed/pixelfed/commit/27f3b29c))
- Updated NotificationTransformer, fixes ([#2389](https://github.com/pixelfed/pixelfed/issues/2389)). ([c4506ebd](https://github.com/pixelfed/pixelfed/commit/c4506ebd))
- Updated Profile + Timeline components, simplify UI. ([38d28ab4](https://github.com/pixelfed/pixelfed/commit/38d28ab4))
- Updated Profile component, make modals scrollable. ([d1c664fa](https://github.com/pixelfed/pixelfed/commit/d1c664fa))
- Updated PostComponent, fixes #2351. ([7a62a42a](https://github.com/pixelfed/pixelfed/commit/7a62a42a))
- Updated DirectMessageController, fix pgsql bug. ([f1c28e7d](https://github.com/pixelfed/pixelfed/commit/f1c28e7d))
- Updated RegisterController, make the minimum user password length configurable. ([09479c02](https://github.com/pixelfed/pixelfed/commit/09479c02))
- Updated AuthServiceProvider, added support for configurable OAuth tokens and refresh tokens lifetime. ([7cfae612](https://github.com/pixelfed/pixelfed/commit/7cfae612))
- Updated EmailService, make case insensitive. ([1b41d664](https://github.com/pixelfed/pixelfed/commit/1b41d664))
- Updated DiscoverController, fix trending api. ([2ab2c9a](https://github.com/pixelfed/pixelfed/commit/2ab2c9a))
- Updated Dark Mode layout. ([d6f8170](https://github.com/pixelfed/pixelfed/commit/d6f8170))
- Updated federation config, make sharedInbox enabled by default. ([6e3522c0](https://github.com/pixelfed/pixelfed/commit/6e3522c0))
- Updated PostComponent, change timestamp format. ([e51665f6](https://github.com/pixelfed/pixelfed/commit/e51665f6))
- Updated PostComponent, use proper username context for reply mentions. Fixes ([#2421](https://github.com/pixelfed/pixelfed/issues/2421)). ([dac06088](https://github.com/pixelfed/pixelfed/commit/dac06088))
- Updated Navbar, added profile avatar. ([19abf1b4](https://github.com/pixelfed/pixelfed/commit/19abf1b4))
- Updated package.json, add blurhash. ([cc1b081a](https://github.com/pixelfed/pixelfed/commit/cc1b081a))

## [v0.10.9 (2020-04-17)](https://github.com/pixelfed/pixelfed/compare/v0.10.8...v0.10.9)
### Added
- Added Profile Following Search ([e3280c11](https://github.com/pixelfed/pixelfed/commit/e3280c11))
- Added Trusted Devices to Sudo Mode ([0c82c970](https://github.com/pixelfed/pixelfed/commit/0c82c970))
- Added reply modal to posts and timelines ([974e6bda](https://github.com/pixelfed/pixelfed/commit/974e6bda))
- Added remote posts and profiles ([95bce31e](https://github.com/pixelfed/pixelfed/commit/95bce31e))
- Added Labs deprecation page ([9b215001](https://github.com/pixelfed/pixelfed/commit/9b215001))
- Added new landing page ([84e203a9](https://github.com/pixelfed/pixelfed/commit/84e203a9))

### Fixed
- Stories on postgres instances ([5ffa71da](https://github.com/pixelfed/pixelfed/commit/5ffa71da))

### Updated
- Updated StatusController, restrict edits to 24 hours ([ae24433b](https://github.com/pixelfed/pixelfed/commit/ae24433b))
- Updated RateLimit, add max post edits per hour and day ([51fbfcdc](https://github.com/pixelfed/pixelfed/commit/51fbfcdc))
- Updated Timeline.vue, move announcements from sidebar to top of timeline ([228f5044](https://github.com/pixelfed/pixelfed/commit/228f5044))
- Updated lexer autolinker and extractor, add support for mentioned usernames containing dashes, periods and underscore characters ([f911c96d](https://github.com/pixelfed/pixelfed/commit/f911c96d))
- Updated Story apis, move FE to v0 and add v1 for oauth clients ([92654fab](https://github.com/pixelfed/pixelfed/commit/92654fab))
- Updated robots.txt ([25101901](https://github.com/pixelfed/pixelfed/commit/25101901))
- Updated mail panel blade view, fix markdown bug ([cbc63b04](https://github.com/pixelfed/pixelfed/commit/cbc63b04))
- Updated self-diagnosis checks ([03f808c7](https://github.com/pixelfed/pixelfed/commit/03f808c7))
- Updated DiscoverController, fixes #2009 ([b04c7170](https://github.com/pixelfed/pixelfed/commit/b04c7170))
- Updated DeleteAccountPipeline, fixes [#2016](https://github.com/pixelfed/pixelfed/issues/2016), a bug affecting account deletion.
- Updated PlaceController, fixes [#2017](https://github.com/pixelfed/pixelfed/issues/2017), a postgres bug affecting country pagination in the places directory ([dd5fa3a4](https://github.com/pixelfed/pixelfed/commit/dd5fa3a4))
- Updated confirm email blade view, remove html5 entity that doesn't display properly ([aa26fa1d](https://github.com/pixelfed/pixelfed/commit/aa26fa1d))
- Updated ApiV1Controller, fix update_credentials endpoint ([a73fad75](https://github.com/pixelfed/pixelfed/commit/a73fad75))
- Updated AdminUserController, add moderation method ([a4cf21ea](https://github.com/pixelfed/pixelfed/commit/a4cf21ea))
- Updated BaseApiController, invalidate session after account deletion ([826978ce](https://github.com/pixelfed/pixelfed/commit/826978ce))
- Updated AdminUserController, add account deletion handler ([9be19ad8](https://github.com/pixelfed/pixelfed/commit/9be19ad8))
- Updated ContactController, fixes [#2042](https://github.com/pixelfed/pixelfed/issues/2042) ([c9057e87](https://github.com/pixelfed/pixelfed/commit/c9057e87))
- Updated Media model, fix remote media preview ([9947050b](https://github.com/pixelfed/pixelfed/commit/9947050b))
- Updated PostComponent, improve likes modal ([664fd272](https://github.com/pixelfed/pixelfed/commit/664fd272))
- Updated StoryViewer, preload media ([336571d0](https://github.com/pixelfed/pixelfed/commit/336571d0))
- Updated StoryCompose, add expand label for lightbox preview ([fdf59753](https://github.com/pixelfed/pixelfed/commit/fdf59753))
- Updated session config, increase session timeout from 2 days to 60 days ([b8795271](https://github.com/pixelfed/pixelfed/commit/b8795271))
- Updated WebfingerService, cache lookup ([8b9faf31](https://github.com/pixelfed/pixelfed/commit/8b9faf31))
- Updated v1 notifications api, fix optional params ([4e3c952c](https://github.com/pixelfed/pixelfed/commit/4e3c952c))
- Updated ApiV1Controller, fix unfavourite bug [#2088](https://github.com/pixelfed/pixelfed/issues/2088) ([3a828522](https://github.com/pixelfed/pixelfed/commit/3a828522))
- Updated SharePipeline, fix item relation bug ([b5899648](https://github.com/pixelfed/pixelfed/commit/b5899648))
- Updated Profile.vue, add v-once to thumbnails to prevent re-render ([a54685f6](https://github.com/pixelfed/pixelfed/commit/a54685f6))
- Updated SearchResults.vue, improve layout ([7e41b4ae](https://github.com/pixelfed/pixelfed/commit/7e41b4ae))
- Updated PostMenu.vue, fix styling of list-group ([4c3b0b7d](https://github.com/pixelfed/pixelfed/commit/4c3b0b7d))
- Updated PostComponent.vue, update styling ([844566b9](https://github.com/pixelfed/pixelfed/commit/844566b9))
- Updated NotificationCard.vue, fix share notifications ([3cb676b1](https://github.com/pixelfed/pixelfed/commit/3cb676b1))
- Updated PostComponent.vue, remove like count from title, fixes [#2091](https://github.com/pixelfed/pixelfed/issues/2091) ([6026998c](https://github.com/pixelfed/pixelfed/commit/6026998c))
- Updated SearchController, add WebfingerService support ([869b4ff7](https://github.com/pixelfed/pixelfed/commit/869b4ff7))
- Updated Profile model, use change_count for version ([0eae9f8b](https://github.com/pixelfed/pixelfed/commit/0eae9f8b))
- Updated Timeline.vue, add remote post/profile links ([d4147083](https://github.com/pixelfed/pixelfed/commit/d4147083))
- Updated StoryTimelineComponent, added list prop for new timeline layout ([1692a95a](https://github.com/pixelfed/pixelfed/commit/1692a95a))
- Updated blank layout, add sharedData js ([4a293ed9](https://github.com/pixelfed/pixelfed/commit/4a293ed9))
- Updated oauth api, allow multiple redirect_uris. Fixes #[2106](https://github.com/pixelfed/pixelfed/issues/2106) ([0540a28a](https://github.com/pixelfed/pixelfed/commit/0540a28a))
- Updated ActivityPub Outbox, fixes #[2100](https://github.com/pixelfed/pixelfed/issues/2100) ([c84cee5a](https://github.com/pixelfed/pixelfed/commit/c84cee5a))
- Updated ApiV1Controller, fixes #[2112](https://github.com/pixelfed/pixelfed/issues/2112) ([324ccd0a](https://github.com/pixelfed/pixelfed/commit/324ccd0a))
- Updated StatusTransformer, fixes #[2113](https://github.com/pixelfed/pixelfed/issues/2113) ([eefa6e0d](https://github.com/pixelfed/pixelfed/commit/eefa6e0d))
- Updated InternalApiController, limit remote profile ui to remote profiles ([d918a68e](https://github.com/pixelfed/pixelfed/commit/d918a68e))
- Updated NotificationCard, fix pagination bug #[2019](https://github.com/pixelfed/pixelfed/issues/2019) ([32beaad5](https://github.com/pixelfed/pixelfed/commit/32beaad5))
- 

## [v0.10.8 (2020-01-29)](https://github.com/pixelfed/pixelfed/compare/v0.10.7...v0.10.8)
### Added
- Added ```BANNED_USERNAMES``` .env var, an optional comma separated string to ban specific usernames from being used ([6cdd64c6](https://github.com/pixelfed/pixelfed/commit/6cdd64c6))
- Added RestrictedAccess middleware for Restricted Mode ([17c1a83d](https://github.com/pixelfed/pixelfed/commit/17c1a83d))
- Added FailedJob garbage collection ([5d424f12](https://github.com/pixelfed/pixelfed/commit/5d424f12))
- Added Password Reset garbage collection ([829c41e1](https://github.com/pixelfed/pixelfed/commit/829c41e1))

### Fixed
- Fixed Story Compose bug affecting postgres instances ([#1918](https://github.com/pixelfed/pixelfed/pull/1918))
- Fixed header background bug on MomentUI profiles ([#1933](https://github.com/pixelfed/pixelfed/pull/1933))
- Fixed TRUST_PROXIES configuration ([#1941](https://github.com/pixelfed/pixelfed/pull/1941))
- Fixed settings page default language ([4223a11e](https://github.com/pixelfed/pixelfed/commit/4223a11e))
- Fixed DeleteAccountPipeline bug that did not use proper media paths ([578d2f35](https://github.com/pixelfed/pixelfed/commit/578d2f35))
- Fixed mastoapi StatusTransformer, fix in_reply_to_id cast to string instead of int ([6ed00c94](https://github.com/pixelfed/pixelfed/commit/6ed00c94))

### Updated
- Updated presenter components, load fallback image on errors ([273170c5](https://github.com/pixelfed/pixelfed/commit/273170c5))
- Updated Story model, hide json attribute by default ([de89403c](https://github.com/pixelfed/pixelfed/commit/de89403c))
- Updated compose view, add deprecation notice for v3 ([57e155b9](https://github.com/pixelfed/pixelfed/commit/57e155b9))
- Updated StoryController, orientate story media and strip exif ([07a13fcf](https://github.com/pixelfed/pixelfed/commit/07a13fcf))
- Updated admin reports, fixed 404 bug ([dbd5c4cf](https://github.com/pixelfed/pixelfed/commit/dbd5c4cf))
- Updated AdminController, abstracted dashboard stats to AdminStatsService ([41abe9d2](https://github.com/pixelfed/pixelfed/commit/41abe9d2))
- Updated StoryCompose component, added upload progress page ([2de3c56f](https://github.com/pixelfed/pixelfed/commit/2de3c56f))
- Updated instance config, cleanup and add restricted mode ([3be32597](https://github.com/pixelfed/pixelfed/commit/3be32597))
- Update RelationshipSettings Controller, fixes #1605 ([4d2da2f1](https://github.com/pixelfed/pixelfed/commit/4d2da2f1))
- Updated password reset, now expires after 24 hours ([829c41e1](https://github.com/pixelfed/pixelfed/commit/829c41e1))
- Updated nav layout ([73249dc2](https://github.com/pixelfed/pixelfed/commit/73249dc2))
- Updated views with noscript warnings ([eaca43a6](https://github.com/pixelfed/pixelfed/commit/eaca43a6))

### Changed

## [v0.10.7 (2020-01-07)](https://github.com/pixelfed/pixelfed/compare/v0.10.6...v0.10.7)

### Added
- Added drafts API endpoint for Camera Roll ([bad2ecde](https://github.com/pixelfed/pixelfed/commit/bad2ecde))
- Added AccountService ([885a1258](https://github.com/pixelfed/pixelfed/commit/885a1258))
- Added post embeds ([1fecf717](https://github.com/pixelfed/pixelfed/commit/1fecf717))
- Added profile embeds ([fb7a3cf0](https://github.com/pixelfed/pixelfed/commit/fb7a3cf0))
- Added Force MetroUI labs experiment ([#1889](https://github.com/pixelfed/pixelfed/pull/1889))
- Added Stories, to enable add ```STORIES_ENABLED=true``` to ```.env```	 and run ```php artisan config:cache && php artisan cache:clear```. If opcache is enabled you may need to reload the web server.

### Fixed
- Fixed like and share/reblog count on profiles ([86cb7d09](https://github.com/pixelfed/pixelfed/commit/86cb7d09))
- Fixed non federating self boosts ([0c59a55e](https://github.com/pixelfed/pixelfed/commit/0c59a55e))
- Fixed CORS issues with API endpoints ([6d6f517d](https://github.com/pixelfed/pixelfed/commit/6d6f517d))
- Fixed mixed albums not appearing on timelines ([e01dff45](https://github.com/pixelfed/pixelfed/commit/e01dff45))

### Changed
- Removed ```relationship``` from ```AccountTransformer``` ([4d084ac5](https://github.com/pixelfed/pixelfed/commit/4d084ac5))
- Updated ```notification``` api endpoint to use ```NotificationService``` ([f4039ce2](https://github.com/pixelfed/pixelfed/commit/f4039ce2)) ([6ef7597](https://github.com/pixelfed/pixelfed/commit/6ef7597))
- Update footer to use localization for the ```Places``` link ([39712714](https://github.com/pixelfed/pixelfed/commit/39712714))
- Updated ComposeModal.vue, added a caption counter. Fixes [#1722](https://github.com/pixelfed/pixelfed/issues/1722). ([009c6ee8](https://github.com/pixelfed/pixelfed/commit/009c6ee8))
- Updated Notifications to use the NotificationService ([f4039ce2](https://github.com/pixelfed/pixelfed/commit/f4039ce218f93a5578225dfdba66f0359c8fc72c))
- Updated PrivacySettings controller, clear cache after updating ([d8d11d7b](https://github.com/pixelfed/pixelfed/commit/d8d11d7b))
- Updated BaseApiController, add timestamp to signed media previews for client side cache invalidation ([73c08987](https://github.com/pixelfed/pixelfed/commit/73c08987))
- Updated AdminInstanceController, remove db transaction from instance scan ([5773434a](https://github.com/pixelfed/pixelfed/commit/5773434a))
- Updated Help Center view, added outdated warning ([0e611d00](https://github.com/pixelfed/pixelfed/commit/0e611d00))
- Updated language view, added English version of language names ([ebb998d2](https://github.com/pixelfed/pixelfed/commit/ebb998d2))
- Updated app.js, added App.utils like ```.format.count```, ```.filters``` and ```.emoji``` ([34c13b6e](https://github.com/pixelfed/pixelfed/commit/34c13b6e))
- Updated CollectionCompose.vue component, fix api namespace change ([71ed965c](https://github.com/pixelfed/pixelfed/commit/71ed965c))
- Updated PostComponent, mark caption sensitive if post is and use util.emoji ([35d51215](https://github.com/pixelfed/pixelfed/commit/35d51215))
- Updated Profile.vue component, use formatted counts ([30f14961](https://github.com/pixelfed/pixelfed/commit/30f14961))
- Updated Timeline.vue component, use formatted counts, util.emoji and increase pagination limit to 5 ([abfc9fe7](https://github.com/pixelfed/pixelfed/commit/abfc9fe7))
- Updated album presenters, use better carousel ([31b114cc](https://github.com/pixelfed/pixelfed/commit/31b114cc)) ([0617fada](https://github.com/pixelfed/pixelfed/commit/0617fada)) ([767fc887](https://github.com/pixelfed/pixelfed/commit/767fc887))
- Updated Timeline.vue component, remove tap for lightbox as it conflicts with new carousel ([96e25ad2](https://github.com/pixelfed/pixelfed/commit/96e25ad2))
- Updated ComposeModal.vue, added album support, editing and UI tweaks ([3aaad81e](https://github.com/pixelfed/pixelfed/commit/3aaad81e))
- Updated InternalApiController, increase license limit to 140 to match UI counter ([b3c18aec](https://github.com/pixelfed/pixelfed/commit/b3c18aec))
- Updated album carousels, fix height bug ([8380822a](https://github.com/pixelfed/pixelfed/commit/8380822a))
- Updated MediaController, add timestamp to signed preview url ([49efaae9](https://github.com/pixelfed/pixelfed/commit/49efaae9))
- Updated BaseApiController, uncache verify_credentials method ([3fa9ac8b](https://github.com/pixelfed/pixelfed/commit/3fa9ac8b))
- Updated StatusHashtagService, reduce cached hashtag count ttl from 6 hours to 5 minutes ([126886e8](https://github.com/pixelfed/pixelfed/commit/126886e8))
- Updated Hashtag.vue component, added formatted posts count ([c71f3dd1](https://github.com/pixelfed/pixelfed/commit/c71f3dd1))
- Updated FixLikes command, fix postgres support ([771f9c46](https://github.com/pixelfed/pixelfed/commit/771f9c46))
- Updated Settings, hide sponsors feature until re-implemented in Profile UI ([c4dd8449](https://github.com/pixelfed/pixelfed/commit/c4dd8449))
- Updated Status view, added ```video``` open graph tag support ([#1799](https://github.com/pixelfed/pixelfed/pull/1799))
- Updated AccountTransformer, added ```local``` attribute ([d2a90f11](https://github.com/pixelfed/pixelfed/commit/d2a90f11))
- Updated Laravel framework from v5.8 to v6.x ([3aff6de33](https://github.com/pixelfed/pixelfed/commit/3aff6de33))
- Updated FollowerController to fix bug affecting private profiles ([a429d961](https://github.com/pixelfed/pixelfed/commit/a429d961))
- Updated StatusTransformer, added ```local``` attribute ([484bb509](https://github.com/pixelfed/pixelfed/commit/484bb509))
- Updated PostComponent, fix bug affecting MomentUI and non authenticated users ([7b3fe215](https://github.com/pixelfed/pixelfed/commit/7b3fe215))
- Updated FixUsernames command to allow usernames containing ```.``` ([e5d77c6d](https://github.com/pixelfed/pixelfed/commit/e5d77c6d))
- Updated landing page, add age check ([d11e82c3](https://github.com/pixelfed/pixelfed/commit/d11e82c3))
- Updated ApiV1Controller, add ```mobile_apis``` to /api/v1/instance endpoint ([57407463](https://github.com/pixelfed/pixelfed/commit/57407463))
- Updated PublicTimelineService, add video media scopes ([7b00eba3](https://github.com/pixelfed/pixelfed/commit/7b00eba3))
- Updated PublicApiController, add AccountService ([5ebd2c8a](https://github.com/pixelfed/pixelfed/commit/5ebd2c8a))
- Updated CommentController, fix scope bug ([45ecad2a](https://github.com/pixelfed/pixelfed/45ecad2a))
- Updated CollectionController, increase limit from 18 to 50. ([c2826fd3](https://github.com/pixelfed/pixelfed/c2826fd3))

## Deprecated
    

## [v0.10.6 (2019-09-30)](https://github.com/pixelfed/pixelfed/compare/v0.10.5...v0.10.6)

### Added
- Added ```/api/v1/accounts/update_credentials``` endpoint [6afd6970](https://github.com/pixelfed/pixelfed/commit/6afd6970)
- Added ```/api/v1/accounts/{id}/followers``` endpoint [41c91cba](https://github.com/pixelfed/pixelfed/commit/41c91cba)
- Added ```/api/v1/accounts/{id}/following``` endpoint [607eb51b](https://github.com/pixelfed/pixelfed/commit/607eb51b)
- Added ```/api/v1/accounts/{id}/statuses``` endpoint [8ce6c1f2](https://github.com/pixelfed/pixelfed/commit/8ce6c1f2)
- Added ```/api/v1/accounts/{id}/follow``` endpoint [f3839026](https://github.com/pixelfed/pixelfed/commit/f3839026)
- Added ```/api/v1/accounts/{id}/unfollow``` endpoint [fadc96b2](https://github.com/pixelfed/pixelfed/commit/fadc96b2)
- Added ```/api/v1/accounts/relationships``` endpoint [4b9f7d6b](https://github.com/pixelfed/pixelfed/commit/4b9f7d6b)
- Added ```/api/v1/accounts/search``` endpoint [b1fccf6d](https://github.com/pixelfed/pixelfed/commit/b1fccf6d)
- Added ```/api/v1/blocks``` endpoint [ac9f1bc0](https://github.com/pixelfed/pixelfed/commit/ac9f1bc0)
- Added ```/api/v1/accounts/{id}/block``` endpoint [c6b1ed97](https://github.com/pixelfed/pixelfed/commit/c6b1ed97)
- Added ```/api/v1/accounts/{id}/unblock``` endpoint [35226c99](https://github.com/pixelfed/pixelfed/commit/35226c99)
- Added ```/api/v1/custom_emojis``` endpoint [6e43431a](https://github.com/pixelfed/pixelfed/commit/6e43431a)
- Added ```/api/v1/domain_blocks``` endpoint [83a6313f](https://github.com/pixelfed/pixelfed/commit/83a6313f)
- Added ```/api/v1/endorsements``` endpoint [1f16221e](https://github.com/pixelfed/pixelfed/commit/1f16221e)
- Added ```/api/v1/favourites``` endpoint [b9cc06da](https://github.com/pixelfed/pixelfed/commit/b9cc06da)
- Added ```/api/v1/statuses/{id}/favourite``` endpoint [4edeba17](https://github.com/pixelfed/pixelfed/commit/4edeba17)
- Added ```/api/v1/statuses/{id}/unfavourite``` endpoint [437e18e3](https://github.com/pixelfed/pixelfed/commit/437e18e3)
- Added ```/api/v1/filters``` endpoint [b3d82edd](https://github.com/pixelfed/pixelfed/commit/b3d82edd)
- Added ```/api/v1/follow_requests``` endpoint [97269136](https://github.com/pixelfed/pixelfed/commit/97269136)
- Added ```/api/v1/follow_requests/{id}/authorize``` endpoint [7bdd9b2a](https://github.com/pixelfed/pixelfed/commit/7bdd9b2a)
- Added ```/api/v1/follow_requests/{id}/reject``` endpoint [62aa922a](https://github.com/pixelfed/pixelfed/commit/62aa922a)
- Added ```/api/v1/suggestions``` endpoint [e52aeeed](https://github.com/pixelfed/pixelfed/commit/e52aeeed)
- Added ```/api/v1/lists``` endpoint [2a106c4e](https://github.com/pixelfed/pixelfed/commit/2a106c4e)
- Added ```/api/v1/accounts/{id}/lists``` endpoint [dba172df](https://github.com/pixelfed/pixelfed/commit/dba172df)
- Added ```/api/v1/lists/{id}/accounts``` endpoint [dba172df](https://github.com/pixelfed/pixelfed/commit/dba172df)
- Added ```/api/v1/media``` endpoint [39f3e313](https://github.com/pixelfed/pixelfed/commit/39f3e313)
- Added ```/api/v1/media/{id}``` endpoint [fcf231f4](https://github.com/pixelfed/pixelfed/commit/fcf231f4)
- Added ```/api/v1/mutes``` endpoint [b280d183](https://github.com/pixelfed/pixelfed/commit/b280d183)
- Added ```/api/v1/accounts/{id}/mute``` endpoint [3e98dce4](https://github.com/pixelfed/pixelfed/commit/3e98dce4)
- Added ```/api/v1/accounts/{id}/unmute``` endpoint [41c96ddd](https://github.com/pixelfed/pixelfed/commit/41c96ddd)
- Added ```/api/v1/notifications``` endpoint [39449f36](https://github.com/pixelfed/pixelfed/commit/39449f36)
- Added ```/api/v1/timelines/home``` endpoint [cf3405d8](https://github.com/pixelfed/pixelfed/commit/cf3405d8)
- Added ```/api/v1/conversations``` endpoint [336f9069](https://github.com/pixelfed/pixelfed/commit/336f9069)
- Added ```/api/v1/timelines/public``` endpoint [f3eeb9c9](https://github.com/pixelfed/pixelfed/commit/f3eeb9c9)
- Added ```/api/v1/statuses/{id}/card``` endpoint [92251208](https://github.com/pixelfed/pixelfed/commit/92251208)
- Added ```/api/v1/statuses/{id}/reblogged_by``` endpoint [118006ed](https://github.com/pixelfed/pixelfed/commit/118006ed)
- Added ```/api/v1/statuses/{id}/favourited_by``` endpoint [5cdff57d](https://github.com/pixelfed/pixelfed/commit/5cdff57d)
- Added POST ```/api/v1/statuses``` endpoint [3aa729a3](https://github.com/pixelfed/pixelfed/commit/3aa729a3)
- Added DELETE ```/api/v1/statuses``` endpoint [0a20b832](https://github.com/pixelfed/pixelfed/commit/0a20b832)
- Added POST ```/api/v1/statuses/{id}/reblog``` endpoint [43cef282](https://github.com/pixelfed/pixelfed/commit/43cef282)
- Added POST ```/api/v1/statuses/{id}/unreblog``` endpoint [3147fe5c](https://github.com/pixelfed/pixelfed/commit/3147fe5c)
- Added GET ```/api/v1/timelines/tag/{hashtag}``` endpoint [2ff53be4](https://github.com/pixelfed/pixelfed/commit/2ff53be4)

### Fixed
- Update developer settings pages, fix vue bug [cd365ab3](https://github.com/pixelfed/pixelfed/commit/cd365ab3)
- Update User model, fix filter relationship [5a0c295e](https://github.com/pixelfed/pixelfed/commit/5a0c295e)

### Changed
- Updated Inbox Accept.Follow to use id of remote object [#1715](https://github.com/pixelfed/pixelfed/pull/1715)
- Update StatusTransformer, make spoiler_text non-nullable [b66cf9cd](https://github.com/pixelfed/pixelfed/commit/b66cf9cd)
- Update FollowerController, make follow and unfollow methods public [6237897d](https://github.com/pixelfed/pixelfed/commit/6237897d)
- Update DiscoverComponent, change api namespace [35275572](https://github.com/pixelfed/pixelfed/commit/35275572)

## Deprecated
- Removed deprecated AttachmentTransformer, superceeded by MediaTransformer [9b5aac4f](https://github.com/pixelfed/pixelfed/commit/9b5aac4f)

### To enable mobile app support
- Run ```php artisan passport:keys```
- Add ```OAUTH_ENABLED=true``` to .env
- Run ```php artisan config:cache```
    

## [v0.10.5 (2019-09-24)](https://github.com/pixelfed/pixelfed/compare/v0.10.4...v0.10.5)

### Added
- Added ```software``` back to AccountTransformer [93c687c7](https://github.com/pixelfed/pixelfed/commit/93c687c7)

### Fixed
- Fixed cache bug in privacy and terms pages [#1712](https://github.com/pixelfed/pixelfed/commit/fe522da8db7a8b0d7c18d405abcb885f8678f35c)

### Changed
    
    
## [v0.10.4 (2019-09-24)](https://github.com/pixelfed/pixelfed/compare/v0.10.3...v0.10.4)

### Added
- Added Welsh translations [#1706](https://github.com/pixelfed/pixelfed/pull/1706)
- Added Api v1 controller [85835f5a](https://github.com/pixelfed/pixelfed/commit/85835f5a6712dea0562df4be897087de5305750f)
- Added database migration that adds a language column to the users table [c87d8c16](https://github.com/pixelfed/pixelfed/commit/c87d8c16)
- Added persistent preferred language [18bc9c30](https://github.com/pixelfed/pixelfed/commit/18bc9c30)

### Fixed
- Fixed count bug in StatusHashtagService [#1694](https://github.com/pixelfed/pixelfed/pull/1694)
- Fixed private account bug [#1699](https://github.com/pixelfed/pixelfed/pull/1699)
- Fixed comments on MomentUI posts [#1704](https://github.com/pixelfed/pixelfed/pull/1704)

### Changed
- Updated EmailService, added new domains [#1690](https://github.com/pixelfed/pixelfed/pull/1690)
- Updated quill.js to v1.3.7 [#1692](https://github.com/pixelfed/pixelfed/pull/1692)
- Cache ProfileController [#1700](https://github.com/pixelfed/pixelfed/pull/1700)
- Updated ComposeUI v4, made cropping optional [#1702](https://github.com/pixelfed/pixelfed/pull/1702)
- Updated DiscoverController, limit Loops to local only posts [#1703](https://github.com/pixelfed/pixelfed/pull/1703)
- Namespaced internal apis [3c306c5e](https://github.com/pixelfed/pixelfed/commit/3c306c5e179d35dbe19a6a1bd9533350e4b96524)
- Updated .env.example with proper remote follow variable [0697f780](https://github.com/pixelfed/pixelfed/commit/0697f780d3a5cba72148f0a767d5a35124a3d9b4)
- Updated show all comments view [0a5eaa31](https://github.com/pixelfed/pixelfed/pull/1708/commits/0a5eaa3118cb09c61d3e5442fe3bf8439a2a12af)
- Updated language page layout [01fb5af](https://github.com/pixelfed/pixelfed/pull/1708/commits/01fb5af19e803488c5794b545d218771f6fce6d7)
- Updated privacy policy page layout [a4229d5](https://github.com/pixelfed/pixelfed/pull/1708/commits/a4229d5d30faea11e7a72d122c4a5762d867aaf3)
- Updated terms page layout [4f8c5e5](https://github.com/pixelfed/pixelfed/pull/1708/commits/4f8c5e5519949c63c702c724a00d8575db4e0014)
- Update v1 API, added /api/v1/instance endpoint [951b6fa0](https://github.com/pixelfed/pixelfed/commit/951b6fa0) [9dc2234b](https://github.com/pixelfed/pixelfed/commit/99dc2234b)

## Deprecated
- Remove deprecated profile following/followers [#1697](https://github.com/pixelfed/pixelfed/pull/1697)
- Remove old comment permalink [05f6598](https://github.com/pixelfed/pixelfed/pull/1708/commits/05f659896d903e1ff41dba810f125d721fa057e7)
    
    
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

- Typo in Inbox prevented proper federation support [#1664](https://github.com/pixelfed/pixelfed/pull/1664)


## [v0.10.1 (2019-09-06)](https://github.com/pixelfed/pixelfed/compare/v0.10.0...v0.10.1)

### Added
- Remote follows! Search for an actor URI, send AP Follow, plus handle incoming AP Accept Follow
- Compose UI v4: a rework of the v3 flow to allow basic cropping and better support future post types
- Profile badges show if a user is following you or is an admin
- Show confirmation message when muting or blocking a user from a post
- Allow "read more" to be disabled on posts [#1545](https://github.com/pixelfed/pixelfed/pull/1545)
- Loops! Discover short videos
- Preliminary support for profile PropertyValue metadata
- Preliminary support for Direct Messages
- Places! Run the artisan task `import:cities` 
- Emails are now validated and banned email domains are disallowed at signup. Artisan task `email:bancheck` will validate existing users.
- .env vars `REDIS_SCHEME` and `REDIS_PATH` allow for using Redis over a Unix socket instead of TCP [#1602](https://github.com/pixelfed/pixelfed/pull/1602)
- .env var `IMAGE_DRIVER` allows using imagick instead of gd

### Fixed
- Show delete button while composing video posts [#1529](https://github.com/pixelfed/pixelfed/pull/1529)
- Show pending follow requests on private profiles
- Allow muted users to comment on your posts [#1537](https://github.com/pixelfed/pixelfed/pull/1537)
- Bugs with carousel cursor and tooltips
- Collections can now be deleted from collection page
- Compose modal now indicates album media limits
- Unlisted and private posts are now delivered
- Don't show Register link in navbar when registrations are closed

### Changed
- Use vue-masonry for Moment UI layout [#1536](https://github.com/pixelfed/pixelfed/pull/1536)
- User post limit changed from 20/hr to 50/hr
- Better mobile profile layout
- Dark mode is now a bit bluer
- Sample nginx.conf in contrib/ now uses HTTPS instead of HTTP. Docs updated to reference this file
- Updated register form
- Allow users to edit email after registrations
    

## [v0.10.0 (2019-07-17)](https://github.com/pixelfed/pixelfed/compare/v0.9.6...v0.10.0)

### Added
- Collections! Add posts to Collections, similar to categories. [#1511](https://github.com/pixelfed/pixelfed/pull/1511)
- Profile donate links: add links to Patreon, Liberapay, and OpenCollective on your profile [#1500](https://github.com/pixelfed/pixelfed/pull/1500)

### Fixed
- Show correct mode when viewing followers / following

### Changed
- Profile model now uses snowflake id [#1502](https://github.com/pixelfed/pixelfed/pull/1502)

### Removed
- OStatus legacy code has been removed [#1510](https://github.com/pixelfed/pixelfed/pull/1510)

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
