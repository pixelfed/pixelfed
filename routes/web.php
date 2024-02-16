<?php

Route::domain(config('pixelfed.domain.app'))->middleware(['validemail', 'twofactor', 'localization'])->group(function () {
    Route::get('/', 'SiteController@home')->name('timeline.personal');
    Route::redirect('/home', '/')->name('home');
    Route::get('web/directory', 'LandingController@directoryRedirect');
    Route::get('web/explore', 'LandingController@exploreRedirect');

    Auth::routes();
    Route::get('auth/raw/mastodon/start', 'RemoteAuthController@startRedirect');
    Route::post('auth/raw/mastodon/config', 'RemoteAuthController@getConfig');
    Route::post('auth/raw/mastodon/domains', 'RemoteAuthController@getAuthDomains');
    Route::post('auth/raw/mastodon/start', 'RemoteAuthController@start');
    Route::post('auth/raw/mastodon/redirect', 'RemoteAuthController@redirect');
    Route::get('auth/raw/mastodon/preflight', 'RemoteAuthController@preflight');
    Route::get('auth/mastodon/callback', 'RemoteAuthController@handleCallback');
    Route::get('auth/mastodon/getting-started', 'RemoteAuthController@onboarding');
    Route::post('auth/raw/mastodon/s/check', 'RemoteAuthController@sessionCheck');
    Route::post('auth/raw/mastodon/s/prefill', 'RemoteAuthController@sessionGetMastodonData');
    Route::post('auth/raw/mastodon/s/username-check', 'RemoteAuthController@sessionValidateUsername');
    Route::post('auth/raw/mastodon/s/email-check', 'RemoteAuthController@sessionValidateEmail');
    Route::post('auth/raw/mastodon/s/following', 'RemoteAuthController@sessionGetMastodonFollowers');
    Route::post('auth/raw/mastodon/s/submit', 'RemoteAuthController@handleSubmit');
    Route::post('auth/raw/mastodon/s/store-bio', 'RemoteAuthController@storeBio');
    Route::post('auth/raw/mastodon/s/store-avatar', 'RemoteAuthController@storeAvatar');
    Route::post('auth/raw/mastodon/s/account-to-id', 'RemoteAuthController@accountToId');
    Route::post('auth/raw/mastodon/s/finish-up', 'RemoteAuthController@finishUp');
    Route::post('auth/raw/mastodon/s/login', 'RemoteAuthController@handleLogin');
    Route::get('auth/pci/{id}/{code}', 'ParentalControlsController@inviteRegister');
    Route::post('auth/pci/{id}/{code}', 'ParentalControlsController@inviteRegisterStore');

    // Route::get('auth/sign_up', 'CuratedRegisterController@index');
    // Route::post('auth/sign_up', 'CuratedRegisterController@proceed');
    // Route::get('auth/sign_up/concierge/response-sent', 'CuratedRegisterController@conciergeResponseSent');
    // Route::get('auth/sign_up/concierge', 'CuratedRegisterController@concierge');
    // Route::post('auth/sign_up/concierge', 'CuratedRegisterController@conciergeStore');
    // Route::get('auth/sign_up/concierge/form', 'CuratedRegisterController@conciergeFormShow');
    // Route::post('auth/sign_up/concierge/form', 'CuratedRegisterController@conciergeFormStore');
    // Route::get('auth/sign_up/confirm', 'CuratedRegisterController@confirmEmail');
    // Route::post('auth/sign_up/confirm', 'CuratedRegisterController@confirmEmailHandle');
    // Route::get('auth/sign_up/confirmed', 'CuratedRegisterController@emailConfirmed');
    Route::get('auth/forgot/email', 'UserEmailForgotController@index')->name('email.forgot');
    Route::post('auth/forgot/email', 'UserEmailForgotController@store')->middleware('throttle:10,900,forgotEmail');

    Route::get('discover', 'DiscoverController@home')->name('discover');

    Route::get('discover/tags/{hashtag}', 'DiscoverController@showTags');
    Route::get('discover/places', 'PlaceController@directoryHome')->name('discover.places');
    Route::get('discover/places/{id}/{slug}', 'PlaceController@show');
    Route::get('discover/location/country/{country}', 'PlaceController@directoryCities');

    Route::group(['prefix' => 'i'], function () {
        Route::redirect('/', '/');
        Route::get('compose', 'StatusController@compose')->name('compose');
        Route::post('comment', 'CommentController@store');
        Route::post('delete', 'StatusController@delete');
        Route::post('mute', 'AccountController@mute');
        Route::post('unmute', 'AccountController@unmute');
        Route::post('block', 'AccountController@block');
        Route::post('unblock', 'AccountController@unblock');
        Route::post('like', 'LikeController@store');
        Route::post('share', 'StatusController@storeShare');
        Route::post('follow', 'FollowerController@store');
        Route::post('bookmark', 'BookmarkController@store');
        Route::get('lang/{locale}', 'SiteController@changeLocale');
        Route::get('restored', 'AccountController@accountRestored');

        Route::get('verify-email', 'AccountController@verifyEmail');
        Route::post('verify-email', 'AccountController@sendVerifyEmail');
        Route::get('verify-email/request', 'InternalApiController@requestEmailVerification');
        Route::post('verify-email/request', 'InternalApiController@requestEmailVerificationStore');
        Route::get('confirm-email/{userToken}/{randomToken}', 'AccountController@confirmVerifyEmail');

        Route::get('auth/sudo', 'AccountController@sudoMode');
        Route::post('auth/sudo', 'AccountController@sudoModeVerify');
        Route::get('auth/checkpoint', 'AccountController@twoFactorCheckpoint');
        Route::post('auth/checkpoint', 'AccountController@twoFactorVerify');

        Route::get('results', 'SearchController@results');
        Route::post('visibility', 'StatusController@toggleVisibility');

        Route::post('metro/dark-mode', 'SettingsController@metroDarkMode');

        Route::group(['prefix' => 'report'], function () {
            Route::get('/', 'ReportController@showForm')->name('report.form');
            Route::post('/', 'ReportController@formStore');
            Route::get('not-interested', 'ReportController@notInterestedForm')->name('report.not-interested');
            Route::get('spam', 'ReportController@spamForm')->name('report.spam');
            Route::get('spam/comment', 'ReportController@spamCommentForm')->name('report.spam.comment');
            Route::get('spam/post', 'ReportController@spamPostForm')->name('report.spam.post');
            Route::get('spam/profile', 'ReportController@spamProfileForm')->name('report.spam.profile');
            Route::get('sensitive/comment', 'ReportController@sensitiveCommentForm')->name('report.sensitive.comment');
            Route::get('sensitive/post', 'ReportController@sensitivePostForm')->name('report.sensitive.post');
            Route::get('sensitive/profile', 'ReportController@sensitiveProfileForm')->name('report.sensitive.profile');
            Route::get('abusive/comment', 'ReportController@abusiveCommentForm')->name('report.abusive.comment');
            Route::get('abusive/post', 'ReportController@abusivePostForm')->name('report.abusive.post');
            Route::get('abusive/profile', 'ReportController@abusiveProfileForm')->name('report.abusive.profile');
        });

        Route::get('collections/create', 'CollectionController@create');

        Route::get('me', 'ProfileController@meRedirect');
        Route::get('intent/follow', 'SiteController@followIntent');
        Route::get('rs/{id}', 'StoryController@remoteStory');
        Route::get('stories/new', 'StoryController@compose');
        Route::get('my/story', 'StoryController@iRedirect');
        Route::get('web/profile/_/{id}', 'InternalApiController@remoteProfile');
        Route::get('web/post/_/{profileId}/{statusid}', 'InternalApiController@remoteStatus');

        Route::group(['prefix' => 'import', 'middleware' => 'dangerzone'], function() {
            Route::get('job/{uuid}/1', 'ImportController@instagramStepOne');
            Route::post('job/{uuid}/1', 'ImportController@instagramStepOneStore');
            Route::get('job/{uuid}/2', 'ImportController@instagramStepTwo');
            Route::post('job/{uuid}/2', 'ImportController@instagramStepTwoStore');
            Route::get('job/{uuid}/3', 'ImportController@instagramStepThree');
            Route::post('job/{uuid}/3', 'ImportController@instagramStepThreeStore');
        });

        Route::get('redirect', 'SiteController@redirectUrl');
        Route::post('admin/media/block/add', 'MediaBlocklistController@add');
        Route::post('admin/media/block/delete', 'MediaBlocklistController@delete');

        Route::get('warning', 'AccountInterstitialController@get');
        Route::post('warning', 'AccountInterstitialController@read');
        Route::get('my2020', 'SeasonalController@yearInReview');

        Route::get('web/my-portfolio', 'PortfolioController@myRedirect');
        Route::get('web/hashtag/{tag}', 'SpaController@hashtagRedirect');
        Route::get('web/username/{id}', 'SpaController@usernameRedirect');
        Route::get('web/post/{id}', 'SpaController@webPost');
        Route::get('web/profile/{id}', 'SpaController@webProfile');

        Route::get('web/{q}', 'SpaController@index')->where('q', '.*');
        Route::get('web', 'SpaController@index');
    });

    Route::group(['prefix' => 'account'], function () {
        Route::redirect('/', '/');
        Route::get('direct', 'AccountController@direct');
        Route::get('direct/t/{id}', 'AccountController@directMessage');
        Route::get('activity', 'AccountController@notifications')->name('notifications');
        Route::get('follow-requests', 'AccountController@followRequests')->name('follow-requests');
        Route::post('follow-requests', 'AccountController@followRequestHandle');
        Route::get('follow-requests.json', 'AccountController@followRequestsJson');
        Route::get('portfolio/{username}.json', 'PortfolioController@getApFeed');
        Route::get('portfolio/{username}.rss', 'PortfolioController@getRssFeed');
    });

    Route::group(['prefix' => 'settings'], function () {
        Route::redirect('/', '/settings/home');
        Route::get('home', 'SettingsController@home')
        ->name('settings');
        Route::post('home', 'SettingsController@homeUpdate');
        Route::get('avatar', 'SettingsController@avatar')->name('settings.avatar');
        Route::post('avatar', 'AvatarController@store');
        Route::delete('avatar', 'AvatarController@deleteAvatar');
        Route::get('password', 'SettingsController@password')->name('settings.password')->middleware('dangerzone');
        Route::post('password', 'SettingsController@passwordUpdate')->middleware('dangerzone');
        Route::get('email', 'SettingsController@email')->name('settings.email')->middleware('dangerzone');
        Route::post('email', 'SettingsController@emailUpdate')->middleware('dangerzone');
        Route::get('notifications', 'SettingsController@notifications')->name('settings.notifications');
        Route::get('privacy', 'SettingsController@privacy')->name('settings.privacy');
        Route::post('privacy', 'SettingsController@privacyStore');
        Route::get('privacy/muted-users', 'SettingsController@mutedUsers')->name('settings.privacy.muted-users');
        Route::post('privacy/muted-users', 'SettingsController@mutedUsersUpdate');
        Route::get('privacy/blocked-users', 'SettingsController@blockedUsers')->name('settings.privacy.blocked-users');
        Route::post('privacy/blocked-users', 'SettingsController@blockedUsersUpdate');
        Route::get('privacy/domain-blocks', 'SettingsController@domainBlocks')->name('settings.privacy.domain-blocks');
        Route::get('privacy/blocked-instances', 'SettingsController@blockedInstances')->name('settings.privacy.blocked-instances');
        Route::post('privacy/blocked-instances', 'SettingsController@blockedInstanceStore');
        Route::post('privacy/blocked-instances/unblock', 'SettingsController@blockedInstanceUnblock')->name('settings.privacy.blocked-instances.unblock');
        Route::get('privacy/blocked-keywords', 'SettingsController@blockedKeywords')->name('settings.privacy.blocked-keywords');
        Route::post('privacy/account', 'SettingsController@privateAccountOptions')->name('settings.privacy.account');
        Route::group(['prefix' => 'remove', 'middleware' => 'dangerzone'], function() {
            Route::get('request/temporary', 'SettingsController@removeAccountTemporary')->name('settings.remove.temporary');
            Route::post('request/temporary', 'SettingsController@removeAccountTemporarySubmit');
            Route::get('request/permanent', 'SettingsController@removeAccountPermanent')->name('settings.remove.permanent');
            Route::post('request/permanent', 'SettingsController@removeAccountPermanentSubmit');
        });

        Route::group(['prefix' => 'security', 'middleware' => 'dangerzone'], function() {
            Route::get(
                '/',
                'SettingsController@security'
            )->name('settings.security');
            Route::get(
                '2fa/setup',
                'SettingsController@securityTwoFactorSetup'
            )->name('settings.security.2fa.setup');
            Route::post(
                '2fa/setup',
                'SettingsController@securityTwoFactorSetupStore'
            );
            Route::get(
                '2fa/edit',
                'SettingsController@securityTwoFactorEdit'
            )->name('settings.security.2fa.edit');
            Route::post(
                '2fa/edit',
                'SettingsController@securityTwoFactorUpdate'
            );
            Route::get(
                '2fa/recovery-codes',
                'SettingsController@securityTwoFactorRecoveryCodes'
            )->name('settings.security.2fa.recovery');
            Route::post(
                '2fa/recovery-codes',
                'SettingsController@securityTwoFactorRecoveryCodesRegenerate'
            );

        });

        Route::get('parental-controls', 'ParentalControlsController@index')->name('settings.parental-controls')->middleware('dangerzone');
        Route::get('parental-controls/add', 'ParentalControlsController@add')->name('settings.pc.add')->middleware('dangerzone');
        Route::post('parental-controls/add', 'ParentalControlsController@store')->middleware('dangerzone');
        Route::get('parental-controls/manage/{id}', 'ParentalControlsController@view')->middleware('dangerzone');
        Route::post('parental-controls/manage/{id}', 'ParentalControlsController@update')->middleware('dangerzone');
        Route::get('parental-controls/manage/{id}/cancel-invite', 'ParentalControlsController@cancelInvite')->name('settings.pc.cancel-invite')->middleware('dangerzone');
        Route::post('parental-controls/manage/{id}/cancel-invite', 'ParentalControlsController@cancelInviteHandle')->middleware('dangerzone');
        Route::get('parental-controls/manage/{id}/stop-managing', 'ParentalControlsController@stopManaging')->name('settings.pc.stop-managing')->middleware('dangerzone');
        Route::post('parental-controls/manage/{id}/stop-managing', 'ParentalControlsController@stopManagingHandle')->middleware('dangerzone');

        Route::get('applications', 'SettingsController@applications')->name('settings.applications')->middleware('dangerzone');
        Route::get('data-export', 'SettingsController@dataExport')->name('settings.dataexport')->middleware('dangerzone');
        Route::post('data-export/following', 'SettingsController@exportFollowing')->middleware('dangerzone');
        Route::post('data-export/followers', 'SettingsController@exportFollowers')->middleware('dangerzone');
        Route::post('data-export/mute-block-list', 'SettingsController@exportMuteBlockList')->middleware('dangerzone');
        Route::post('data-export/account', 'SettingsController@exportAccount')->middleware('dangerzone');
        Route::post('data-export/statuses', 'SettingsController@exportStatuses')->middleware('dangerzone');
        Route::get('developers', 'SettingsController@developers')->name('settings.developers')->middleware('dangerzone');
        Route::get('labs', 'SettingsController@labs')->name('settings.labs');
        Route::post('labs', 'SettingsController@labsStore');

        Route::get('accessibility', 'SettingsController@accessibility')->name('settings.accessibility');
        Route::post('accessibility', 'SettingsController@accessibilityStore');

        Route::group(['prefix' => 'relationships'], function() {
            Route::redirect('/', '/settings/relationships/home');
            Route::get('home', 'SettingsController@relationshipsHome')->name('settings.relationships');
        });
        Route::get('invites/create', 'UserInviteController@create')->name('settings.invites.create');
        Route::post('invites/create', 'UserInviteController@store');
        Route::get('invites', 'UserInviteController@show')->name('settings.invites');
        // Route::get('sponsor', 'SettingsController@sponsor')->name('settings.sponsor');
        // Route::post('sponsor', 'SettingsController@sponsorStore');
        Route::group(['prefix' => 'import', 'middleware' => 'dangerzone'], function() {
          Route::get('/', 'SettingsController@dataImport')->name('settings.import');
          Route::prefix('instagram')->group(function() {
            Route::get('/', 'ImportController@instagram')->name('settings.import.ig');
            Route::post('/', 'ImportController@instagramStart');
          });
          Route::prefix('mastodon')->group(function() {
            Route::get('/', 'ImportController@mastodon')->name('settings.import.mastodon');
          });
        });

        Route::get('timeline', 'SettingsController@timelineSettings')->name('settings.timeline');
        Route::post('timeline', 'SettingsController@updateTimelineSettings');
        Route::get('media', 'SettingsController@mediaSettings')->name('settings.media');
        Route::post('media', 'SettingsController@updateMediaSettings');

        Route::group(['prefix' => 'account/aliases', 'middleware' => 'dangerzone'], function() {
            Route::get('manage', 'ProfileAliasController@index');
            Route::post('manage', 'ProfileAliasController@store');
            Route::post('manage/delete', 'ProfileAliasController@delete');
        });
    });

    Route::group(['prefix' => 'site'], function () {
        Route::redirect('/', '/');
        Route::get('about', 'SiteController@about')->name('site.about');
        Route::view('help', 'site.help')->name('site.help');
        Route::view('developer-api', 'site.developer')->name('site.developers');
        Route::view('fediverse', 'site.fediverse')->name('site.fediverse');
        Route::view('open-source', 'site.opensource')->name('site.opensource');
        Route::view('banned-instances', 'site.bannedinstances')->name('site.bannedinstances');
        Route::get('terms', 'SiteController@terms')->name('site.terms');
        Route::get('privacy', 'SiteController@privacy')->name('site.privacy');
        Route::view('platform', 'site.platform')->name('site.platform');
        Route::view('language', 'site.language')->name('site.language');
        Route::get('contact', 'ContactController@show')->name('site.contact');
        Route::post('contact', 'ContactController@store');
        Route::group(['prefix'=>'kb'], function() {
            Route::view('getting-started', 'site.help.getting-started')->name('help.getting-started');
            Route::view('sharing-media', 'site.help.sharing-media')->name('help.sharing-media');
            Route::view('your-profile', 'site.help.your-profile')->name('help.your-profile');
            Route::view('stories', 'site.help.stories')->name('help.stories');
            Route::view('embed', 'site.help.embed')->name('help.embed');
            Route::view('hashtags', 'site.help.hashtags')->name('help.hashtags');
            Route::view('instance-actor', 'site.help.instance-actor')->name('help.instance-actor');
            Route::view('discover', 'site.help.discover')->name('help.discover');
            Route::view('direct-messages', 'site.help.dm')->name('help.dm');
            Route::view('timelines', 'site.help.timelines')->name('help.timelines');
            Route::view('what-is-the-fediverse', 'site.help.what-is-fediverse')->name('help.what-is-fediverse');
            Route::view('safety-tips', 'site.help.safety-tips')->name('help.safety-tips');

            Route::get('community-guidelines', 'SiteController@communityGuidelines')->name('help.community-guidelines');
            Route::view('controlling-visibility', 'site.help.controlling-visibility')->name('help.controlling-visibility');
            Route::view('blocking-accounts', 'site.help.blocking-accounts')->name('help.blocking-accounts');
            Route::view('report-something', 'site.help.report-something')->name('help.report-something');
            Route::view('data-policy', 'site.help.data-policy')->name('help.data-policy');
            Route::view('labs-deprecation', 'site.help.labs-deprecation')->name('help.labs-deprecation');
            Route::view('tagging-people', 'site.help.tagging-people')->name('help.tagging-people');
            Route::view('licenses', 'site.help.licenses')->name('help.licenses');
            Route::view('instance-max-users-limit', 'site.help.instance-max-users')->name('help.instance-max-users-limit');
            Route::view('import', 'site.help.import')->name('help.import');
            Route::view('parental-controls', 'site.help.parental-controls');
            // Route::view('email-confirmation-issues', 'site.help.email-confirmation-issues')->name('help.email-confirmation-issues');
            // Route::view('curated-onboarding', 'site.help.curated-onboarding')->name('help.curated-onboarding');
        });
        Route::get('newsroom/{year}/{month}/{slug}', 'NewsroomController@show');
        Route::get('newsroom/archive', 'NewsroomController@archive');
        Route::get('newsroom/search', 'NewsroomController@search');
        Route::get('newsroom', 'NewsroomController@index');
        Route::get('legal-notice', 'SiteController@legalNotice');
    });

    Route::group(['prefix' => 'timeline'], function () {
        Route::redirect('/', '/');
        Route::get('public', 'TimelineController@local')->name('timeline.public');
        Route::get('network', 'TimelineController@network')->name('timeline.network');
    });

    Route::group(['prefix' => 'users'], function () {
        Route::redirect('/', '/');
        Route::get('{user}.atom', 'ProfileController@showAtomFeed')->where('user', '.*');
        Route::get('{username}/outbox', 'FederationController@userOutbox');
        Route::get('{username}/followers', 'FederationController@userFollowers');
        Route::get('{username}/following', 'FederationController@userFollowing');
        Route::get('{username}', 'ProfileController@permalinkRedirect');
    });

    Route::group(['prefix' => 'installer'], function() {
        Route::get('api/requirements', 'InstallController@getRequirements')->withoutMiddleware(['web']);
        Route::post('precheck/database', 'InstallController@precheckDatabase')->withoutMiddleware(['web']);
        Route::post('store', 'InstallController@store')->withoutMiddleware(['web']);
        Route::get('/', 'InstallController@index')->withoutMiddleware(['web']);
        Route::get('/{q}', 'InstallController@index')->withoutMiddleware(['web'])->where('q', '.*');
    });

    Route::group(['prefix' => 'e'], function() {
        Route::get('terms', 'MobileController@terms');
        Route::get('privacy', 'MobileController@privacy');
    });

    Route::get('auth/invite/a/{code}', 'AdminInviteController@index');
    Route::post('api/v1.1/auth/invite/admin/re', 'AdminInviteController@apiRegister')->middleware('throttle:5,1440');

    Route::get('storage/m/_v2/{pid}/{mhash}/{uhash}/{f}', 'MediaController@fallbackRedirect');
    Route::get('stories/{username}', 'ProfileController@stories');
    Route::get('p/{id}', 'StatusController@shortcodeRedirect');
    Route::get('c/{collection}', 'CollectionController@show');
    Route::get('p/{username}/{id}/c', 'CommentController@showAll');
    Route::get('p/{username}/{id}/embed', 'StatusController@showEmbed');
    Route::get('p/{username}/{id}/edit', 'StatusController@edit');
    Route::post('p/{username}/{id}/edit', 'StatusController@editStore');
    Route::get('p/{username}/{id}.json', 'StatusController@showObject');
    Route::get('p/{username}/{id}', 'StatusController@show');
    Route::get('{username}/embed', 'ProfileController@embed');
    Route::get('{username}/live', 'LiveStreamController@showProfilePlayer');
    Route::get('@{username}@{domain}', 'SiteController@legacyWebfingerRedirect');
    Route::get('@{username}', 'SiteController@legacyProfileRedirect');
    Route::get('{username}', 'ProfileController@show');
});
