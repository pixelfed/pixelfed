<?php

Route::domain(config('pixelfed.domain.admin'))->prefix('i/admin')->group(function () {
	Route::redirect('/', '/dashboard');
	Route::redirect('timeline', config('app.url').'/timeline');
	Route::get('dashboard', 'AdminController@home')->name('admin.home');
	Route::get('stats', 'AdminController@stats')->name('admin.stats');
	Route::get('reports', 'AdminController@reports')->name('admin.reports');
	Route::get('reports/show/{id}', 'AdminController@showReport');
	Route::post('reports/show/{id}', 'AdminController@updateReport');
	Route::post('reports/bulk', 'AdminController@bulkUpdateReport');
	Route::get('reports/autospam/{id}', 'AdminController@showSpam');
	Route::post('reports/autospam/sync', 'AdminController@fixUncategorizedSpam');
	Route::post('reports/autospam/{id}', 'AdminController@updateSpam');
	Route::get('reports/autospam', 'AdminController@spam');
	Route::get('reports/appeals', 'AdminController@appeals');
	Route::get('reports/appeal/{id}', 'AdminController@showAppeal');
	Route::post('reports/appeal/{id}', 'AdminController@updateAppeal');
	Route::get('reports/email-verifications', 'AdminController@reportMailVerifications');
	Route::post('reports/email-verifications/ignore', 'AdminController@reportMailVerifyIgnore');
	Route::post('reports/email-verifications/approve', 'AdminController@reportMailVerifyApprove');
	Route::post('reports/email-verifications/clear-ignored', 'AdminController@reportMailVerifyClearIgnored');
	Route::redirect('stories', '/stories/list');
	Route::get('stories/list', 'AdminController@stories')->name('admin.stories');
	Route::redirect('statuses', '/statuses/list');
	Route::get('statuses/list', 'AdminController@statuses')->name('admin.statuses');
	Route::get('statuses/show/{id}', 'AdminController@showStatus');
	Route::redirect('profiles', '/i/admin/profiles/list');
	Route::get('profiles/list', 'AdminController@profiles')->name('admin.profiles');
	Route::get('profiles/edit/{id}', 'AdminController@profileShow');
	Route::redirect('users', '/users/list');
	Route::get('users/list', 'AdminController@users')->name('admin.users');
	Route::get('users/show/{id}', 'AdminController@userShow');
	Route::get('users/edit/{id}', 'AdminController@userEdit');
	Route::post('users/edit/{id}', 'AdminController@userEditSubmit');
	Route::get('users/activity/{id}', 'AdminController@userActivity');
	Route::get('users/message/{id}', 'AdminController@userMessage');
	Route::post('users/message/{id}', 'AdminController@userMessageSend');
	Route::get('users/modtools/{id}', 'AdminController@userModTools');
	Route::get('users/modlogs/{id}', 'AdminController@userModLogs');
	Route::post('users/modlogs/{id}', 'AdminController@userModLogsMessage');
	Route::post('users/modlogs/{id}/delete', 'AdminController@userModLogDelete');
	Route::get('users/delete/{id}', 'AdminController@userDelete');
	Route::post('users/delete/{id}', 'AdminController@userDeleteProcess');
	Route::post('users/moderation/update', 'AdminController@userModerate');
	Route::get('media', 'AdminController@media')->name('admin.media');
	Route::redirect('media/list', '/i/admin/media');
	Route::get('media/show/{id}', 'AdminController@mediaShow');
	Route::get('settings', 'AdminController@settings')->name('admin.settings');
	Route::post('settings', 'AdminController@settingsHomeStore');
	Route::get('settings/features', 'AdminController@settingsFeatures')->name('admin.settings.features');
	Route::get('settings/pages', 'AdminController@settingsPages')->name('admin.settings.pages');
	Route::get('settings/pages/edit', 'PageController@edit')->name('admin.settings.pages.edit');
	Route::post('settings/pages/edit', 'PageController@store');
	Route::post('settings/pages/delete', 'PageController@delete');
	Route::post('settings/pages/create', 'PageController@generatePage');
	Route::get('settings/maintenance', 'AdminController@settingsMaintenance')->name('admin.settings.maintenance');
	Route::get('settings/backups', 'AdminController@settingsBackups')->name('admin.settings.backups');
	Route::get('settings/storage', 'AdminController@settingsStorage')->name('admin.settings.storage');
	Route::get('settings/system', 'AdminController@settingsSystem')->name('admin.settings.system');

	Route::get('instances', 'AdminController@instances')->name('admin.instances');
	Route::post('instances', 'AdminController@instanceScan');
	Route::get('instances/show/{id}', 'AdminController@instanceShow');
	Route::post('instances/edit/{id}', 'AdminController@instanceEdit');
	Route::get('apps/home', 'AdminController@appsHome')->name('admin.apps');
	Route::get('hashtags/home', 'AdminController@hashtagsHome')->name('admin.hashtags');
	Route::get('discover/home', 'AdminController@discoverHome')->name('admin.discover');
	Route::get('discover/category/create', 'AdminController@discoverCreateCategory')->name('admin.discover.create-category');
	Route::post('discover/category/create', 'AdminController@discoverCreateCategoryStore');
	Route::get('discover/category/edit/{id}', 'AdminController@discoverCategoryEdit');
	Route::post('discover/category/edit/{id}', 'AdminController@discoverCategoryUpdate');
	Route::post('discover/category/hashtag/create', 'AdminController@discoveryCategoryTagStore')->name('admin.discover.create-hashtag');

	Route::get('messages/home', 'AdminController@messagesHome')->name('admin.messages');
	Route::get('messages/show/{id}', 'AdminController@messagesShow');
	Route::post('messages/mark-read', 'AdminController@messagesMarkRead');
	Route::redirect('site-news', '/i/admin/newsroom');
	Route::get('newsroom', 'AdminController@newsroomHome')->name('admin.newsroom.home');
	Route::get('newsroom/create', 'AdminController@newsroomCreate')->name('admin.newsroom.create');
	Route::get('newsroom/edit/{id}', 'AdminController@newsroomEdit');
	Route::post('newsroom/edit/{id}', 'AdminController@newsroomUpdate');
	Route::delete('newsroom/edit/{id}', 'AdminController@newsroomDelete');
	Route::post('newsroom/create', 'AdminController@newsroomStore');

	Route::get('diagnostics/home', 'AdminController@diagnosticsHome')->name('admin.diagnostics');
	Route::post('diagnostics/decrypt', 'AdminController@diagnosticsDecrypt')->name('admin.diagnostics.decrypt');
	Route::get('custom-emoji/home', 'AdminController@customEmojiHome')->name('admin.custom-emoji');
	Route::post('custom-emoji/toggle-active/{id}', 'AdminController@customEmojiToggleActive');
	Route::get('custom-emoji/new', 'AdminController@customEmojiAdd');
	Route::post('custom-emoji/new', 'AdminController@customEmojiStore');
	Route::post('custom-emoji/delete/{id}', 'AdminController@customEmojiDelete');
	Route::get('custom-emoji/duplicates/{id}', 'AdminController@customEmojiShowDuplicates');

	Route::get('directory/home', 'AdminController@directoryHome')->name('admin.directory');

	Route::get('autospam/home', 'AdminController@autospamHome')->name('admin.autospam');

	Route::prefix('api')->group(function() {
		Route::get('stats', 'AdminController@getStats');
		Route::get('accounts', 'AdminController@getAccounts');
		Route::get('posts', 'AdminController@getPosts');
		Route::get('instances', 'AdminController@getInstances');
		Route::post('directory/save', 'AdminController@directoryStore');
		Route::get('directory/initial-data', 'AdminController@directoryInitialData');
		Route::get('directory/popular-posts', 'AdminController@directoryGetPopularPosts');
		Route::post('directory/add-by-id', 'AdminController@directoryGetAddPostByIdSearch');
		Route::delete('directory/banner-image', 'AdminController@directoryDeleteBannerImage');
		Route::post('directory/submit', 'AdminController@directoryHandleServerSubmission');
		Route::post('directory/testimonial/save', 'AdminController@directorySaveTestimonial');
		Route::post('directory/testimonial/delete', 'AdminController@directoryDeleteTestimonial');
		Route::post('directory/testimonial/update', 'AdminController@directoryUpdateTestimonial');
		Route::get('hashtags/stats', 'AdminController@hashtagsStats');
		Route::get('hashtags/query', 'AdminController@hashtagsApi');
		Route::get('hashtags/get', 'AdminController@hashtagsGet');
		Route::post('hashtags/update', 'AdminController@hashtagsUpdate');
		Route::post('hashtags/clear-trending-cache', 'AdminController@hashtagsClearTrendingCache');
		Route::get('instances/get', 'AdminController@getInstancesApi');
		Route::get('instances/stats', 'AdminController@getInstancesStatsApi');
		Route::get('instances/query', 'AdminController@getInstancesQueryApi');
		Route::post('instances/update', 'AdminController@postInstanceUpdateApi');
		Route::post('instances/create', 'AdminController@postInstanceCreateNewApi');
		Route::post('instances/delete', 'AdminController@postInstanceDeleteApi');
		Route::post('instances/refresh-stats', 'AdminController@postInstanceRefreshStatsApi');
		Route::get('instances/download-backup', 'AdminController@downloadBackup');
		Route::post('instances/import-data', 'AdminController@importBackup');
		Route::get('reports/stats', 'AdminController@reportsStats');
		Route::get('reports/all', 'AdminController@reportsApiAll');
		Route::get('reports/get/{id}', 'AdminController@reportsApiGet');
		Route::post('reports/handle', 'AdminController@reportsApiHandle');
		Route::get('reports/spam/all', 'AdminController@reportsApiSpamAll');
		Route::get('reports/spam/get/{id}', 'AdminController@reportsApiSpamGet');
		Route::post('reports/spam/handle', 'AdminController@reportsApiSpamHandle');
		Route::post('autospam/config', 'AdminController@getAutospamConfigApi');
		Route::post('autospam/reports/closed', 'AdminController@getAutospamReportsClosedApi');
		Route::post('autospam/train', 'AdminController@postAutospamTrainSpamApi');
		Route::post('autospam/search/non-spam', 'AdminController@postAutospamTrainNonSpamSearchApi');
		Route::post('autospam/train/non-spam', 'AdminController@postAutospamTrainNonSpamSubmitApi');
		Route::post('autospam/tokens/custom', 'AdminController@getAutospamCustomTokensApi');
		Route::post('autospam/tokens/store', 'AdminController@saveNewAutospamCustomTokensApi');
		Route::post('autospam/tokens/update', 'AdminController@updateAutospamCustomTokensApi');
		Route::post('autospam/tokens/export', 'AdminController@exportAutospamCustomTokensApi');
		Route::post('autospam/config/enable', 'AdminController@enableAutospamApi');
		Route::post('autospam/config/disable', 'AdminController@disableAutospamApi');
	});
});

Route::domain(config('portfolio.domain'))->group(function () {
	Route::redirect('redirect/home', config('app.url'));
	Route::get('/', 'PortfolioController@index');
	Route::post('api/portfolio/self/curated.json', 'PortfolioController@storeCurated');
	Route::post('api/portfolio/self/settings.json', 'PortfolioController@getSettings');
	Route::get('api/portfolio/account/settings.json', 'PortfolioController@getAccountSettings');
	Route::post('api/portfolio/self/update-settings.json', 'PortfolioController@storeSettings');
	Route::get('api/portfolio/{username}/feed', 'PortfolioController@getFeed');

	Route::prefix(config('portfolio.path'))->group(function() {
		Route::get('/', 'PortfolioController@index');
		Route::get('settings', 'PortfolioController@settings')->name('portfolio.settings');
		Route::post('settings', 'PortfolioController@store');
		Route::get('{username}/{id}', 'PortfolioController@showPost');
		Route::get('{username}', 'PortfolioController@show');

		Route::fallback(function () {
			return view('errors.404');
		});
	});
});

Route::domain(config('pixelfed.domain.app'))->middleware(['validemail', 'twofactor', 'localization'])->group(function () {
	Route::get('/', 'SiteController@home')->name('timeline.personal');
	Route::redirect('/home', '/')->name('home');
	Route::get('web/directory', 'LandingController@directoryRedirect');
	Route::get('web/explore', 'LandingController@exploreRedirect');

	Auth::routes();

	Route::get('discover', 'DiscoverController@home')->name('discover');

	Route::group(['prefix' => 'api'], function () {
		Route::get('search', 'SearchController@searchAPI');
		Route::post('status/view', 'StatusController@storeView');
		Route::get('v1/polls/{id}', 'PollController@getPoll');
		Route::post('v1/polls/{id}/votes', 'PollController@vote');

		Route::group(['prefix' => 'compose'], function() {
			Route::group(['prefix' => 'v0'], function() {
				Route::post('/media/upload', 'ComposeController@mediaUpload');
				Route::post('/media/update', 'ComposeController@mediaUpdate');
				Route::delete('/media/delete', 'ComposeController@mediaDelete');
				Route::get('/search/tag', 'ComposeController@searchTag');
				Route::get('/search/location', 'ComposeController@searchLocation');
				Route::get('/search/mention', 'ComposeController@searchMentionAutocomplete');
				Route::get('/search/hashtag', 'ComposeController@searchHashtagAutocomplete');

				Route::post('/publish', 'ComposeController@store');
				Route::post('/publish/text', 'ComposeController@storeText');
				Route::get('/media/processing', 'ComposeController@mediaProcessingCheck');
				Route::get('/settings', 'ComposeController@composeSettings');
				Route::post('/poll', 'ComposeController@createPoll');
			});
		});

		Route::group(['prefix' => 'direct'], function () {
			Route::get('browse', 'DirectMessageController@browse');
			Route::post('create', 'DirectMessageController@create');
			Route::get('thread', 'DirectMessageController@thread');
			Route::post('mute', 'DirectMessageController@mute');
			Route::post('unmute', 'DirectMessageController@unmute');
			Route::delete('message', 'DirectMessageController@delete');
			Route::post('media', 'DirectMessageController@mediaUpload');
			Route::post('lookup', 'DirectMessageController@composeLookup');
			Route::post('read', 'DirectMessageController@read');
		});

		Route::group(['prefix' => 'v2'], function() {
			Route::get('config', 'ApiController@siteConfiguration');
			Route::get('discover', 'InternalApiController@discover');
			Route::get('discover/posts', 'InternalApiController@discoverPosts')->middleware('auth:api');
			Route::get('profile/{username}/status/{postid}', 'PublicApiController@status');
			Route::get('profile/{username}/status/{postid}/state', 'PublicApiController@statusState');
			Route::get('comments/{username}/status/{postId}', 'PublicApiController@statusComments');
			Route::get('status/{id}/replies', 'InternalApiController@statusReplies');
			Route::post('moderator/action', 'InternalApiController@modAction');
			Route::get('discover/categories', 'InternalApiController@discoverCategories');
			Route::get('loops', 'DiscoverController@loopsApi');
			Route::post('loops/watch', 'DiscoverController@loopWatch');
			Route::get('discover/tag', 'DiscoverController@getHashtags');
			Route::get('statuses/{id}/replies', 'Api\ApiV1Controller@statusReplies');
			Route::get('statuses/{id}/state', 'Api\ApiV1Controller@statusState');
		});

		Route::group(['prefix' => 'pixelfed'], function() {
			Route::group(['prefix' => 'v1'], function() {
				Route::get('accounts/verify_credentials', 'ApiController@verifyCredentials');
				Route::get('accounts/relationships', 'Api\ApiV1Controller@accountRelationshipsById');
				Route::get('accounts/search', 'Api\ApiV1Controller@accountSearch');
				Route::get('accounts/{id}/statuses', 'PublicApiController@accountStatuses');
				Route::post('accounts/{id}/block', 'Api\ApiV1Controller@accountBlockById');
				Route::post('accounts/{id}/unblock', 'Api\ApiV1Controller@accountUnblockById');
				Route::get('statuses/{id}', 'PublicApiController@getStatus');
				Route::get('accounts/{id}', 'PublicApiController@account');
				Route::post('avatar/update', 'ApiController@avatarUpdate');
				Route::get('custom_emojis', 'Api\ApiV1Controller@customEmojis');
				Route::get('notifications', 'ApiController@notifications');
				Route::get('timelines/public', 'PublicApiController@publicTimelineApi');
				Route::get('timelines/home', 'PublicApiController@homeTimelineApi');
				Route::get('timelines/network', 'PublicApiController@networkTimelineApi');
				Route::get('newsroom/timeline', 'NewsroomController@timelineApi');
				Route::post('newsroom/markasread', 'NewsroomController@markAsRead');
				Route::get('favourites', 'Api\BaseApiController@accountLikes');
				Route::get('mutes', 'AccountController@accountMutes');
				Route::get('blocks', 'AccountController@accountBlocks');
			});

			Route::group(['prefix' => 'v2'], function() {
				Route::get('config', 'ApiController@siteConfiguration');
				Route::get('discover', 'InternalApiController@discover');
				Route::get('discover/posts', 'InternalApiController@discoverPosts');
				Route::get('discover/profiles', 'DiscoverController@profilesDirectoryApi');
				Route::get('profile/{username}/status/{postid}', 'PublicApiController@status');
				Route::get('comments/{username}/status/{postId}', 'PublicApiController@statusComments');
				Route::post('moderator/action', 'InternalApiController@modAction');
				Route::get('discover/categories', 'InternalApiController@discoverCategories');
				Route::get('loops', 'DiscoverController@loopsApi');
				Route::post('loops/watch', 'DiscoverController@loopWatch');
				Route::get('discover/tag', 'DiscoverController@getHashtags');
				Route::get('discover/posts/trending', 'DiscoverController@trendingApi');
				Route::get('discover/posts/hashtags', 'DiscoverController@trendingHashtags');
				Route::get('discover/posts/places', 'DiscoverController@trendingPlaces');
				Route::get('seasonal/yir', 'SeasonalController@getData');
				Route::post('seasonal/yir', 'SeasonalController@store');
				Route::get('mutes', 'AccountController@accountMutesV2');
				Route::get('blocks', 'AccountController@accountBlocksV2');
				Route::get('filters', 'AccountController@accountFiltersV2');
				Route::post('status/compose', 'InternalApiController@composePost');
				Route::get('status/{id}/replies', 'InternalApiController@statusReplies');
				Route::post('status/{id}/archive', 'ApiController@archive');
				Route::post('status/{id}/unarchive', 'ApiController@unarchive');
				Route::get('statuses/archives', 'ApiController@archivedPosts');
				Route::get('discover/memories', 'DiscoverController@myMemories');
				Route::get('discover/account-insights', 'DiscoverController@accountInsightsPopularPosts');
				Route::get('discover/server-timeline', 'DiscoverController@serverTimeline');
				Route::get('discover/meta', 'DiscoverController@enabledFeatures');
				Route::post('discover/admin/features', 'DiscoverController@updateFeatures');
			});

			Route::get('discover/accounts/popular', 'Api\ApiV1Controller@discoverAccountsPopular');
			Route::post('web/change-language.json', 'SpaController@updateLanguage');
		});

		Route::group(['prefix' => 'local'], function () {
			// Route::post('status/compose', 'InternalApiController@composePost')->middleware('throttle:maxPostsPerHour,60')->middleware('throttle:maxPostsPerDay,1440');
			Route::get('exp/rec', 'ApiController@userRecommendations');
			Route::post('discover/tag/subscribe', 'HashtagFollowController@store');
			Route::get('discover/tag/list', 'HashtagFollowController@getTags');
			// Route::get('profile/sponsor/{id}', 'ProfileSponsorController@get');
			Route::get('bookmarks', 'InternalApiController@bookmarks');
			Route::get('collection/items/{id}', 'CollectionController@getItems');
			Route::post('collection/item', 'CollectionController@storeId');
			Route::delete('collection/item', 'CollectionController@deleteId');
			Route::get('collection/{id}', 'CollectionController@getCollection');
			Route::post('collection/{id}', 'CollectionController@store');
			Route::delete('collection/{id}', 'CollectionController@delete');
			Route::post('collection/{id}/publish', 'CollectionController@publish');
			Route::get('profile/collections/{id}', 'CollectionController@getUserCollections');

			Route::post('compose/tag/untagme', 'MediaTagController@untagProfile');

			Route::post('import/ig', 'ImportPostController@store');
			Route::get('import/ig/config', 'ImportPostController@getConfig');
			Route::post('import/ig/media', 'ImportPostController@storeMedia');
			Route::post('import/ig/existing', 'ImportPostController@getImportedFiles');
			Route::post('import/ig/posts', 'ImportPostController@getImportedPosts');
			Route::post('import/ig/processing', 'ImportPostController@getProcessingCount');
		});

		Route::group(['prefix' => 'web/stories'], function () {
			Route::get('v1/recent', 'StoryController@recent');
			Route::get('v1/viewers', 'StoryController@viewers');
			Route::get('v1/profile/{id}', 'StoryController@profile');
			Route::get('v1/exists/{id}', 'StoryController@exists');
			Route::get('v1/poll/results', 'StoryController@pollResults');
			Route::post('v1/viewed', 'StoryController@viewed');
			Route::post('v1/react', 'StoryController@react');
			Route::post('v1/comment', 'StoryController@comment');
			Route::post('v1/publish/poll', 'StoryController@publishStoryPoll');
			Route::post('v1/poll/vote', 'StoryController@storyPollVote');
			Route::post('v1/report', 'StoryController@storeReport');
			Route::post('v1/add', 'StoryController@apiV1Add');
			Route::post('v1/crop', 'StoryController@cropPhoto');
			Route::post('v1/publish', 'StoryController@publishStory');
			Route::delete('v1/delete/{id}', 'StoryController@apiV1Delete');
		});

		Route::group(['prefix' => 'portfolio'], function () {
			Route::post('self/curated.json', 'PortfolioController@storeCurated');
			Route::post('self/settings.json', 'PortfolioController@getSettings');
			Route::get('account/settings.json', 'PortfolioController@getAccountSettings');
			Route::post('self/update-settings.json', 'PortfolioController@storeSettings');
			Route::get('{username}/feed', 'PortfolioController@getFeed');
		});
	});

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
		Route::prefix('import')->group(function() {
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
