<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\AccountInterstitialController;
use App\Http\Controllers\AdminInviteController;
use App\Http\Controllers\AppRegisterController;
use App\Http\Controllers\AuthorizeInteractionController;
use App\Http\Controllers\AvatarController;
use App\Http\Controllers\BookmarkController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\CuratedRegisterController;
use App\Http\Controllers\DiscoverController;
use App\Http\Controllers\FederationController;
use App\Http\Controllers\FollowerController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\Groups\GroupsTopicController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\InstallController;
use App\Http\Controllers\InternalApiController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\LiveStreamController;
use App\Http\Controllers\MediaBlocklistController;
use App\Http\Controllers\MobileController;
use App\Http\Controllers\NewsroomController;
use App\Http\Controllers\OAuth\ApiTokenController;
use App\Http\Controllers\OAuth\OobAuthorizationController;
use App\Http\Controllers\ParentalControlsController;
use App\Http\Controllers\PersonalAccessTokenController;
use App\Http\Controllers\PlaceController;
use App\Http\Controllers\PortfolioController;
use App\Http\Controllers\ProfileAliasController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProfileMigrationController;
use App\Http\Controllers\RemoteAuthController;
use App\Http\Controllers\RemoteOidcController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\SpaController;
use App\Http\Controllers\StatusController;
use App\Http\Controllers\StoryController;
use App\Http\Controllers\TimelineController;
use App\Http\Controllers\UserEmailForgotController;
use App\Http\Controllers\UserInviteController;
use Laravel\Passport\Http\Controllers\AuthorizationController;
use Laravel\Passport\Http\Controllers\AuthorizedAccessTokenController;
use Laravel\Passport\Http\Controllers\ClientController;
use Laravel\Passport\Http\Controllers\DenyAuthorizationController;
use Laravel\Passport\Http\Controllers\TransientTokenController;

Route::domain(config('pixelfed.domain.app'))->middleware(['validemail', 'twofactor', 'localization'])->group(function () {
    Route::get('/', [SiteController::class, 'home'])->name('timeline.personal');
    Route::redirect('/home', '/')->name('home');
    Route::get('web/directory', [LandingController::class, 'directoryRedirect']);
    Route::get('web/explore', [LandingController::class, 'exploreRedirect']);
    Route::get('authorize_interaction', [AuthorizeInteractionController::class, 'get']);

    Auth::routes();

    Route::get('auth/oidc/start', [RemoteOidcController::class, 'start']);
    Route::get('auth/oidc/callback', [RemoteOidcController::class, 'handleCallback']);

    Route::get('auth/raw/mastodon/start', [RemoteAuthController::class, 'startRedirect']);
    Route::post('auth/raw/mastodon/config', [RemoteAuthController::class, 'getConfig']);
    Route::post('auth/raw/mastodon/domains', [RemoteAuthController::class, 'getAuthDomains']);
    Route::post('auth/raw/mastodon/start', [RemoteAuthController::class, 'start']);
    Route::post('auth/raw/mastodon/redirect', [RemoteAuthController::class, 'redirect']);
    Route::get('auth/raw/mastodon/preflight', [RemoteAuthController::class, 'preflight']);
    Route::get('auth/mastodon/callback', [RemoteAuthController::class, 'handleCallback']);
    Route::get('auth/mastodon/getting-started', [RemoteAuthController::class, 'onboarding']);
    Route::post('auth/raw/mastodon/s/check', [RemoteAuthController::class, 'sessionCheck']);
    Route::post('auth/raw/mastodon/s/prefill', [RemoteAuthController::class, 'sessionGetMastodonData']);
    Route::post('auth/raw/mastodon/s/username-check', [RemoteAuthController::class, 'sessionValidateUsername']);
    Route::post('auth/raw/mastodon/s/email-check', [RemoteAuthController::class, 'sessionValidateEmail']);
    Route::post('auth/raw/mastodon/s/following', [RemoteAuthController::class, 'sessionGetMastodonFollowers']);
    Route::post('auth/raw/mastodon/s/submit', [RemoteAuthController::class, 'handleSubmit']);
    Route::post('auth/raw/mastodon/s/store-bio', [RemoteAuthController::class, 'storeBio']);
    Route::post('auth/raw/mastodon/s/store-avatar', [RemoteAuthController::class, 'storeAvatar']);
    Route::post('auth/raw/mastodon/s/account-to-id', [RemoteAuthController::class, 'accountToId']);
    Route::post('auth/raw/mastodon/s/finish-up', [RemoteAuthController::class, 'finishUp']);
    Route::post('auth/raw/mastodon/s/login', [RemoteAuthController::class, 'handleLogin']);
    Route::get('auth/pci/{id}/{code}', [ParentalControlsController::class, 'inviteRegister']);
    Route::post('auth/pci/{id}/{code}', [ParentalControlsController::class, 'inviteRegisterStore']);

    Route::get('auth/sign_up', [SiteController::class, 'curatedOnboarding'])->name('auth.curated-onboarding');
    Route::post('auth/sign_up', [CuratedRegisterController::class, 'proceed']);
    Route::get('auth/sign_up/concierge/response-sent', [CuratedRegisterController::class, 'conciergeResponseSent']);
    Route::get('auth/sign_up/concierge', [CuratedRegisterController::class, 'concierge']);
    Route::post('auth/sign_up/concierge', [CuratedRegisterController::class, 'conciergeStore']);
    Route::get('auth/sign_up/concierge/form', [CuratedRegisterController::class, 'conciergeFormShow']);
    Route::post('auth/sign_up/concierge/form', [CuratedRegisterController::class, 'conciergeFormStore']);
    Route::get('auth/sign_up/confirm', [CuratedRegisterController::class, 'confirmEmail']);
    Route::post('auth/sign_up/confirm', [CuratedRegisterController::class, 'confirmEmailHandle']);
    Route::get('auth/sign_up/confirmed', [CuratedRegisterController::class, 'emailConfirmed']);
    Route::get('auth/sign_up/resend-confirmation', [CuratedRegisterController::class, 'resendConfirmation']);
    Route::post('auth/sign_up/resend-confirmation', [CuratedRegisterController::class, 'resendConfirmationProcess']);
    Route::get('auth/forgot/email', [UserEmailForgotController::class, 'index'])->name('email.forgot');
    Route::post('auth/forgot/email', [UserEmailForgotController::class, 'store'])->middleware('throttle:10,900,forgotEmail');

    Route::group([
        'as' => 'passport.',
        'prefix' => 'oauth',
        'middleware' => ['oauth-web'],
    ], function () {
        Route::post('/token', [
            'uses' => [ApiTokenController::class, 'issueToken'],
            'as' => 'token',
            'middleware' => 'throttle:10,1',
        ]);

        Route::get('/authorize', [
            'uses' => [AuthorizationController::class, 'authorize'],
            'as' => 'authorizations.authorize',
            'middleware' => ['throttle:10,1'],
        ]);

        Route::middleware(['auth:web', 'validemail'])->group(function () {
            Route::post('/token/refresh', [
                'uses' => [TransientTokenController::class, 'refresh'],
                'as' => 'token.refresh',
            ]);

            Route::post('/authorize', [
                'uses' => [OobAuthorizationController::class, 'approve'],
                'as' => 'authorizations.approve',
            ]);

            Route::delete('/authorize', [
                'uses' => [DenyAuthorizationController::class, 'deny'],
                'as' => 'authorizations.deny',
            ]);

            Route::get('/tokens', [
                'uses' => [AuthorizedAccessTokenController::class, 'forUser'],
                'as' => 'tokens.index',
            ]);

            Route::delete('/tokens/{token_id}', [
                'uses' => [AuthorizedAccessTokenController::class, 'destroy'],
                'as' => 'tokens.destroy',
            ]);

            Route::get('/clients', [
                'uses' => [ClientController::class, 'forUser'],
                'as' => 'clients.index',
            ]);

            Route::post('/clients', [
                'uses' => [ClientController::class, 'store'],
                'as' => 'clients.store',
            ]);

            Route::put('/clients/{client_id}', [
                'uses' => [ClientController::class, 'update'],
                'as' => 'clients.update',
            ]);

            Route::delete('/clients/{client_id}', [
                'uses' => [ClientController::class, 'destroy'],
                'as' => 'clients.destroy',
            ]);

            Route::get('/scopes', [
                'uses' => [PersonalAccessTokenController::class, 'scopes'],
                'as' => 'scopes.index',
            ]);

            Route::get('/personal-access-tokens', [
                'uses' => [PersonalAccessTokenController::class, 'index'],
                'as' => 'personal.tokens.index',
            ]);

            Route::post('/personal-access-tokens', [
                'uses' => [PersonalAccessTokenController::class, 'store'],
                'as' => 'personal.tokens.store',
            ])->middleware(['throttle:oauth-pat']);

            Route::post('/personal-access-tokens/{token_id}/renew', [
                'uses' => [PersonalAccessTokenController::class, 'renew'],
                'as' => 'personal.tokens.renew',
            ])->middleware(['throttle:oauth-pat']);

            Route::delete('/personal-access-tokens/{token_id}', [
                'uses' => [PersonalAccessTokenController::class, 'destroy'],
                'as' => 'personal.tokens.destroy',
            ]);
        });
    });

    Route::get('discover', [DiscoverController::class, 'home'])->name('discover');

    Route::get('discover/tags/{hashtag}', [DiscoverController::class, 'showTags']);
    Route::get('discover/places', [PlaceController::class, 'directoryHome'])->name('discover.places');
    Route::get('discover/places/{id}/{slug}', [PlaceController::class, 'show']);
    Route::get('discover/location/country/{country}', [PlaceController::class, 'directoryCities']);

    Route::get('/i/app-email-verify', [AppRegisterController::class, 'index']);
    Route::post('/i/app-email-verify', [AppRegisterController::class, 'store'])->middleware('throttle:app-signup');
    Route::get('/i/app-email-resend', [AppRegisterController::class, 'resendVerification']);
    Route::post('/i/app-email-resend', [AppRegisterController::class, 'resendVerificationStore'])->middleware('throttle:app-code-resend');

    Route::group(['prefix' => 'i'], function () {
        Route::redirect('/', '/');
        Route::get('compose', [StatusController::class, 'compose'])->name('compose');
        Route::post('comment', [CommentController::class, 'store']);
        Route::post('delete', [StatusController::class, 'delete']);
        Route::post('mute', [AccountController::class, 'mute']);
        Route::post('unmute', [AccountController::class, 'unmute']);
        Route::post('block', [AccountController::class, 'block']);
        Route::post('unblock', [AccountController::class, 'unblock']);
        Route::post('like', [LikeController::class, 'store']);
        Route::post('share', [StatusController::class, 'storeShare']);
        Route::post('follow', [FollowerController::class, 'store']);
        Route::post('bookmark', [BookmarkController::class, 'store']);
        Route::get('lang/{locale}', [SiteController::class, 'changeLocale']);
        Route::get('restored', [AccountController::class, 'accountRestored']);

        Route::get('verify-email', [AccountController::class, 'verifyEmail'])->name('account.verify_email');
        Route::post('verify-email', [AccountController::class, 'sendVerifyEmail']);
        Route::get('verify-email/request', [InternalApiController::class, 'requestEmailVerification']);
        Route::post('verify-email/request', [InternalApiController::class, 'requestEmailVerificationStore']);
        Route::get('confirm-email/{userToken}/{randomToken}', [AccountController::class, 'confirmVerifyEmail']);

        Route::get('auth/sudo', [AccountController::class, 'sudoMode']);
        Route::post('auth/sudo', [AccountController::class, 'sudoModeVerify']);
        Route::get('auth/checkpoint', [AccountController::class, 'twoFactorCheckpoint']);
        Route::post('auth/checkpoint', [AccountController::class, 'twoFactorVerify']);

        Route::get('results', [SearchController::class, 'results']);
        Route::post('visibility', [StatusController::class, 'toggleVisibility']);

        Route::post('metro/dark-mode', [SettingsController::class, 'metroDarkMode']);

        Route::group(['prefix' => 'report'], function () {
            Route::get('/', [ReportController::class, 'showForm'])->name('report.form');
            Route::post('/', [ReportController::class, 'formStore']);
            Route::get('not-interested', [ReportController::class, 'notInterestedForm'])->name('report.not-interested');
            Route::get('spam', [ReportController::class, 'spamForm'])->name('report.spam');
            Route::get('spam/comment', [ReportController::class, 'spamCommentForm'])->name('report.spam.comment');
            Route::get('spam/post', [ReportController::class, 'spamPostForm'])->name('report.spam.post');
            Route::get('spam/profile', [ReportController::class, 'spamProfileForm'])->name('report.spam.profile');
            Route::get('sensitive/comment', [ReportController::class, 'sensitiveCommentForm'])->name('report.sensitive.comment');
            Route::get('sensitive/post', [ReportController::class, 'sensitivePostForm'])->name('report.sensitive.post');
            Route::get('sensitive/profile', [ReportController::class, 'sensitiveProfileForm'])->name('report.sensitive.profile');
            Route::get('abusive/comment', [ReportController::class, 'abusiveCommentForm'])->name('report.abusive.comment');
            Route::get('abusive/post', [ReportController::class, 'abusivePostForm'])->name('report.abusive.post');
            Route::get('abusive/profile', [ReportController::class, 'abusiveProfileForm'])->name('report.abusive.profile');
        });

        Route::get('collections/create', [CollectionController::class, 'create']);

        Route::get('me', [ProfileController::class, 'meRedirect']);
        Route::get('intent/follow', [SiteController::class, 'followIntent']);
        Route::get('rs/{id}', [StoryController::class, 'remoteStory']);
        Route::get('stories/new', [StoryController::class, 'compose']);
        Route::get('my/story', [StoryController::class, 'iRedirect']);
        Route::get('web/profile/_/{id}', [InternalApiController::class, 'remoteProfile']);
        Route::get('web/post/_/{profileId}/{statusid}', [InternalApiController::class, 'remoteStatus']);

        Route::group(['prefix' => 'import', 'middleware' => 'dangerzone'], function () {
            Route::get('job/{uuid}/1', [ImportController::class, 'instagramStepOne']);
            Route::post('job/{uuid}/1', [ImportController::class, 'instagramStepOneStore']);
            Route::get('job/{uuid}/2', [ImportController::class, 'instagramStepTwo']);
            Route::post('job/{uuid}/2', [ImportController::class, 'instagramStepTwoStore']);
            Route::get('job/{uuid}/3', [ImportController::class, 'instagramStepThree']);
            Route::post('job/{uuid}/3', [ImportController::class, 'instagramStepThreeStore']);
        });

        Route::get('redirect', [SiteController::class, 'redirectUrl']);
        Route::post('admin/media/block/add', [MediaBlocklistController::class, 'add']);
        Route::post('admin/media/block/delete', [MediaBlocklistController::class, 'delete']);

        Route::get('warning', [AccountInterstitialController::class, 'get']);
        Route::post('warning', [AccountInterstitialController::class, 'read']);

        Route::get('contact-admin-response/{id}', [ContactController::class, 'showAdminResponse']);

        Route::get('web/my-portfolio', [PortfolioController::class, 'myRedirect']);
        Route::get('web/hashtag/{tag}', [SpaController::class, 'hashtagRedirect']);
        Route::get('web/username/{id}', [SpaController::class, 'usernameRedirect']);
        Route::get('web/post/{id}', [SpaController::class, 'webPost']);
        Route::get('web/profile/{id}', [SpaController::class, 'webProfile']);

        Route::get('web/{q}', [SpaController::class, 'index'])->where('q', '.*');
        Route::get('web', [SpaController::class, 'index']);
    });

    Route::group(['prefix' => 'account'], function () {
        Route::redirect('/', '/');
        Route::get('direct', [AccountController::class, 'direct']);
        Route::get('direct/t/{id}', [AccountController::class, 'directMessage']);
        Route::get('activity', [AccountController::class, 'notifications'])->name('notifications');
        Route::get('follow-requests', [AccountController::class, 'followRequests'])->name('follow-requests');
        Route::post('follow-requests', [AccountController::class, 'followRequestHandle']);
        Route::get('follow-requests.json', [AccountController::class, 'followRequestsJson']);
        Route::get('portfolio/{username}.json', [PortfolioController::class, 'getApFeed']);
        Route::get('portfolio/{username}.rss', [PortfolioController::class, 'getRssFeed']);
    });

    Route::group(['prefix' => 'settings'], function () {
        Route::redirect('/', '/settings/home');
        Route::get('home', [SettingsController::class, 'home'])
            ->name('settings');
        Route::post('home', [SettingsController::class, 'homeUpdate']);
        Route::get('avatar', [SettingsController::class, 'avatar'])->name('settings.avatar');
        Route::post('avatar', [AvatarController::class, 'store']);
        Route::delete('avatar', [AvatarController::class, 'deleteAvatar']);
        Route::get('password', [SettingsController::class, 'password'])->name('settings.password')->middleware('dangerzone');
        Route::post('password', [SettingsController::class, 'passwordUpdate'])->middleware('dangerzone');
        Route::get('email', [SettingsController::class, 'email'])->name('settings.email')->middleware('dangerzone');
        Route::post('email', [SettingsController::class, 'emailUpdate'])->middleware('dangerzone');
        Route::get('notifications', [SettingsController::class, 'notifications'])->name('settings.notifications');
        Route::get('privacy', [SettingsController::class, 'privacy'])->name('settings.privacy');
        Route::post('privacy', [SettingsController::class, 'privacyStore']);
        Route::get('privacy/muted-users', [SettingsController::class, 'mutedUsers'])->name('settings.privacy.muted-users');
        Route::post('privacy/muted-users', [SettingsController::class, 'mutedUsersUpdate']);
        Route::get('privacy/blocked-users', [SettingsController::class, 'blockedUsers'])->name('settings.privacy.blocked-users');
        Route::post('privacy/blocked-users', [SettingsController::class, 'blockedUsersUpdate']);
        Route::get('privacy/domain-blocks', [SettingsController::class, 'domainBlocks'])->name('settings.privacy.domain-blocks');
        Route::get('privacy/blocked-instances', [SettingsController::class, 'blockedInstances'])->name('settings.privacy.blocked-instances');
        Route::post('privacy/blocked-instances', [SettingsController::class, 'blockedInstanceStore']);
        Route::post('privacy/blocked-instances/unblock', [SettingsController::class, 'blockedInstanceUnblock'])->name('settings.privacy.blocked-instances.unblock');
        Route::get('privacy/blocked-keywords', [SettingsController::class, 'blockedKeywords'])->name('settings.privacy.blocked-keywords');
        Route::post('privacy/account', [SettingsController::class, 'privateAccountOptions'])->name('settings.privacy.account');
        Route::group(['prefix' => 'remove', 'middleware' => 'dangerzone'], function () {
            Route::get('request/temporary', [SettingsController::class, 'removeAccountTemporary'])->name('settings.remove.temporary');
            Route::post('request/temporary', [SettingsController::class, 'removeAccountTemporarySubmit']);
            Route::get('request/permanent', [SettingsController::class, 'removeAccountPermanent'])->name('settings.remove.permanent');
            Route::post('request/permanent', [SettingsController::class, 'removeAccountPermanentSubmit']);
        });

        Route::group(['prefix' => 'security', 'middleware' => 'dangerzone'], function () {
            Route::get('/', [SettingsController::class, 'security'])->name('settings.security');
            Route::get('2fa/setup', [SettingsController::class, 'securityTwoFactorSetup'])->name('settings.security.2fa.setup');
            Route::post('2fa/setup', [SettingsController::class, 'securityTwoFactorSetupStore']);
            Route::get('2fa/edit', [SettingsController::class, 'securityTwoFactorEdit'])->name('settings.security.2fa.edit');
            Route::post('2fa/edit', [SettingsController::class, 'securityTwoFactorUpdate']);
            Route::get('2fa/recovery-codes', [SettingsController::class, 'securityTwoFactorRecoveryCodes'])->name('settings.security.2fa.recovery');
            Route::post('2fa/recovery-codes', [SettingsController::class, 'securityTwoFactorRecoveryCodesRegenerate']);
        });

        Route::get('parental-controls', [ParentalControlsController::class, 'index'])->name('settings.parental-controls')->middleware('dangerzone');
        Route::get('parental-controls/add', [ParentalControlsController::class, 'add'])->name('settings.pc.add')->middleware('dangerzone');
        Route::post('parental-controls/add', [ParentalControlsController::class, 'store'])->middleware('dangerzone');
        Route::get('parental-controls/manage/{id}', [ParentalControlsController::class, 'view'])->middleware('dangerzone');
        Route::post('parental-controls/manage/{id}', [ParentalControlsController::class, 'update'])->middleware('dangerzone');
        Route::get('parental-controls/manage/{id}/cancel-invite', [ParentalControlsController::class, 'cancelInvite'])->name('settings.pc.cancel-invite')->middleware('dangerzone');
        Route::post('parental-controls/manage/{id}/cancel-invite', [ParentalControlsController::class, 'cancelInviteHandle'])->middleware('dangerzone');
        Route::get('parental-controls/manage/{id}/stop-managing', [ParentalControlsController::class, 'stopManaging'])->name('settings.pc.stop-managing')->middleware('dangerzone');
        Route::post('parental-controls/manage/{id}/stop-managing', [ParentalControlsController::class, 'stopManagingHandle'])->middleware('dangerzone');

        Route::get('applications', [SettingsController::class, 'applications'])->name('settings.applications')->middleware('dangerzone');
        Route::get('data-export', [SettingsController::class, 'dataExport'])->name('settings.dataexport')->middleware('dangerzone');
        Route::post('data-export/following', [SettingsController::class, 'exportFollowing'])->middleware('dangerzone');
        Route::post('data-export/followers', [SettingsController::class, 'exportFollowers'])->middleware('dangerzone');
        Route::post('data-export/mute-block-list', [SettingsController::class, 'exportMuteBlockList'])->middleware('dangerzone');
        Route::post('data-export/account', [SettingsController::class, 'exportAccount'])->middleware('dangerzone');
        Route::post('data-export/statuses', [SettingsController::class, 'exportStatuses'])->middleware('dangerzone');
        Route::get('developers', [SettingsController::class, 'developers'])->name('settings.developers')->middleware('dangerzone');
        Route::get('labs', [SettingsController::class, 'labs'])->name('settings.labs');
        Route::post('labs', [SettingsController::class, 'labsStore']);

        Route::get('accessibility', [SettingsController::class, 'accessibility'])->name('settings.accessibility');
        Route::post('accessibility', [SettingsController::class, 'accessibilityStore']);

        Route::group(['prefix' => 'relationships'], function () {
            Route::redirect('/', '/settings/relationships/home');
            Route::get('home', [SettingsController::class, 'relationshipsHome'])->name('settings.relationships');
        });
        Route::get('invites/create', [UserInviteController::class, 'create'])->name('settings.invites.create');
        Route::post('invites/create', [UserInviteController::class, 'store']);
        Route::get('invites', [UserInviteController::class, 'show'])->name('settings.invites');
        // Route::get('sponsor', [SettingsController::class, 'sponsor'])->name('settings.sponsor');
        // Route::post('sponsor', [SettingsController::class, 'sponsorStore']);
        Route::group(['prefix' => 'import', 'middleware' => 'dangerzone'], function () {
            Route::get('/', [SettingsController::class, 'dataImport'])->name('settings.import');
            Route::prefix('instagram')->group(function () {
                Route::get('/', [ImportController::class, 'instagram'])->name('settings.import.ig');
                Route::post('/', [ImportController::class, 'instagramStart']);
            });
            Route::prefix('mastodon')->group(function () {
                Route::get('/', [ImportController::class, 'mastodon'])->name('settings.import.mastodon');
            });
        });

        Route::get('timeline', [SettingsController::class, 'timelineSettings'])->name('settings.timeline');
        Route::post('timeline', [SettingsController::class, 'updateTimelineSettings']);
        Route::get('media', [SettingsController::class, 'mediaSettings'])->name('settings.media');
        Route::post('media', [SettingsController::class, 'updateMediaSettings']);

        Route::group(['prefix' => 'account/aliases', 'middleware' => 'dangerzone'], function () {
            Route::get('manage', [ProfileAliasController::class, 'index']);
            Route::post('manage', [ProfileAliasController::class, 'store']);
            Route::post('manage/delete', [ProfileAliasController::class, 'delete']);
        });

        Route::group(['prefix' => 'account/migration', 'middleware' => 'dangerzone'], function () {
            Route::get('manage', [ProfileMigrationController::class, 'index']);
            Route::post('manage', [ProfileMigrationController::class, 'store']);
        });

        Route::group(['prefix' => 'filters'], function () {
            Route::get('/', [SettingsController::class, 'filtersHome'])->name('settings.filters');
        });
    });

    Route::group(['prefix' => 'site'], function () {
        Route::redirect('/', '/');
        Route::get('about', [SiteController::class, 'about'])->name('site.about');
        Route::view('help', 'site.help')->name('site.help');
        Route::view('developer-api', 'site.developer')->name('site.developers');
        Route::view('fediverse', 'site.fediverse')->name('site.fediverse');
        Route::view('open-source', 'site.opensource')->name('site.opensource');
        Route::view('banned-instances', 'site.bannedinstances')->name('site.bannedinstances');
        Route::get('terms', [SiteController::class, 'terms'])->name('site.terms');
        Route::get('privacy', [SiteController::class, 'privacy'])->name('site.privacy');
        Route::view('platform', 'site.platform')->name('site.platform');
        Route::view('language', 'site.language')->name('site.language');
        Route::get('contact', [ContactController::class, 'show'])->name('site.contact');
        Route::post('contact', [ContactController::class, 'store']);
        Route::group(['prefix' => 'kb'], function () {
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

            Route::get('community-guidelines', [SiteController::class, 'communityGuidelines'])->name('help.community-guidelines');
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
            Route::view('email-confirmation-issues', 'site.help.email-confirmation-issues')->name('help.email-confirmation-issues');
            Route::view('curated-onboarding', 'site.help.curated-onboarding')->name('help.curated-onboarding');
            Route::view('account-migration', 'site.help.account-migration')->name('help.account-migration');
        });
        Route::get('newsroom/{year}/{month}/{slug}', [NewsroomController::class, 'show']);
        Route::get('newsroom/archive', [NewsroomController::class, 'archive']);
        Route::get('newsroom/search', [NewsroomController::class, 'search']);
        Route::get('newsroom', [NewsroomController::class, 'index']);
        Route::get('legal-notice', [SiteController::class, 'legalNotice']);
    });

    Route::group(['prefix' => 'timeline'], function () {
        Route::redirect('/', '/');
        Route::get('public', [TimelineController::class, 'local'])->name('timeline.public');
        Route::get('network', [TimelineController::class, 'network'])->name('timeline.network');
    });

    Route::group(['prefix' => 'users'], function () {
        Route::redirect('/', '/');
        Route::get('{user}.atom', [ProfileController::class, 'showAtomFeed'])->where('user', '.*');
        Route::get('{username}/outbox', [FederationController::class, 'userOutbox']);
        Route::get('{username}/followers', [FederationController::class, 'userFollowers']);
        Route::get('{username}/following', [FederationController::class, 'userFollowing']);
        Route::get('{username}', [ProfileController::class, 'permalinkRedirect']);
    });

    Route::group(['prefix' => 'installer'], function () {
        Route::get('api/requirements', [InstallController::class, 'getRequirements'])->withoutMiddleware(['web']);
        Route::post('precheck/database', [InstallController::class, 'precheckDatabase'])->withoutMiddleware(['web']);
        Route::post('store', [InstallController::class, 'store'])->withoutMiddleware(['web']);
        Route::get('/', [InstallController::class, 'index'])->withoutMiddleware(['web']);
        Route::get('/{q}', [InstallController::class, 'index'])->withoutMiddleware(['web'])->where('q', '.*');
    });

    Route::group(['prefix' => 'e'], function () {
        Route::get('terms', [MobileController::class, 'terms']);
        Route::get('privacy', [MobileController::class, 'privacy']);
    });

    Route::get('auth/invite/a/{code}', [AdminInviteController::class, 'index']);
    Route::post('api/v1.1/auth/invite/admin/re', [AdminInviteController::class, 'apiRegister'])->middleware('throttle:5,1440');

    Route::redirect('groups/', '/groups/home');
    Route::redirect('groups/home', '/groups/feed');

    Route::prefix('groups')->group(function () {
        // Route::get('feed', [GroupController::class, 'index']);
        Route::get('{id}/invite/claim', [GroupController::class, 'groupInviteClaim']);
        Route::get('{id}/invite', [GroupController::class, 'groupInviteLanding']);
        Route::get('{id}/settings', [GroupController::class, 'groupSettings']);
        Route::get('{gid}/topics/{topic}', [GroupsTopicController::class, 'showTopicFeed']);
        Route::get('{gid}/p/{sid}.json', [GroupController::class, 'getStatusObject']);
        Route::get('{gid}/p/{sid}', [GroupController::class, 'showStatus']);
        Route::get('{id}/user/{pid}', [GroupController::class, 'showProfile']);
        Route::get('{id}/un/{pid}', [GroupController::class, 'showProfile']);
        Route::get('{id}/username/{pid}', [GroupController::class, 'showProfileByUsername']);
        Route::get('{id}/{path}', [GroupController::class, 'show']);
        Route::get('{id}.json', [GroupController::class, 'getGroupObject']);
        Route::get('feed', [GroupController::class, 'index']);
        Route::get('create', [GroupController::class, 'index']);
        Route::get('discover', [GroupController::class, 'index']);
        Route::get('search', [GroupController::class, 'index']);
        Route::get('joins', [GroupController::class, 'index']);
        Route::get('notifications', [GroupController::class, 'index']);
        Route::get('{id}', [GroupController::class, 'show']);
    });
    Route::get('g/{hid}', [GroupController::class, 'groupShortLinkRedirect']);

    Route::get('stories/{username}', [ProfileController::class, 'stories']);
    Route::get('p/{id}', [StatusController::class, 'shortcodeRedirect']);
    Route::get('c/{collection}', [CollectionController::class, 'show']);
    Route::get('p/{username}/{id}/c', [CommentController::class, 'showAll']);
    Route::get('p/{username}/{id}/embed', [StatusController::class, 'showEmbed']);
    Route::get('p/{username}/{id}/edit', [StatusController::class, 'edit']);
    Route::post('p/{username}/{id}/edit', [StatusController::class, 'editStore']);
    Route::get('p/{username}/{id}.json', [StatusController::class, 'showObject']);
    Route::get('p/{username}/{id}', [StatusController::class, 'show']);
    Route::get('{username}/embed', [ProfileController::class, 'embed']);
    Route::get('{username}/live', [LiveStreamController::class, 'showProfilePlayer']);
    Route::get('@{username}@{domain}', [SiteController::class, 'legacyWebfingerRedirect']);
    Route::get('@{username}', [SiteController::class, 'legacyProfileRedirect']);
    Route::get('{username}', [ProfileController::class, 'show']);
});
