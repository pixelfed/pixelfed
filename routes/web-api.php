<?php

use App\Http\Controllers\SoftwareUpdateController;

Route::domain(config('pixelfed.domain.app'))->middleware(['validemail', 'twofactor', 'localization'])->group(function () {
    Route::group(['prefix' => 'api'], function () {
        Route::get('search', 'SearchController@searchAPI');
        Route::post('status/view', 'StatusController@storeView');
        Route::get('v1/polls/{id}', 'PollController@getPoll');
        Route::post('v1/polls/{id}/votes', 'PollController@vote');

        Route::group(['prefix' => 'web-admin'], function() {
            Route::get('software-update/check', [SoftwareUpdateController::class, 'getSoftwareUpdateCheck']);
        });

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
});
