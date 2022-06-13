# Release Notes

## [Unreleased](https://github.com/pixelfed/pixelfed/compare/v0.11.3...dev)

### New Features
- Custom content warnings/spoiler text ([d4864213](https://github.com/pixelfed/pixelfed/commit/d4864213))

### Breaking
- Replaced `predis` with `phpredis` as default redis driver due to predis being deprecated, install [phpredis](https://github.com/phpredis/phpredis/blob/develop/INSTALL.markdown) if you're still using predis.

### Updates
- Improve S3 support by removing `ListObjects` call in media deletion ([#3438](https://github.com/pixelfed/pixelfed/pull/3438))
- Enforce UTC in incoming activities ([18931a1f](https://github.com/pixelfed/pixelfed/commit/18931a1f))
- Add storage flags to admin dashboard diagnostics ([#3444](https://github.com/pixelfed/pixelfed/pull/3444))
- Hardcode UTC application timezone to prevent timezone issues ([b0d2c5e1](https://github.com/pixelfed/pixelfed/commit/b0d2c5e1))
- Remove arbitrary metro url redirect timeout ([84209c24](https://github.com/pixelfed/pixelfed/commit/84209c24))
- Fix JSON-LD contexts ([#3464](https://github.com/pixelfed/pixelfed/pull/3464))
- Fix json-ld attributes, fixes #3423 ([95f902b1](https://github.com/pixelfed/pixelfed/commit/95f902b1))
- Add trusted proxies flag to admin dashboard diagnostics ([#3450](https://github.com/pixelfed/pixelfed/pull/3450))
- Fix json-ld attributes, fixes #3423 ([95f902b1](https://github.com/pixelfed/pixelfed/commit/95f902b1))
- Update exp config, enforce mastoapi compatibility by default ([a160b233](https://github.com/pixelfed/pixelfed/commit/a160b233))
- Update home timeline, redirect to /i/web unless force_old_ui is present ([5ff4730f](https://github.com/pixelfed/pixelfed/commit/5ff4730f))
- Update adminReportController, fix mail verification request 500 bug by changing filter precedence to catch deleted users that may still be cached in AccountService ([3f322e29](https://github.com/pixelfed/pixelfed/commit/3f322e29))
- Update AP Helpers, fix getSensitive and getScope missing parameters ([657c66c1](https://github.com/pixelfed/pixelfed/commit/657c66c1))
- Fix mastodon api compatibility ([#3499](https://github.com/pixelfed/pixelfed/pull/3499))
- Add ffmpeg config, disable logging by default ([108e3803](https://github.com/pixelfed/pixelfed/commit/108e3803))
- Refactor AP profileFetch logic to fix race conditions and improve updating fields and avatars ([505261da](https://github.com/pixelfed/pixelfed/commit/505261da))
- Update network timeline api, limit falloff to 2 days ([13a66303](https://github.com/pixelfed/pixelfed/commit/13a66303))
- Update Inbox, store follow request activity ([c82f2085](https://github.com/pixelfed/pixelfed/commit/c82f2085))
-  ([](https://github.com/pixelfed/pixelfed/commit/))

## [v0.11.3 (2022-05-09)](https://github.com/pixelfed/pixelfed/compare/v0.11.2...v0.11.3)

### Added
- Custom Emoji ([#3166](https://github.com/pixelfed/pixelfed/pull/3166))
- LDAP Authentication ([#3296](https://github.com/pixelfed/pixelfed/pull/3296))

### Metro 2.0 UI
- Dark Mode ([cb540373](https://github.com/pixelfed/pixelfed/commit/cb540373))
- Added Hovercards ([16ced7b4](https://github.com/pixelfed/pixelfed/commit/16ced7b4))
- Fix word-break on statuses ([16ced7b4](https://github.com/pixelfed/pixelfed/commit/16ced7b4))
- Add pronouns to hovercards ([33f863e8](https://github.com/pixelfed/pixelfed/commit/33f863e8))
- Improved onboarding ([042c5b6c](https://github.com/pixelfed/pixelfed/commit/042c5b6c))
- Add Hide Counts & Stats setting ([01af7d80](https://github.com/pixelfed/pixelfed/commit/01af7d80))
- Fix nsfw videos not displaying sensitive warning ([01af7d80](https://github.com/pixelfed/pixelfed/commit/01af7d80))
- Easy Avatar updates - update from timelines with drag-n-drop support ([f37d3798](https://github.com/pixelfed/pixelfed/commit/f37d3798))
- Comment hovercards ([f37d3798](https://github.com/pixelfed/pixelfed/commit/f37d3798))
- Mod tools button on posts for admins ([f37d3798](https://github.com/pixelfed/pixelfed/commit/f37d3798))
- Improved Media Previews - disable to restore original preview aspect ratios ([c55eeac8](https://github.com/pixelfed/pixelfed/commit/c55eeac8))
- Moved media license to post header ([390f3ab0](https://github.com/pixelfed/pixelfed/commit/390f3ab0))
- Mobile app drawer menu ([7b4318fd](https://github.com/pixelfed/pixelfed/commit/7b4318fd))
- Add Preferred Profile Layout UI setting ([a816ea66](https://github.com/pixelfed/pixelfed/commit/a816ea66))
- Fix profile masonry layout on mobile. Fixes #3203 ([fdf90f2d](https://github.com/pixelfed/pixelfed/commit/fdf90f2d))
- Add search bar to mobile breakpoints and adjust avatar size when necessary ([77b9b6bd](https://github.com/pixelfed/pixelfed/commit/77b9b6bd))
- Improved profile layout on mobile breakpoints ([77b9b6bd](https://github.com/pixelfed/pixelfed/commit/77b9b6bd))
- New Discover layout with My Hashtags, My Memories, Account Insights, Find Friends and Server Timelines ([0b680099](https://github.com/pixelfed/pixelfed/commit/0b680099))
- Fix private profile feed not loading for owner ([e950b3b2](https://github.com/pixelfed/pixelfed/commit/e950b3b2))
- Add "Shared by" link to posts that opens a list of accounts that reblogged the post ([e4b4bfc1](https://github.com/pixelfed/pixelfed/commit/e4b4bfc1))
- Notification filters ([537af6df](https://github.com/pixelfed/pixelfed/commit/537af6df))
- Full screen preview on photo albums ([ac40fde1](https://github.com/pixelfed/pixelfed/commit/ac40fde1))

### Updated
- Updated MediaStorageService, fix remote avatar bug. ([1c20d696](https://github.com/pixelfed/pixelfed/commit/1c20d696))
- Updated WebfingerService. Fixes #3167. ([aff74566](https://github.com/pixelfed/pixelfed/commit/aff74566))
- Updated ComposeModal, add max file size and allowed mime types. Fixes #3162. ([879281cc](https://github.com/pixelfed/pixelfed/commit/879281cc))
- Updated profile embeds, fix NaN bug and improve performance. ([3bd211d7](https://github.com/pixelfed/pixelfed/commit/3bd211d7))
- Updated ApiV1Controller, improve follow count cache invalidation. ([4b6effb9](https://github.com/pixelfed/pixelfed/commit/4b6effb9))
- Updated web routes, fix atom feeds for account usernames containing a dot. ([8c54ab57](https://github.com/pixelfed/pixelfed/commit/8c54ab57))
- Updated atom feeds, include media alt text. Fixes #3184. ([5d9b6863](https://github.com/pixelfed/pixelfed/commit/5d9b6863))
- Updated ApiV1Controller, add custom_emoji endpoint. ([16e72518](https://github.com/pixelfed/pixelfed/commit/16e72518))
- Updated InternalApiController, redirect remote post and profiles to Metro 2.0. ([3c35158e](https://github.com/pixelfed/pixelfed/commit/3c35158e))
- Updated BaseApiController, improve favourites endpoint. ([f063cb01](https://github.com/pixelfed/pixelfed/commit/f063cb01))
- Updated ApiV1Controller, invalidate status reply cache on new reply. ([3c261bbf](https://github.com/pixelfed/pixelfed/commit/3c261bbf))
- Updated PublicApiController, add bookmark state to timeline endpoints. ([c0b1e042](https://github.com/pixelfed/pixelfed/commit/c0b1e042))
- Updated ApiV1Controller, fix private status replies returning 404. ([73226360](https://github.com/pixelfed/pixelfed/commit/73226360))
- Updated StatusService, use BookmarkService for bookmarked state. ([a7d71551](https://github.com/pixelfed/pixelfed/commit/a7d71551))
- Updated Apis, added ReblogService to improve reblogged state for api entities ([6cfd6be5](https://github.com/pixelfed/pixelfed/commit/6cfd6be5))
- Updated InstanceActorController, fix content-type header. ([21792246](https://github.com/pixelfed/pixelfed/commit/21792246))
- Updated Exception handler to report validation message bag errors. ([74905ba1](https://github.com/pixelfed/pixelfed/commit/74905ba1))
- Updated ApiV1Controller, add validation messages to update_credentials endpoint. ([cd785601](https://github.com/pixelfed/pixelfed/commit/cd785601))
- Updated ComposeController, improve location search results ordering by use frequency. ([29c4bd25](https://github.com/pixelfed/pixelfed/commit/29c4bd25))
- Updated AvatarController, fix mimetype bug. ([7fa9d4dc](https://github.com/pixelfed/pixelfed/commit/7fa9d4dc))
- Updated PostComponent.vue, filter out non-text comments. ([a7346f21](https://github.com/pixelfed/pixelfed/commit/a7346f21))
- Updated Profile.vue component, fix v-once bug. ([4d003d00](https://github.com/pixelfed/pixelfed/commit/4d003d00))
- Updated filesystems config, set S3 visibility to public by default. Fixes #2913. ([49a53c27](https://github.com/pixelfed/pixelfed/commit/49a53c27))
- Updated CommentPipeline, improve parent reply_count calculation. ([ccc94802](https://github.com/pixelfed/pixelfed/commit/ccc94802))
- Updated StatusTagsPipeline, process federated hashtags and mentions ([a84b1736](https://github.com/pixelfed/pixelfed/commit/a84b1736))
- Updated Inbox, fix undo announce. ([cf286fb0](https://github.com/pixelfed/pixelfed/commit/cf286fb0))
- Updated ApiV1Controller, improve favourites endpoint. ([151dc17c](https://github.com/pixelfed/pixelfed/commit/151dc17c))
- Updated StatusController, set missing reblog/share type. ([548a12a4](https://github.com/pixelfed/pixelfed/commit/548a12a4))
- Updated index view, remove shortcut from favicon meta tag. Fixes #3196. ([6e2cb3cd](https://github.com/pixelfed/pixelfed/commit/6e2cb3cd))
- Updated CollectionController, fix broken unauthenticated access. Fixes #3242. ([bd249f0c](https://github.com/pixelfed/pixelfed/commit/bd249f0c))
- Updated ComposeController, add collection support to compose endpoint. ([ec2cfaf5](https://github.com/pixelfed/pixelfed/commit/ec2cfaf5))
- Updated instance config, match default oauth settings in AuthServiceProvider. ([52f25ff1](https://github.com/pixelfed/pixelfed/commit/52f25ff1))
- Updated ComposeModal.vue, fix redirect after posting. Fixes #3254. ([5db64e94](https://github.com/pixelfed/pixelfed/commit/5db64e94))
- Updated StatusController, redirect status view for authed users to Metro 2.0 UI. ([71dff472](https://github.com/pixelfed/pixelfed/commit/71dff472))
- Updated ProfileController, redirect profile view for authed users to Metro 2.0 UI. ([7f8129a7](https://github.com/pixelfed/pixelfed/commit/7f8129a7))
- Updated SpaController, fix variable typo. Fixes #3268. ([8d1af1d6](https://github.com/pixelfed/pixelfed/commit/8d1af1d6))
- Updated ComposeModal, fix post redirect on old UI. ([160e32a5](https://github.com/pixelfed/pixelfed/commit/160e32a5))
- Updated LikeService, improve caching logic and add profile id to likedBy method to fix #3271. ([6af842eb](https://github.com/pixelfed/pixelfed/commit/6af842eb))
- Updated admin diagnostics, add more configuration data to help diagnose potential issues. ([eab96fc3](https://github.com/pixelfed/pixelfed/commit/eab96fc3))
- Updated ConfigCacheService, fix discover features. ([ad48521a](https://github.com/pixelfed/pixelfed/commit/ad48521a))
- Updated MediaTransformer, fix type case bug. Fixes #3281. ([c1669253](https://github.com/pixelfed/pixelfed/commit/c1669253))
- Updated SpaController, redirect web ui hashtags to legacy page for unauthenticated users. ([a44b812b](https://github.com/pixelfed/pixelfed/commit/a44b812b))
- Updated ApiV1Controller, fixes #3288. ([3e670774](https://github.com/pixelfed/pixelfed/commit/3e670774))
- Updated AP Helpers, fixes #3287. ([b78bff72](https://github.com/pixelfed/pixelfed/commit/b78bff72))
- Updated AP Helpers, fixes #3290. ([53975206](https://github.com/pixelfed/pixelfed/commit/53975206))
- Updated AccountController, refresh relationship after handling follow request. ([fe768785](https://github.com/pixelfed/pixelfed/commit/fe768785))
- Updated CollectionController, fixes #3289. ([c7e1e473](https://github.com/pixelfed/pixelfed/commit/c7e1e473))
- Updated SpaController, handle web redirects. ([b6c6c85b](https://github.com/pixelfed/pixelfed/commit/b6c6c85b))
- Updated presenter components, remove video poster attribute. ([4d612dfa](https://github.com/pixelfed/pixelfed/commit/4d612dfa))
- Improved reblog api performance ([3ef6c9fe](https://github.com/pixelfed/pixelfed/commit/3ef6c9fe))
- Updated ApiV1Controller, fix unlisted replies. ([c13bca76](https://github.com/pixelfed/pixelfed/commit/c13bca76))
- Updated SearchApiV2Service, filter banned instances. ([281443d7](https://github.com/pixelfed/pixelfed/commit/281443d7))
- Updated DiscoverController, fix favourited state on memories. ([b91747b4](https://github.com/pixelfed/pixelfed/commit/b91747b4))
- Updated InboxPipeline, fixes #3306. ([20710f4d](https://github.com/pixelfed/pixelfed/commit/20710f4d))
- Updated inbox workers, fixes #3304. ([cd4f73be](https://github.com/pixelfed/pixelfed/commit/cd4f73be))
- Updated Inbox, fixes #3305. ([14231632](https://github.com/pixelfed/pixelfed/commit/14231632))
- Updated Inbox, fixes #3313. ([1c3e72c0](https://github.com/pixelfed/pixelfed/commit/1c3e72c0))
- Updated Inbox, fixes #3314. ([dfcd2e6d](https://github.com/pixelfed/pixelfed/commit/dfcd2e6d))
- Updated search service, fix banned instance edge case. ([74018e9c](https://github.com/pixelfed/pixelfed/commit/74018e9c))
- Updated inbox, fixes #3315. ([c3c3ce18](https://github.com/pixelfed/pixelfed/commit/c3c3ce18))
- Updated ApiV1Controller, fix instance endpoint. ([c383f100](https://github.com/pixelfed/pixelfed/commit/c383f100))
- Updated ApiV1Controller, marshal json without escaped slashes. ([89303fa4](https://github.com/pixelfed/pixelfed/commit/89303fa4))
- Updated ApiV1Controller, fix statusCreate validator. ([b6b15b0c](https://github.com/pixelfed/pixelfed/commit/b6b15b0c))
- Updated ApiV1Controller, fix notification entities. ([afe903c3](https://github.com/pixelfed/pixelfed/commit/afe903c3))
- Updated FederationController, fix webfinger endpoint. ([a0e15d89](https://github.com/pixelfed/pixelfed/commit/a0e15d89))
- Updated ApiV1Controller, fix context entities. ([b1ab41e0](https://github.com/pixelfed/pixelfed/commit/b1ab41e0))
- Updated ApiV1Controller, fix timeline default limit. ([a87f8301](https://github.com/pixelfed/pixelfed/commit/a87f8301))
- Updated ApiV1Controller, fix search v2 entities. ([9dac861e](https://github.com/pixelfed/pixelfed/commit/9dac861e))
- Updated ApiV1Controller, fix apps endpoint. ([50baae52](https://github.com/pixelfed/pixelfed/commit/50baae52))
- Updated ApiV1Controller, add apps/verify_credentials endpoint. ([c4d38c20](https://github.com/pixelfed/pixelfed/commit/c4d38c20))
- Updated ApiV1Controller, increase max limion timelines. ([df22f2e4](https://github.com/pixelfed/pixelfed/commit/df22f2e4))
- Updated ApiV1Controller, add preferences endpoint. ([c3e56b87](https://github.com/pixelfed/pixelfed/commit/c3e56b87))
- Updated ApiV1Controller, fix tag timeline limits and remove has(media) constraint. ([8c65d60b](https://github.com/pixelfed/pixelfed/commit/8c65d60b))
- Updated ApiV1Controller, add trends endpoint. ([d40a8453](https://github.com/pixelfed/pixelfed/commit/d40a8453))
- Updated ApiV1Controller, add announcements endpoint. ([fbe07c51](https://github.com/pixelfed/pixelfed/commit/fbe07c51))
- Updated ApiV1Controller, add markers endpoint. ([93a9769e](https://github.com/pixelfed/pixelfed/commit/93a9769e))
- Updated ApiV1Controller, increase limits from 80 to 100. ([15eccd44](https://github.com/pixelfed/pixelfed/commit/15eccd44))
- Updated ApiV1Controller, fix accountStatusesById endpoint. ([db7b1af3](https://github.com/pixelfed/pixelfed/commit/db7b1af3))
- Updated ApiV1Controller, update statusCreate entity. ([a84ab6ea](https://github.com/pixelfed/pixelfed/commit/a84ab6ea))
- Updated ApiV1Controller, remove pinned attribute to match MastoAPI Status entity. ([6057de30](https://github.com/pixelfed/pixelfed/commit/6057de30))
- Updated controller signatures, fix mysql 8 support. ([72e3d891](https://github.com/pixelfed/pixelfed/commit/72e3d891))
- Updated ApiV1Controller, remove no-preview image from media urls. ([37dfb101](https://github.com/pixelfed/pixelfed/commit/37dfb101))
- Updated DeleteAccountPipeline, fix perf issues. ([a9edd93f](https://github.com/pixelfed/pixelfed/commit/a9edd93f))
- Updated DeleteAccountPipeline, improve coverage. ([4870cc3b](https://github.com/pixelfed/pixelfed/commit/4870cc3b))
- Updated media model, use original photo url for non-existent thumbnails. ([9b04b9d8](https://github.com/pixelfed/pixelfed/commit/9b04b9d8))
- Updated PlaceController, require authentication. ([e7783af6](https://github.com/pixelfed/pixelfed/commit/e7783af6))
- Updated PublicApiController, disable legacy public access to local timeline. ([6ba7d433](https://github.com/pixelfed/pixelfed/commit/6ba7d433))
- Updated DiscoverController, cache public tag feed and only include local posts for unauthenticated users. ([0541aed5](https://github.com/pixelfed/pixelfed/commit/0541aed5))
- Updated DiscoverController, improve tag feed performance. ([d8ff40eb](https://github.com/pixelfed/pixelfed/commit/d8ff40eb))
- Updated ApiV1Controller, fix timeline pagination. ([a5cdc28b](https://github.com/pixelfed/pixelfed/commit/a5cdc28b))
- Updated ApiV1Controller, add missing pagination header. ([5649873a](https://github.com/pixelfed/pixelfed/commit/5649873a))
- Updated CollectionController, limit unpublished collections to owner. ([a0061eb5](https://github.com/pixelfed/pixelfed/commit/a0061eb5))
- Updated AP Inbox, fixes #3332. ([f8931dc7](https://github.com/pixelfed/pixelfed/commit/f8931dc7))
- Updated AdminReportController, add account delete button. ([563817a9](https://github.com/pixelfed/pixelfed/commit/563817a9))
- Updated ApiV1Controller, added /api/v2/media endpoint, fixes #3405. ([f07cc14c](https://github.com/pixelfed/pixelfed/commit/f07cc14c))
- Updated AP fanout, added Content-Type and User-Agent for activity delivery. ([@noellabo](https://github.com/noellabo)) ([209c125](https://github.com/pixelfed/pixelfed/commit/209c125))
- Updated DirectMessageController to support new Metro 2.0 UI DMs. ([a4659fd2](https://github.com/pixelfed/pixelfed/commit/a4659fd2))
- Updated Like model, bump max likes per day from 100 to 200. ([71ba5fed](https://github.com/pixelfed/pixelfed/commit/71ba5fed))
- Updated HashtagService, use sorted set for followed tags. ([153eb6ba](https://github.com/pixelfed/pixelfed/commit/153eb6ba))
- Updated Discover component, fixed post side effects (fixes #3409). ([fe5a92b2](https://github.com/pixelfed/pixelfed/commit/fe5a92b2))

## [v0.11.2 (2022-01-09)](https://github.com/pixelfed/pixelfed/compare/v0.11.1...v0.11.2)

### Breaking
- Dropped support for PHP 7.3 [#3041](https://github.com/pixelfed/pixelfed/pull/3041)

### Metro 2.0 UI
- Added UI Settings modal and fixed height media previews setting ([f2467e71](https://github.com/pixelfed/pixelfed/commit/f2467e71))
- Set max-width of 1440px for larger screens ([af68872a](https://github.com/pixelfed/pixelfed/commit/af68872a))
- Add link to sidebar profile card ([85964510](https://github.com/pixelfed/pixelfed/commit/85964510))
- Improved search bar, now resolves (and imports) remote accounts and posts, including webfinger addresses ([c8a667f2](https://github.com/pixelfed/pixelfed/commit/c8a667f2))
- Added user facing changelog at `/i/web/whats-new` ([e61dc66a](https://github.com/pixelfed/pixelfed/commit/e61dc66a))

### Configuration
- Enable network timeline by default ([b95aec12](https://github.com/pixelfed/pixelfed/commit/b95aec12))

### Postgres Compatibility
- Fix Story recent endpoint on postgres instances ([ddf41dc3](https://github.com/pixelfed/pixelfed/commit/ddf41dc3))
- Fix Direct Message conversations endpoint on postgres instances ([fcabc9be](https://github.com/pixelfed/pixelfed/commit/fcabc9be))

### Added
- Manual email verification requests. ([bc659387](https://github.com/pixelfed/pixelfed/commit/bc659387))
- Added StatusMentionService, fixes #3026. ([e5387d67](https://github.com/pixelfed/pixelfed/commit/e5387d67))
- Cloud Backups, a command to store backups on S3 or compatible filesystems. [#3037](https://github.com/pixelfed/pixelfed/pull/3037) ([3515a98e](https://github.com/pixelfed/pixelfed/commit/3515a98e))
- Web UI Localizations + Crowdin integration. ([f7d9b40b](https://github.com/pixelfed/pixelfed/commit/f7d9b40b)) ([7ff120c9](https://github.com/pixelfed/pixelfed/commit/7ff120c9))
- Store remote avatars locally if S3 not enabled. ([b4bd0400](https://github.com/pixelfed/pixelfed/commit/b4bd0400))

### Updated
- Updated NotificationService, fix 500 bug. ([4a609dc3](https://github.com/pixelfed/pixelfed/commit/4a609dc3))
- Updated HttpSignatures, update instance actor headers. Fixes #2935. ([a900de21](https://github.com/pixelfed/pixelfed/commit/a900de21))
- Updated NoteTransformer, fix tag array. ([7b3e672d](https://github.com/pixelfed/pixelfed/commit/7b3e672d))
- Updated video presenters, add playsinline attribute to video tags. ([0299aa5b](https://github.com/pixelfed/pixelfed/commit/0299aa5b))
- Updated RemotePost, RemoteProfile components, add fallback avatars. ([754151dc](https://github.com/pixelfed/pixelfed/commit/754151dc))
- Updated FederationController, move well-known to api middleware and cache webfinger lookups. ([4505d1f0](https://github.com/pixelfed/pixelfed/commit/4505d1f0))
- Updated InstanceActorController, improve json seralization by not escaping slashes. ([0a8eb81b](https://github.com/pixelfed/pixelfed/commit/0a8eb81b))
- Refactor following & relationship logic. Replace FollowerObserver with FollowerService and added RelationshipService to cache results. Removed NotificationTransformer includes and replaced with cached services to improve performance and reduce database queries. ([80d9b939](https://github.com/pixelfed/pixelfed/commit/80d9b939))
- Updated PublicApiController, use AccountService in accountStatuses method. ([bef959f4](https://github.com/pixelfed/pixelfed/commit/bef959f4))
- Updated auth config, add throttle limit to password resets. ([2609c86a](https://github.com/pixelfed/pixelfed/commit/2609c86a))
- Updated StatusCard component, add relationship state button. ([0436b124](https://github.com/pixelfed/pixelfed/commit/0436b124))
- Updated Timeline component, cascade relationship state change. ([f4bd5672](https://github.com/pixelfed/pixelfed/commit/f4bd5672))
- Updated Activity component, only show context button for actionable activities. ([7886fd59](https://github.com/pixelfed/pixelfed/commit/7886fd59))
- Updated Autospam service, use silent classification for better user experience. ([f0d4c172](https://github.com/pixelfed/pixelfed/commit/f0d4c172))
- Updated Profile component, improve error messages when block/mute limit reached. ([02237845](https://github.com/pixelfed/pixelfed/commit/02237845))
- Updated Activity component, fix missing types. ([5167c68d](https://github.com/pixelfed/pixelfed/commit/5167c68d))
- Updated Timeline component, apply block/mute filters client side for local and network timelines. ([be194b8a](https://github.com/pixelfed/pixelfed/commit/be194b8a))
- Updated public timeline api, use cached sorted set and client side block/mute filtering. ([37abcf38](https://github.com/pixelfed/pixelfed/commit/37abcf38))
- Updated public timeline api, add experimental cache. ([192553ff](https://github.com/pixelfed/pixelfed/commit/192553ff))
- Updated dark mode styles, fix black box on stories. Closes #2982. ([3169f68e](https://github.com/pixelfed/pixelfed/commit/3169f68e))
- Updated verify_credentials api endpoint to improve performance. ([7df3540b](https://github.com/pixelfed/pixelfed/commit/7df3540b))
- Updated Localization util, filter out .DS_Store. ([0107e8fd](https://github.com/pixelfed/pixelfed/commit/0107e8fd))
- Updated PublicApiController, fix private account statuses api. Closes #2995. ([aa2dd26c](https://github.com/pixelfed/pixelfed/commit/aa2dd26c))
- Updated Status model, use AccountService to generate urls instead of loading profile relation. ([2ae527c0](https://github.com/pixelfed/pixelfed/commit/2ae527c0))
- Updated Autospam service, add mark all as read and mark all as not spam options and filter active, spam and not spam reports. ([ae8c7517](https://github.com/pixelfed/pixelfed/commit/ae8c7517))
- Updated UserInviteController, fixes #3017. ([b8e9056e](https://github.com/pixelfed/pixelfed/commit/b8e9056e))
- Updated AccountService, add dynamic user settings methods. ([2aa73c1f](https://github.com/pixelfed/pixelfed/commit/2aa73c1f))
- Updated MediaStorageService, improve header parsing. ([9d9e9ce7](https://github.com/pixelfed/pixelfed/commit/9d9e9ce7))
- Updated SearchApiV2Service, improve performance and include hashtag post counts when applicable ([fbaed93e](https://github.com/pixelfed/pixelfed/commit/fbaed93e))
- Updated AccountTransformer, add note_text and location fields. ([98f76abb](https://github.com/pixelfed/pixelfed/commit/98f76abb))
- Updated UserSetting model, cast compose_settings and other as json. ([03420278](https://github.com/pixelfed/pixelfed/commit/03420278))
- Updated ApiV1Controller, improve settings and add discoverPosts endpoint. ([079804e6](https://github.com/pixelfed/pixelfed/commit/079804e6))
- Updated LikePipeline jobs, fix likes_count calculation. ([fe64e187](https://github.com/pixelfed/pixelfed/commit/fe64e187))
- Updated InternalApiController, prevent moderation actions against admin accounts. ([945a7e49](https://github.com/pixelfed/pixelfed/commit/945a7e49))
- Updated CommentPipeline, move reply_count calculation to comment pipeline job and improve count calculation. ([b6b0837f](https://github.com/pixelfed/pixelfed/commit/b6b0837f))
- Updated ApiV1Controller, improve statusesById perf and dispatch CommentPipeline job when applicable. ([466286af](https://github.com/pixelfed/pixelfed/commit/466286af))
- Updated MediaService, return empty array if cant find status. ([c2910e5d](https://github.com/pixelfed/pixelfed/commit/c2910e5d))
- Updated StatusService, improve cache invalidation. ([83b48b56](https://github.com/pixelfed/pixelfed/commit/83b48b56))
- Updated Hashtag component, fix spinner. ([fefbc44a](https://github.com/pixelfed/pixelfed/commit/fefbc44a))
- Updated NotificationCard, update api endpoint and add group notification types. ([e09a14d8](https://github.com/pixelfed/pixelfed/commit/e09a14d8))
- Updated ContextMenu component, fix account url paths. ([01ca1edd](https://github.com/pixelfed/pixelfed/commit/01ca1edd))
- Updated PollCard component, add showBorder prop. ([0c8fffbd](https://github.com/pixelfed/pixelfed/commit/0c8fffbd))
- Updated PhotoPresenter component, add lightbox toggle. ([0cc1365f](https://github.com/pixelfed/pixelfed/commit/0cc1365f))
- Updated console kernel, add db session garbage collector that runs twice daily. ([03b0a62a](https://github.com/pixelfed/pixelfed/commit/03b0a62a))
- Updated ComposeController, refactor compose_settings. ([edc2958b](https://github.com/pixelfed/pixelfed/commit/edc2958b))
- Updated StatusEntityLexer, prevent boosts and replies from being added to PublicTimelineService. ([32707372](https://github.com/pixelfed/pixelfed/commit/32707372))
- Updated SpaController, persist web language changes. ([7bc684e5](https://github.com/pixelfed/pixelfed/commit/7bc684e5))
- Updated LoginController, bump decayMinutes from 1 to 60. ([6bf92bed](https://github.com/pixelfed/pixelfed/commit/6bf92bed))
- Updated SPA, rewrite autolink urls to SPA when applicable. ([0837b410](https://github.com/pixelfed/pixelfed/commit/0837b410))
- Updated site config, increase ttl and enable SPA by default. ([469d49d8](https://github.com/pixelfed/pixelfed/commit/469d49d8))
- Updated Webfinger, fixes #3050. ([ff7ee3bd](https://github.com/pixelfed/pixelfed/commit/ff7ee3bd))
- Updated status api, autolink caption before returning response. ([b00a453b](https://github.com/pixelfed/pixelfed/commit/b00a453b))
- Updated Timeline, add new ui promo in timelines that can be hidden using localstorage. ([e13959ae](https://github.com/pixelfed/pixelfed/commit/e13959ae))
- Updated FederationController, increase webfinger cache ttl from 12 hours to 14 days. ([745c3580](https://github.com/pixelfed/pixelfed/commit/745c3580))
- Updated DiscoverController, add yearly option and increase limit from 15 to 30 posts. ([10b6058c](https://github.com/pixelfed/pixelfed/commit/10b6058c))
- Updated RemoteAvatarFetch job, fixed bug preventing new avatars from being stored. ([92bc2845](https://github.com/pixelfed/pixelfed/commit/92bc2845))
- Updated AccountService, fix json casting. ([e5f8f344](https://github.com/pixelfed/pixelfed/commit/e5f8f344))
- Updated ApiV1Controller, fix illegal operator bug by setting default min_id. ([415826f2](https://github.com/pixelfed/pixelfed/commit/415826f2))
- Updated StatusService, add getMastodon method for mastoapi compatibility. ([36a129fe](https://github.com/pixelfed/pixelfed/commit/36a129fe))
- Updated PublicApiController, fix accountStatuses pagination operator. ([85fc9dd0](https://github.com/pixelfed/pixelfed/commit/85fc9dd0))
- Updated PublicApiController, enforce only_media on accountStatuses method. Fixes #3105. ([861a2d36](https://github.com/pixelfed/pixelfed/commit/861a2d36))
- Updated ApiV1Controller, add mastoapi strict mode. ([46485426](https://github.com/pixelfed/pixelfed/commit/46485426))
- Updated AccountController, refresh RelationshipService on mute/block. ([6f1b0245](https://github.com/pixelfed/pixelfed/commit/6f1b0245))
- Updated ApiV1Controller, fix version on instance endpoint. ([a6261221](https://github.com/pixelfed/pixelfed/commit/a6261221))
- Updated components, fix api endpoints. Fixes #3138. ([e724633e](https://github.com/pixelfed/pixelfed/commit/e724633e))
- Updated ApiV1Controller, fix public timeline endpoint. ([80c7def3](https://github.com/pixelfed/pixelfed/commit/80c7def3))
- Updated PublicApiController, fix public timeline endpoint. ([dcb7ba9c](https://github.com/pixelfed/pixelfed/commit/dcb7ba9c))
- Updated ApiV1Controller, fix home timeline entities. ([6fc0dcb3](https://github.com/pixelfed/pixelfed/commit/6fc0dcb3))
- Updated ApiV1Controller, fix favourites endpoints ([d6d99385](https://github.com/pixelfed/pixelfed/commit/d6d99385))
- Updated ApiV1Controller, fix reblogs endpoints ([de42d84c](https://github.com/pixelfed/pixelfed/commit/de42d84c))
- Updated SearchApiV2Service, resolve remote queries. ([c8a667f2](https://github.com/pixelfed/pixelfed/commit/c8a667f2))

## [v0.11.1 (2021-09-07)](https://github.com/pixelfed/pixelfed/compare/v0.11.0...v0.11.1)
### Added
- WebP Support ([069a0e4a](https://github.com/pixelfed/pixelfed/commit/069a0e4a))
- Auto Following support for admins ([68aa2540](https://github.com/pixelfed/pixelfed/commit/68aa2540))
- Mark as spammer mod tool, unlists and applies content warning to existing and future post ([6d956a86](https://github.com/pixelfed/pixelfed/commit/6d956a86))
- Diagnostics for error page and admin dashboard ([64725ecc](https://github.com/pixelfed/pixelfed/commit/64725ecc))
- Default media licenses and media license sync ([ea0fc90c](https://github.com/pixelfed/pixelfed/commit/ea0fc90c))
- Customize media description/alt-text length limit ([072d55d1](https://github.com/pixelfed/pixelfed/commit/072d55d1))
- Federate Media Licenses ([14a1367a](https://github.com/pixelfed/pixelfed/commit/14a1367a))
- Archive Posts ([e9ef0c88](https://github.com/pixelfed/pixelfed/commit/e9ef0c88))
- Polls ([77092200](https://github.com/pixelfed/pixelfed/commit/77092200))
- Federated Stories (#2895)

### Updated
- Updated PrettyNumber, fix deprecated warning. ([20ec870b](https://github.com/pixelfed/pixelfed/commit/20ec870b))
- Updated landing page, use config_cache. ([54920294](https://github.com/pixelfed/pixelfed/commit/54920294))
- Updated Timeline, implement suggested post opt out. ([66750d34](https://github.com/pixelfed/pixelfed/commit/66750d34))
- Updated Notification component, add at (@) symbol for remote profiles and local urls for remote posts and profile. ([aafd6a21](https://github.com/pixelfed/pixelfed/commit/aafd6a21))
- Updated Activity component, add at (@) symbol for remote profiles and local urls for remote posts and profile. ([a2211815](https://github.com/pixelfed/pixelfed/commit/a2211815))
- Updated Profile, add linkified bio, joined date, follows you label and improved website handling. ([8ee10436](https://github.com/pixelfed/pixelfed/commit/8ee10436))
- Updated routes, add legacy webfinger profile redirect. ([93c7af74](https://github.com/pixelfed/pixelfed/commit/93c7af74))
- Updated StoryController, fix expiration time bug. ([39e57f95](https://github.com/pixelfed/pixelfed/commit/39e57f95))
- Updated Profile component, fix remote urls. ([6e56dbed](https://github.com/pixelfed/pixelfed/commit/6e56dbed))
- Updated verify email screen, add contact admin link. ([f37952d6](https://github.com/pixelfed/pixelfed/commit/f37952d6))
- Updated RemoteProfile component, implement pagination. ([02b04a4b](https://github.com/pixelfed/pixelfed/commit/02b04a4b))
- Updated AP Helpers, generate notification for remote replies. ([8edd8294](https://github.com/pixelfed/pixelfed/commit/8edd8294))
- Updated like api, store status_profile_id and is_comment. ([c8c6b983](https://github.com/pixelfed/pixelfed/commit/c8c6b983))
- Updated Remote Post + Profile hashtag to redirect to local urls. ([1fa08644](https://github.com/pixelfed/pixelfed/commit/1fa08644))
- Updated Inbox, delete notifications on tombstone. ([ef63124d](https://github.com/pixelfed/pixelfed/commit/ef63124d))
- Updated NotificationCard, fix missing status bug. ([a3a86d46](https://github.com/pixelfed/pixelfed/commit/a3a86d46))
- Updated Activity component, fix comment bug. ([9a2db8eb](https://github.com/pixelfed/pixelfed/commit/9a2db8eb))
- Updated Inbox, fix tombstone bug. ([929ff5eb](https://github.com/pixelfed/pixelfed/commit/929ff5eb))
- Updated LikeService, skip self likes. ([3741c76d](https://github.com/pixelfed/pixelfed/commit/3741c76d))
- Updated StatusController, improve share api perf (11s to 72ms). ([d48ebb82](https://github.com/pixelfed/pixelfed/commit/d48ebb82))
- Updated ApiController, fix nulls in  hashtag endpoint. ([f1208de0](https://github.com/pixelfed/pixelfed/commit/f1208de0))
- Updated SharePipeline, add Undo->Announce support. ([c8e40e0f](https://github.com/pixelfed/pixelfed/commit/c8e40e0f))
- Updated NetworkTimeline, fix remote comment urls. ([308acc91](https://github.com/pixelfed/pixelfed/commit/308acc91))
- Updated Timeline component, abstracted reusable partials. ([858f3f9e](https://github.com/pixelfed/pixelfed/commit/858f3f9e))
- Updated Timeline, fix suggested posts. ([3ba5c88c](https://github.com/pixelfed/pixelfed/commit/3ba5c88c))
- Updated Timeline, disable new post update checker and hide reaction bar on network timeline. ([1e3d3a69](https://github.com/pixelfed/pixelfed/commit/1e3d3a69))
- Updated PublicApiController, improve network timeline perf. ([e5f683fd](https://github.com/pixelfed/pixelfed/commit/e5f683fd))
- Updated Network Timeline, use existing Timeline component. ([0deaafc0](https://github.com/pixelfed/pixelfed/commit/0deaafc0))
- Updated PostComponent, show like count to owner using MomentUI. ([e9c46bab](https://github.com/pixelfed/pixelfed/commit/e9c46bab))
- Updated ContextMenu, add missing statusUrl method. ([3cffdb11](https://github.com/pixelfed/pixelfed/commit/3cffdb11))
- Updated PublicApiController, add LikeService to Network timeline. ([82895591](https://github.com/pixelfed/pixelfed/commit/82895591))
- Updated moderator api, expire cached status in StatusService. ([f215ee26](https://github.com/pixelfed/pixelfed/commit/f215ee26))
- Updated StatusHashtagService, fix null status bug. ([51a277e1](https://github.com/pixelfed/pixelfed/commit/51a277e1))
- Updated NotificationService, use zrevrangebyscore for api. ([d43e6d8d](https://github.com/pixelfed/pixelfed/commit/d43e6d8d))
- Updated ApiV1Controller, use PublicTimelineService. ([f67c67bc](https://github.com/pixelfed/pixelfed/commit/f67c67bc))
- Updated ApiV1Controller, use ProfileService for verify_credentials. ([352aa573](https://github.com/pixelfed/pixelfed/commit/352aa573))
- Updated RemotePost.vue, fix content warning button. ([7647e724](https://github.com/pixelfed/pixelfed/commit/7647e724))
- Updated AdminMediaController, improve perf and use simple pagination. ([f2686cac](https://github.com/pixelfed/pixelfed/commit/f2686cac))
- Updated PostComponent, fix MomentUI like counter. ([42c6121a](https://github.com/pixelfed/pixelfed/commit/42c6121a))
- Updated status views, remove like counts from status embed. ([1a2e41b1](https://github.com/pixelfed/pixelfed/commit/1a2e41b1))
- Updated Profile, fix unauthenticated private profiles. ([9017f7c4](https://github.com/pixelfed/pixelfed/commit/9017f7c4))
- Updated PublicApiController, impr home timeline perf. ([4fe42e5b](https://github.com/pixelfed/pixelfed/commit/4fe42e5b))
- Updated Timeline.vue, fix comment button. ([b6b5ce7c](https://github.com/pixelfed/pixelfed/commit/b6b5ce7c))
- Updated StatusEntityLexer, only add specific status types to PublicTimelineService. ([1fdcbe5b](https://github.com/pixelfed/pixelfed/commit/1fdcbe5b))
- Updated ActivityPub helpers, fix comment threading in statusFetch() method ([26b9c140](https://github.com/pixelfed/pixelfed/commit/26b9c140))
- Updated NotificationCard, fix typo in mention, share and comments. Fixes #2848. ([b37bb426](https://github.com/pixelfed/pixelfed/commit/b37bb426))
- Updated StatusCard.vue, add togglecw events to other presenters. ([9607243f](https://github.com/pixelfed/pixelfed/commit/9607243f))
- Updated presenters, fix content warning layout. ([fc56acb8](https://github.com/pixelfed/pixelfed/commit/fc56acb8))
- Updated reply blade view, fix missing avatar and media images. ([5fb33772](https://github.com/pixelfed/pixelfed/commit/5fb33772))
- Updated components, add fallback default avatar. ([726553f5](https://github.com/pixelfed/pixelfed/commit/726553f5))
- Updated job queue, separate deletes into their own queue. ([7f421392](https://github.com/pixelfed/pixelfed/commit/7f421392))
- Updated DiscoverController, use UserFilterService on trendingApi. ([135474ae](https://github.com/pixelfed/pixelfed/commit/135474ae))
- Updated PublicApiController, use UserFilterService in public timeline endpoint. ([ca6e491c](https://github.com/pixelfed/pixelfed/commit/ca6e491c))
- Updated ContextMenu, add View Profile link. ([8544bcbd](https://github.com/pixelfed/pixelfed/commit/8544bcbd))
- Updated presenters, improve content warnings. ([86422c81](https://github.com/pixelfed/pixelfed/commit/86422c81))
- Updated Timeline.vue, increase pagination limit from 3 to 12 and add empty feed placeholder. ([916e8f71](https://github.com/pixelfed/pixelfed/commit/916e8f71))
- Updated Timeline.vue, improve followed hashtags. ([728f10d7](https://github.com/pixelfed/pixelfed/commit/728f10d7))
- Updated PostComponent, use profileUrl method for comments. ([7ed65fc9](https://github.com/pixelfed/pixelfed/commit/7ed65fc9))
- Updated Timeline, fix empty timeline card. ([11eb6acd](https://github.com/pixelfed/pixelfed/commit/11eb6acd))
- Updated ap helpers, set text type when appropriate. ([9f4f983f](https://github.com/pixelfed/pixelfed/commit/9f4f983f))
- Updated StatusCard, add text support. ([ed14ee48](https://github.com/pixelfed/pixelfed/commit/ed14ee48))
- Updated PublicApiController, filter out text replies on home timeline. ([86219b57](https://github.com/pixelfed/pixelfed/commit/86219b57))
- Updated RemotePost.vue, improve text only post UI. ([b0257be2](https://github.com/pixelfed/pixelfed/commit/b0257be2))
- Updated Timeline, make text-only posts opt-in by default. ([0153ed6d](https://github.com/pixelfed/pixelfed/commit/0153ed6d))
- Updated LikeController, add UndoLikePipeline and federate Undo Like activities. ([8ac8fcad](https://github.com/pixelfed/pixelfed/commit/8ac8fcad))
- Updated Settings, add default license and enforced media descriptions. ([67e3f604](https://github.com/pixelfed/pixelfed/commit/67e3f604))
- Updated Compose Apis, make media descriptions/alt text length limit configurable. Default length: 1000. ([072d55d1](https://github.com/pixelfed/pixelfed/commit/072d55d1))
- Updated ApiV1Controller, add default license support. ([2a791f19](https://github.com/pixelfed/pixelfed/commit/2a791f19))
- Updated StatusTransformers, remove includes and use cached services. ([09d5198c](https://github.com/pixelfed/pixelfed/commit/09d5198c))
- Updated RemotePost component, update likes reaction bar. ([1060dd23](https://github.com/pixelfed/pixelfed/commit/1060dd23))
- Updated FollowPipeline, fix cache invalidation bug. ([c1f14f89](https://github.com/pixelfed/pixelfed/commit/c1f14f89))
- Updated PublicApiController, improve accountStatuses api perf. ([bce8edd9](https://github.com/pixelfed/pixelfed/commit/bce8edd9))
- Updated ApiControllers, use NotificationService. ([f9516ac3](https://github.com/pixelfed/pixelfed/commit/f9516ac3))
- Updated Notification components, fix old notifications with missing attributes. ([b6e226ae](https://github.com/pixelfed/pixelfed/commit/b6e226ae))
- Updated LikeController, improve query perf. ([f3d6023e](https://github.com/pixelfed/pixelfed/commit/f3d6023e))
- Updated License util, add nameToId method. ([f6131ed7](https://github.com/pixelfed/pixelfed/commit/f6131ed7))
- Updated RemoteProfile, add warning about potentially out of date information. ([7274574c](https://github.com/pixelfed/pixelfed/commit/7274574c))
- Updated NotifcationCard.vue component, add refresh button for cold notification cache. ([0e178a33](https://github.com/pixelfed/pixelfed/commit/0e178a33))
- Updated RemoteProfile component, add follower modals. ([c4146a30](https://github.com/pixelfed/pixelfed/commit/c4146a30))
- Updated FollowerService, cache audience. ([22257cc2](https://github.com/pixelfed/pixelfed/commit/22257cc2))
- Updated StatusService, add non-public option and improve cache invalidation. ([15c4fdd9](https://github.com/pixelfed/pixelfed/commit/15c4fdd9))
- Updated ContactAdmin mail, set New Support Message subject. ([bc3add05](https://github.com/pixelfed/pixelfed/commit/bc3add05))
- Updated StatusTransformer, prioritize scope over deprecated visibility attribute. ([6e45021f](https://github.com/pixelfed/pixelfed/commit/6e45021f))
- Updated StatusService, invalidate profile embed cache on deletion. ([acaf630d](https://github.com/pixelfed/pixelfed/commit/acaf630d))
- Updated status.reply view, fix archived post leakage. ([4fb3d1fa](https://github.com/pixelfed/pixelfed/commit/4fb3d1fa))
- Updated PostComponents, re-add time to timestamp. ([c5281dcd](https://github.com/pixelfed/pixelfed/commit/c5281dcd))
- Updated follow intent, fix follower count leak. ([03199e2f](https://github.com/pixelfed/pixelfed/commit/03199e2f))
- Updated Status model, add poll relation and allow up to 2 urls to autolink. ([2593cdee](https://github.com/pixelfed/pixelfed/commit/2593cdee))
- Updated snowflake id generation to improve randomness. ([e5aea490](https://github.com/pixelfed/pixelfed/commit/e5aea490))
- Updated Timeline, remove recent posts. ([7641b731](https://github.com/pixelfed/pixelfed/commit/7641b731))
- Updated InstanceCrawlPipeline, remove unused variable. ([e73cf531](https://github.com/pixelfed/pixelfed/commit/e73cf531))
- Updated StoryComposeController, fix expiry bug. ([7dee8f58](https://github.com/pixelfed/pixelfed/commit/7dee8f58))
- Updated Profile, fix following count bug. ([ee9f0795](https://github.com/pixelfed/pixelfed/commit/ee9f0795))
- Updated DirectMessageController, fix autocomplete bug. ([0f00be4d](https://github.com/pixelfed/pixelfed/commit/0f00be4d))
- Updated StoryService, fix division by zero bug. ([6ae1ba0a](https://github.com/pixelfed/pixelfed/commit/6ae1ba0a))
- Updated ApiV1Controller, fix empty public timeline bug. ([0584f9ee](https://github.com/pixelfed/pixelfed/commit/0584f9ee))

## [v0.11.0 (2021-06-01)](https://github.com/pixelfed/pixelfed/compare/v0.10.10...v0.11.0)
### Added
- Autocomplete Support (hashtags + mentions) ([de514f7d](https://github.com/pixelfed/pixelfed/commit/de514f7d))
- Creative Commons Licenses ([552e950](https://github.com/pixelfed/pixelfed/commit/552e950))
- Network Timeline ([af7face4](https://github.com/pixelfed/pixelfed/commit/af7face4))
- Admin config settings ([f2066b74](https://github.com/pixelfed/pixelfed/commit/f2066b74))
- Profile pronouns ([fabb57a9](https://github.com/pixelfed/pixelfed/commit/fabb57a9))
- Hashtag timeline api support ([241ae036](https://github.com/pixelfed/pixelfed/commit/241ae036))
- New admin dashboard layout ([eb7d5a4e](https://github.com/pixelfed/pixelfed/commit/eb7d5a4e))
- Fresh about page layout ([92dc7af6](https://github.com/pixelfed/pixelfed/commit/92dc7af6))
- Instance Rules ([a4efbb75](https://github.com/pixelfed/pixelfed/commit/a4efbb75))
- New Home Timeline ([56215be7](https://github.com/pixelfed/pixelfed/commit/56215be7))

### Updated
- Updated AdminController, fix variable name in updateSpam method. ([6edaf940](https://github.com/pixelfed/pixelfed/commit/6edaf940))
- Updated RemoteAvatarFetch, only dispatch jobs if cloud storage is enabled. ([4f40f6f5](https://github.com/pixelfed/pixelfed/commit/4f40f6f5))
- Updated StatusService, add ttl of 7 days. ([6e44ae0b](https://github.com/pixelfed/pixelfed/commit/6e44ae0b))
- Updated StatusHashtagService, use StatusService for statuses. ([0355b567](https://github.com/pixelfed/pixelfed/commit/0355b567))
- Updated StatusHashtagService, remove deprecated methods. ([aa4c718d](https://github.com/pixelfed/pixelfed/commit/aa4c718d))
- Updated ApiV1Controller, add StatusService del calls to update likes_count, reblogs_count and reply_count. ([05b9445c](https://github.com/pixelfed/pixelfed/commit/05b9445c))
- Updated Like, Status and Comment controllers to add StatusService del() method to update counts. ([eab4370c](https://github.com/pixelfed/pixelfed/commit/eab4370c))
- Updated ComposeController, use placeholder image for video media. Fixes #2595. ([789ed4b4](https://github.com/pixelfed/pixelfed/commit/789ed4b4))
- Updated DiscoverController, change api schema. ([2eea0409](https://github.com/pixelfed/pixelfed/commit/2eea0409))
- Updated StatusDelete pipeline, call StatusService::del() to remove status from cache. ([3f772ff8](https://github.com/pixelfed/pixelfed/commit/3f772ff8))
- Updated StatusHashtagTransformer, add blurhash attribute. ([899bbeba](https://github.com/pixelfed/pixelfed/commit/899bbeba))
- Updated status square previews, add blurhash and improved content warnings. ([39e389dd](https://github.com/pixelfed/pixelfed/commit/39e389dd))
- Updated Blurhash util, add default hash for invalid media. ([38a37c15](https://github.com/pixelfed/pixelfed/commit/38a37c15))
- Updated VideoThumbnail job, generate blurhash for videos. ([896452c7](https://github.com/pixelfed/pixelfed/commit/896452c7))
- Updated MediaTransformers, add default blurhash attribute. ([3f14a4c4](https://github.com/pixelfed/pixelfed/commit/3f14a4c4))
- Updated Timeline.vue, fix hashtag status previews. ([7768e844](https://github.com/pixelfed/pixelfed/commit/7768e844))
- Updated AP helpers, fix statusFetch 404s. ([3419379a](https://github.com/pixelfed/pixelfed/commit/3419379a))
- Updated InternalApiController, update discoverPosts method to improve performance. ([9862a855](https://github.com/pixelfed/pixelfed/commit/9862a855))
- Updated DiscoverComponent, add blurhash and like/comment counts. ([a8ebdd2e](https://github.com/pixelfed/pixelfed/commit/a8ebdd2e))
- Updated DiscoverComponent, add spinner loaders and remove deprecated sections. ([34869247](https://github.com/pixelfed/pixelfed/commit/34869247))
- Updated AccountController, add mutes and blocks endpoint to pixelfed api. ([1fb7e2b2](https://github.com/pixelfed/pixelfed/commit/1fb7e2b2))
- Updated AccountService, cache object and observe changes. ([b299da93](https://github.com/pixelfed/pixelfed/commit/b299da93))
- Updated webfinger util, fail on invalid webfinger url. Fixes ([#2613](https://github.com/pixelfed/pixelfed/issues/2613)) ([2d11317c](https://github.com/pixelfed/pixelfed/commit/2d11317c))
- Updated MediaStorageService, dispatch deletes to MediaDeletePipeline. ([37dbb3de](https://github.com/pixelfed/pixelfed/commit/37dbb3de))
- Updated ComposeController, use MediaStorageService for media deletes. ([ab5469ff](https://github.com/pixelfed/pixelfed/commit/ab5469ff))
- Updated StatusDeletePipeline, use MediaStorageService for media deletes. ([9fd90e17](https://github.com/pixelfed/pixelfed/commit/9fd90e17))
- Updated Discover, allow public discover access. ([1404ac6e](https://github.com/pixelfed/pixelfed/commit/1404ac6e))
- Updated pixelfed config, add media_fast_process setting. ([6bee5072](https://github.com/pixelfed/pixelfed/commit/6bee5072))
- Updated ComposeController, add mediaProcessingCheck method. ([33b625f5](https://github.com/pixelfed/pixelfed/commit/33b625f5))
- Updated ComposeModal, add processing step disabled by default. ([e6e76e80](https://github.com/pixelfed/pixelfed/commit/e6e76e80))
- Updated DiscoverComponent, allow unauthenticated if enabled. ([a1059a6e](https://github.com/pixelfed/pixelfed/commit/a1059a6e))
- Updated components, improve content warnings. ([a9e98965](https://github.com/pixelfed/pixelfed/commit/a9e98965))
- Updated ComposeModal, prevent tagging empty users. Fixes #2633. ([ceae664c](https://github.com/pixelfed/pixelfed/commit/ceae664c))
- Updated ComposeModal, show filter warning for unsupported browsers. ([12ce7602](https://github.com/pixelfed/pixelfed/commit/12ce7602))
- Updated Hashtag component, fix null infinite loading bug. Fixes #2637. ([55136518](https://github.com/pixelfed/pixelfed/commit/55136518))
- Updated filesystems config, add backup driver to store backups on other filesystems. ([ae90eef9](https://github.com/pixelfed/pixelfed/commit/ae90eef9))
- Updated Embeds. Fix Profile + Status embeds, remove following count and improve cache invalidation and hidden follower counts. ([5ac9d0e8](https://github.com/pixelfed/pixelfed/commit/5ac9d0e8))
- Updated FederationController, return 404 for invalid webfinger addresses. Fixes ([#2647](https://github.com/pixelfed/pixelfed/issues/2647)). ([deb6f115](https://github.com/pixelfed/pixelfed/commit/deb6f115))
- Updated InboxPipeline, fail earlier for invalid public keys. Fixes ([#2648](https://github.com/pixelfed/pixelfed/issues/2648)). ([d1c5e9b8](https://github.com/pixelfed/pixelfed/commit/d1c5e9b8))
- Updated Status model, refactor liked and shared methods to fix cache invalidation bug. ([f05c3b66](https://github.com/pixelfed/pixelfed/commit/f05c3b66))
- Updated Timeline component, add inline reports modal. ([e64b4bd3](https://github.com/pixelfed/pixelfed/commit/e64b4bd3))
- Updated federation pipeline, add locks. ([ddc76887](https://github.com/pixelfed/pixelfed/commit/ddc76887))
- Updated MediaStorageService, improve head checks to fix failed jobs. ([1769cdfd](https://github.com/pixelfed/pixelfed/commit/1769cdfd))
- Updated user admin, remove expensive db query and add search. ([8feeadbf](https://github.com/pixelfed/pixelfed/commit/8feeadbf))
- Updated Compose apis, prevent private accounts from posting public or unlisted scopes. ([f53bfa6f](https://github.com/pixelfed/pixelfed/commit/f53bfa6f))
- Updated font icons, use font-display:swap. ([77d4353a](https://github.com/pixelfed/pixelfed/commit/77d4353a))
- Updated ComposeModal, limit visibility scope for private accounts. ([001d4105](https://github.com/pixelfed/pixelfed/commit/001d4105))
- Updated ComposeController, add autocomplete apis for hashtags and mentions. ([f0e48a09](https://github.com/pixelfed/pixelfed/commit/f0e48a09))
- Updated StatusController, invalidate profile embed cache on status delete. ([9c8a87c3](https://github.com/pixelfed/pixelfed/commit/9c8a87c3))
- Updated moderation api, invalidate profile embed. ([b2501bfc](https://github.com/pixelfed/pixelfed/commit/b2501bfc))
- Updated Nodeinfo util, use last_active_at for monthly active user count. ([d200c12c](https://github.com/pixelfed/pixelfed/commit/d200c12c))
- Updated PhotoPresenter, add width and height to images. ([3f8202e2](https://github.com/pixelfed/pixelfed/commit/3f8202e2))
- Updated Compose Apis, refactor rate limits. ([42375b3d](https://github.com/pixelfed/pixelfed/commit/42375b3d))
- Updated PublicApiController, show unlisted comments. ([e1c6297e](https://github.com/pixelfed/pixelfed/commit/e1c6297e))
- Updated ApiV1Controller, add missing variable. ([886ea617](https://github.com/pixelfed/pixelfed/commit/886ea617))
- Updated PublicApiController, limit network pagination to 3 months. ([10119bbb](https://github.com/pixelfed/pixelfed/commit/10119bbb))
- Updated admin instance page, add search and improve performance. ([f5829373](https://github.com/pixelfed/pixelfed/commit/f5829373))
- Updated AdminInstanceController, invalidate banned domain cache when updated. ([35393edf](https://github.com/pixelfed/pixelfed/commit/35393edf))
- Updated AP Helpers, use instance filtering. ([66b4f8c7](https://github.com/pixelfed/pixelfed/commit/66b4f8c7))
- Updated ApiV1Controller, add missing instance api attributes. ([64b86546](https://github.com/pixelfed/pixelfed/commit/64b86546))
- Updated story garbage collection, handle non active stories and new ephemeral story media directory. ([c43f8bcc](https://github.com/pixelfed/pixelfed/commit/c43f8bcc))
- Updated Stories, add crop and duration settings to composer. ([c8edca69](https://github.com/pixelfed/pixelfed/commit/c8edca69))
- Updated instance endpoint, add custom description. ([668e936e](https://github.com/pixelfed/pixelfed/commit/668e936e))
- Updated StoryCompose component, improve full screen preview. ([39a76103](https://github.com/pixelfed/pixelfed/commit/39a76103))
- Updated Helpers, fix broken tests. ([22dddaa0](https://github.com/pixelfed/pixelfed/commit/22dddaa0))
- Updated StoryController, fix cache crop bug. ([c2f8faae](https://github.com/pixelfed/pixelfed/commit/c2f8faae))
- Updated StoryController, optimize photo size by resizing to 9:16 aspect. ([e66ed9a2](https://github.com/pixelfed/pixelfed/commit/e66ed9a2))
- Updated StoryCompose crop logic. ([2ead622c](https://github.com/pixelfed/pixelfed/commit/2ead622c))
- Updated StatusController, allow license edits without 24 hour limit. ([c799a01a](https://github.com/pixelfed/pixelfed/commit/c799a01a))
- Updated Settings, remove reports page. ([9cf962ff](https://github.com/pixelfed/pixelfed/commit/9cf962ff))
- Updated ProfileService, use account transformer. ([391b1287](https://github.com/pixelfed/pixelfed/commit/391b1287))
- Updated LikeController, hide like counts. ([ea687240](https://github.com/pixelfed/pixelfed/commit/ea687240))
- Updated StatusTransformers, add liked_by attribute. ([372bacb0](https://github.com/pixelfed/pixelfed/commit/372bacb0))
- Updated PostComponent, change like logic. ([0a35f5d6](https://github.com/pixelfed/pixelfed/commit/0a35f5d6))
- Updated Timeline component, change like logic. ([7bcbf96b](https://github.com/pixelfed/pixelfed/commit/7bcbf96b))
- Updated LikeService, fix likedBy method. ([a5e64da6](https://github.com/pixelfed/pixelfed/commit/a5e64da6))
- Updated PublicApiController, increase public timeline to 6 months from 3. ([8a736432](https://github.com/pixelfed/pixelfed/commit/8a736432))
- Updated LikeService, show like count to status owner. ([4408e2ef](https://github.com/pixelfed/pixelfed/commit/4408e2ef))
- Updated admin settings, add rules. ([a4efbb75](https://github.com/pixelfed/pixelfed/commit/a4efbb75))
- Updated LikeService, fix authentication bug. ([c9abd70e](https://github.com/pixelfed/pixelfed/commit/c9abd70e))
- Updated StatusTransformer, fix missing tags attribute. ([dac326e9](https://github.com/pixelfed/pixelfed/commit/dac326e9))
- Updated ComposeController, bail on empty attachments. ([061b145b](https://github.com/pixelfed/pixelfed/commit/061b145b))
- Updated landing and about page. ([92dc7af6](https://github.com/pixelfed/pixelfed/commit/92dc7af6))
- Updated AdminStatsService, fix postgres bug. ([af719135](https://github.com/pixelfed/pixelfed/commit/af719135))
- Updated api, remove auth requirement for hashtag timeline. ([c8e43c60](https://github.com/pixelfed/pixelfed/commit/c8e43c60))
- Updated NotificationCard component, fix default value. ([78ad4e77](https://github.com/pixelfed/pixelfed/commit/78ad4e77))
- Updated Timeline component, show counts and make sidebar footer lighter. ([0788bffa](https://github.com/pixelfed/pixelfed/commit/0788bffa))
- Updated AuthServiceProvider, increase default token + refresh token lifetime. ([178ed63d](https://github.com/pixelfed/pixelfed/commit/178ed63d))
- Updated liked by, fix remote username urls. ([f767d99a](https://github.com/pixelfed/pixelfed/commit/f767d99a))
- Updated StatusController, add cache invalidation for timeline cursor. ([f3bf2fd4](https://github.com/pixelfed/pixelfed/commit/f3bf2fd4))
- Updated PublicApiController, add recent feed support to home timeline. ([1e230e80](https://github.com/pixelfed/pixelfed/commit/1e230e80))
- Updated Inbox, fix reply/comment bug by moving attachment validation to Note with attachments. ([28df9f7e](https://github.com/pixelfed/pixelfed/commit/28df9f7e))
- Updated PrettyNumber, add decimal option. ([84520fe1](https://github.com/pixelfed/pixelfed/commit/84520fe1))
- Updated app config, change default descriptions. ([7d24560d](https://github.com/pixelfed/pixelfed/commit/7d24560d))
- Updated NotificationCard, fix loading bug. ([69567e19](https://github.com/pixelfed/pixelfed/commit/69567e19))
- Updated DirectMessageController, disable exception logging for invalid urls. Fixes ([#2752](https://github.com/pixelfed/pixelfed/issues/2752)). ([2d0a253e](https://github.com/pixelfed/pixelfed/commit/2d0a253e))

## [v0.10.10 (2021-01-28)](https://github.com/pixelfed/pixelfed/compare/v0.10.9...v0.10.10)
### Added
- Direct Messages ([d63569c](https://github.com/pixelfed/pixelfed/commit/d63569c))
- ActivityPubFetchService for signed GET requests ([8763bfc5](https://github.com/pixelfed/pixelfed/commit/8763bfc5)) ([3ee1215a](https://github.com/pixelfed/pixelfed/commit/3ee1215a))
- Custom content warnings for remote posts ([6afc61a4](https://github.com/pixelfed/pixelfed/commit/6afc61a4))
- Thai translations ([74cd536](https://github.com/pixelfed/pixelfed/commit/74cd536))
- Added Bookmarks to v1 api ([99cb48c5](https://github.com/pixelfed/pixelfed/commit/99cb48c5))
- Added New Post notification to Timeline ([a0e7c4d5](https://github.com/pixelfed/pixelfed/commit/a0e7c4d5))
- Add Instagram Import ([e2a6bdd0](https://github.com/pixelfed/pixelfed/commit/e2a6bdd0))
- Add notification preview to NotificationCard ([28445e27](https://github.com/pixelfed/pixelfed/commit/28445e27))
- Add MediaPathService ([c54b29c5](https://github.com/pixelfed/pixelfed/commit/c54b29c5))
- Add Media Tags ([711fc020](https://github.com/pixelfed/pixelfed/commit/711fc020))
- Add MediaTagService ([524c6d45](https://github.com/pixelfed/pixelfed/commit/524c6d45))
- Add MediaBlocklist feature ([ba1f7e7e](https://github.com/pixelfed/pixelfed/commit/ba1f7e7e))
- New Discover Layout, add trending hashtags, places and posts ([c251d41b](https://github.com/pixelfed/pixelfed/commit/c251d41b))
- Add Password change email notification ([de1cca4f](https://github.com/pixelfed/pixelfed/commit/de1cca4f))
- Add shared inbox ([4733ca9f](https://github.com/pixelfed/pixelfed/commit/4733ca9f))
- Add federated photo filters ([0a5a0e86](https://github.com/pixelfed/pixelfed/commit/0a5a0e86))
- Add AccountInterstitial model and controller ([8766ccfe](https://github.com/pixelfed/pixelfed/commit/8766ccfe))
- Add Blurhash encoder ([fad102bf](https://github.com/pixelfed/pixelfed/commit/fad102bf))
- Add autospam feature ([b892bcf0](https://github.com/pixelfed/pixelfed/commit/b892bcf0))
- Add hCaptcha ([082c1ccb](https://github.com/pixelfed/pixelfed/commit/082c1ccb))
- Add StatusView model to store views for discover algorithm ([7a68ee94](https://github.com/pixelfed/pixelfed/commit/7a68ee94))
- Add Year in Review feature (mysql only) ([f32072a3](https://github.com/pixelfed/pixelfed/commit/f32072a3))

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
- Updated Status model, fix thumb nsfw caching. ([327ef138](https://github.com/pixelfed/pixelfed/commit/327ef138))
- Updated User model, add interstitial relation. ([bd321a72](https://github.com/pixelfed/pixelfed/commit/bd321a72))
- Updated StatusStatelessTransformer, add missing attributes. ([4d22426d](https://github.com/pixelfed/pixelfed/commit/4d22426d))
- Updated media pipeline, add blurhash support. ([473e0495](https://github.com/pixelfed/pixelfed/commit/473e0495))
- Updated DeleteAccountPipeline, add AccountInterstitial and DirectMessage purging. ([b3078f27](https://github.com/pixelfed/pixelfed/commit/b3078f27))
- Updated ComposeModal.vue component, reuse sharedData. ([e28d022f](https://github.com/pixelfed/pixelfed/commit/e28d022f))
- Updated ApiController, return status object after deletion. ([0718711d](https://github.com/pixelfed/pixelfed/commit/0718711d))
- Updated InternalApiController, add interstitial logic. ([20681bcf](https://github.com/pixelfed/pixelfed/commit/20681bcf))
- Updated PublicApiController, improve stateless object caching. ([342e7a50](https://github.com/pixelfed/pixelfed/commit/342e7a50))
- Updated StatusController, add interstitial logic. ([003caf7e](https://github.com/pixelfed/pixelfed/commit/003caf7e))
- Updated middleware, add AccountInterstitial support. ([19d6e7df](https://github.com/pixelfed/pixelfed/commit/19d6e7df))
- Updated BaseApiController, add favourites method. ([76353ca9](https://github.com/pixelfed/pixelfed/commit/76353ca9))
- Updated dockerfile, fix composer issue. ([ef45c4b21](https://github.com/pixelfed/pixelfed/commit/ef45c4b21))
- Updated reply/comment view, improve layout and include child reply. ([2eca670e](https://github.com/pixelfed/pixelfed/commit/2eca670e))
- Updated Collections, add custom limit. ([048642be](https://github.com/pixelfed/pixelfed/commit/048642be))
- Updated AccountInterstitialController, add autospam type. ([c67f0c57](https://github.com/pixelfed/pixelfed/commit/c67f0c57))
- Updated Profile model, improve counter caching. ([4a14e970](https://github.com/pixelfed/pixelfed/commit/4a14e970))
- Updated ComposeModal, fix filter bug on safari. ([8e3e7586](https://github.com/pixelfed/pixelfed/commit/8e3e7586))
- Updated StatusStatelessController, remove unused attributes. ([d0d46807](https://github.com/pixelfed/pixelfed/commit/d0d46807))
- Updated Profile, fix follower counter bug. ([d06bec9c](https://github.com/pixelfed/pixelfed/commit/d06bec9c))
- Updated NotificationTransformer, add missing types. ([3a428366](https://github.com/pixelfed/pixelfed/commit/3a428366))
- Updated StatusService, fix json bug. ([1ea2db74](https://github.com/pixelfed/pixelfed/commit/1ea2db74))
- Updated NotificationTransformer, handle tagged deletes. ([881fa865](https://github.com/pixelfed/pixelfed/commit/881fa865))
- Updated horizon config, add new default values. ([90c8a721](https://github.com/pixelfed/pixelfed/commit/90c8a721))
- Updated ComposeModal, add maxlength attribute to alt text input. Fixes ([#2490](https://github.com/pixelfed/pixelfed/issues/2490)). ([526b5531](https://github.com/pixelfed/pixelfed/commit/526b5531))
- Updated PublicApiController, add state endpoint. ([9fc5a80c](https://github.com/pixelfed/pixelfed/commit/9fc5a80c))
- Updated PostComponent, add reply modal. ([a10d851f](https://github.com/pixelfed/pixelfed/commit/a10d851f))
- Updated Timeline, remove simple mode and set labs deprecation date. ([df9c3adf](https://github.com/pixelfed/pixelfed/commit/df9c3adf))
- Updated 2FA setup, fix qrcode handler. ([cd2661fc](https://github.com/pixelfed/pixelfed/commit/cd2661fc))
- Updated avatars, use jpeg default. ([f6528c84](https://github.com/pixelfed/pixelfed/commit/f6528c84))
- Updated antispam bouncer, change recent from 1 week to 3 months. ([7d818197](https://github.com/pixelfed/pixelfed/commit/7d818197))
- Updated Post components, fix remote post and profile urls. ([cfcf17f3](https://github.com/pixelfed/pixelfed/commit/cfcf17f3))
- Updated migrations, fix broken oauth change. ([4a885c88](https://github.com/pixelfed/pixelfed/commit/4a885c88))
- Updated LikeController, store status_profile_id and is_comment attributes. ([799a4cba](https://github.com/pixelfed/pixelfed/commit/799a4cba))
- Updated Profile, fix status count. ([6dcd472b](https://github.com/pixelfed/pixelfed/commit/6dcd472b))
- Updated StatusService, cast response to array. ([0fbde91e](https://github.com/pixelfed/pixelfed/commit/0fbde91e))
- Updated status model, use scope over deprecated visibility attribute. ([f70826e1](https://github.com/pixelfed/pixelfed/commit/f70826e1))
- Updated Follower model, increase hourly limit from 30 to 150. ([b9b84e6f](https://github.com/pixelfed/pixelfed/commit/b9b84e6f))
- Updated StatusController, fix scope bug. ([7dc3739c](https://github.com/pixelfed/pixelfed/commit/7dc3739c))
- Updated AP helpers, fixed federation bug. ([a52564f3](https://github.com/pixelfed/pixelfed/commit/a52564f3))
- Updated Helpers, cache profiles. ([1f672ecf](https://github.com/pixelfed/pixelfed/commit/1f672ecf))
- Updated DiscoverController, improve trending api performance. ([d8d3331f](https://github.com/pixelfed/pixelfed/commit/d8d3331f))
- Updated InboxWorker, fix race condition in account deletes. ([4a4d8f00](https://github.com/pixelfed/pixelfed/commit/4a4d8f00))
- Updated StoryItemTransformer, increase story duration from 5 seconds to 10 seconds. ([5b0b14fc](https://github.com/pixelfed/pixelfed/commit/5b0b14fc))
- Updated StatusController, add view method. ([0cfc12c5](https://github.com/pixelfed/pixelfed/commit/0cfc12c5))
- Updated MediaPathService, add story method. ([aac44309](https://github.com/pixelfed/pixelfed/commit/aac44309))
- Updated StatusDelete job, handle cloud storage media deletes. ([4b1a0fd7](https://github.com/pixelfed/pixelfed/commit/4b1a0fd7))
- Updated ImageOptimizePipeline, add skip_optimize and MediaStorageService support. ([234f72f3](https://github.com/pixelfed/pixelfed/commit/234f72f3))
- Updated Media model, add cdn support to url and thumbnailUrl methods. ([57fa889d](https://github.com/pixelfed/pixelfed/commit/57fa889d))
- Updated MediaController, remove deprecated endpoint. ([8132db74](https://github.com/pixelfed/pixelfed/commit/8132db74))
- Updated api controllers, deprecate old endpoints. ([4415af1b](https://github.com/pixelfed/pixelfed/commit/4415af1b))
- Updated mobile apis, add blurhash. ([cf40526e](https://github.com/pixelfed/pixelfed/commit/cf40526e))
- Updated Image media util, store dimensions of media not thumbnail. ([40bd64aa](https://github.com/pixelfed/pixelfed/commit/40bd64aa))
- Updated MediaTransformers, include meta attribute with focus and dimensions. ([f8cbe1e4](https://github.com/pixelfed/pixelfed/commit/f8cbe1e4))
- Updated storage, add remote media cache directory. ([0eabbfdd](https://github.com/pixelfed/pixelfed/commit/0eabbfdd))
- Updated backup config, prevents gateway timeouts for large databases using mysql. ([9cd4bd74](https://github.com/pixelfed/pixelfed/commit/9cd4bd74))
- Updated MediaPipeline, handle cloud object storage. ([be6d12fc](https://github.com/pixelfed/pixelfed/commit/be6d12fc))
- Updated AP Helpers, use MediaStoragePipeline. ([01a1ffd6](https://github.com/pixelfed/pixelfed/commit/01a1ffd6))
- Updated RemoteProfile component, change thumbnail url. ([c1118956](https://github.com/pixelfed/pixelfed/commit/c1118956))
- Updated blade views. ([9683e846](https://github.com/pixelfed/pixelfed/commit/9683e846))
- Updated cache config, use phpredis by default. ([ed6877df](https://github.com/pixelfed/pixelfed/commit/ed6877df))
- Updated components, fix url rewriter. Closes #2538. ([e8cc66dc](https://github.com/pixelfed/pixelfed/commit/e8cc66dc))
- Updated UserCreate command, closes #2581. ([b2b8c9f9](https://github.com/pixelfed/pixelfed/commit/b2b8c9f9))
- Updated AvatarController, remove deprecated thumb_path. ([889c3d87](https://github.com/pixelfed/pixelfed/commit/889c3d87))
- Updated VideoThumbnail, add MediaStoragePipeline. ([98c44f7b](https://github.com/pixelfed/pixelfed/commit/98c44f7b))
- Updated StatusDelete pipeline, fix object storage thumbnail deletion. ([f930c4bd](https://github.com/pixelfed/pixelfed/commit/f930c4bd))
- Updated MediaStorageService, clear transformer cache after storing media. ([ce6ab80d](https://github.com/pixelfed/pixelfed/commit/ce6ab80d))
- Updated MediaTransformer, remove cache busting. ([258b2729](https://github.com/pixelfed/pixelfed/commit/258b2729))
- Updated AP helpers, only run MediaStoragePipeline if using cloud storage. ([77f21b4b](https://github.com/pixelfed/pixelfed/commit/77f21b4b))
- Updated AvatarObserver, add logic to delete avatars stored in S3. ([9eafc31e](https://github.com/pixelfed/pixelfed/commit/9eafc31e))
- Updated Profile model, use cdn_url for avatars. ([ea8e4261](https://github.com/pixelfed/pixelfed/commit/ea8e4261))
- Updated ActivityPubFetchService, add url validation. ([654b08d3](https://github.com/pixelfed/pixelfed/commit/654b08d3))
- Updated MediaStorageService, add avatar method. ([94a9f685](https://github.com/pixelfed/pixelfed/commit/94a9f685))
- Updated AvatarPipeline, add remote avatar fetch. ([4c148055](https://github.com/pixelfed/pixelfed/commit/4c148055))
- Updated ComposeController, update media version. ([cc2d4bf8](https://github.com/pixelfed/pixelfed/commit/cc2d4bf8))
- Updated AP Helpers, add blurhash and RemoteAvatarFetch. ([de8828e8](https://github.com/pixelfed/pixelfed/commit/de8828e8))
- Updated Timeline, prevent nextTick() when reloading same comment modal. Fixes #2584. ([cc84125b](https://github.com/pixelfed/pixelfed/commit/cc84125b))
- Updated site config, add labels to config. ([abe9cb3d](https://github.com/pixelfed/pixelfed/commit/abe9cb3d))
- Update StatusLabelService, change config key. ([4abfe76a](https://github.com/pixelfed/pixelfed/commit/4abfe76a))


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
