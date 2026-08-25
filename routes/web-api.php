<?php

use App\Http\Controllers\SoftwareUpdateController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\Api\ApiV1Controller;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\ComposeController;
use App\Http\Controllers\DirectMessageController;
use App\Http\Controllers\DiscoverController;
use App\Http\Controllers\HashtagFollowController;
use App\Http\Controllers\ImportPostController;
use App\Http\Controllers\InternalApiController;
use App\Http\Controllers\MediaTagController;
use App\Http\Controllers\NewsroomController;
use App\Http\Controllers\PollController;
use App\Http\Controllers\PortfolioController;
use App\Http\Controllers\ProfileSponsorController;
use App\Http\Controllers\PublicApiController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SeasonalController;
use App\Http\Controllers\SpaController;
use App\Http\Controllers\StatusController;
use App\Http\Controllers\StoryController;

Route::domain(config('pixelfed.domain.app'))->middleware(['validemail', 'twofactor', 'localization'])->group(function () {
    Route::group(['prefix' => 'api'], function () {
        Route::get('search', [SearchController::class, 'searchAPI']);
        Route::post('status/view', [StatusController::class, 'storeView']);
        Route::get('v1/polls/{id}', [PollController::class, 'getPoll']);
        Route::post('v1/polls/{id}/votes', [PollController::class, 'vote']);

        Route::group(['prefix' => 'web-admin'], function() {
            Route::get('software-update/check', [SoftwareUpdateController::class, 'getSoftwareUpdateCheck']);
        });

        Route::group(['prefix' => 'compose'], function() {
            Route::group(['prefix' => 'v0'], function() {
                Route::post('/media/upload', [ComposeController::class, 'mediaUpload']);
                Route::post('/media/update', [ComposeController::class, 'mediaUpdate']);
                Route::delete('/media/delete', [ComposeController::class, 'mediaDelete']);
                Route::get('/search/tag', [ComposeController::class, 'searchTag']);
                Route::get('/search/location', [ComposeController::class, 'searchLocation']);
                Route::get('/search/mention', [ComposeController::class, 'searchMentionAutocomplete']);
                Route::get('/search/hashtag', [ComposeController::class, 'searchHashtagAutocomplete']);

                Route::post('/publish', [ComposeController::class, 'store']);
                Route::post('/publish/text', [ComposeController::class, 'storeText']);
                Route::get('/media/processing', [ComposeController::class, 'mediaProcessingCheck']);
                Route::get('/settings', [ComposeController::class, 'composeSettings']);
                Route::post('/poll', [ComposeController::class, 'createPoll']);
            });
        });

        Route::group(['prefix' => 'direct'], function () {
            Route::get('browse', [DirectMessageController::class, 'browse']);
            Route::post('create', [DirectMessageController::class, 'create']);
            Route::get('thread', [DirectMessageController::class, 'thread']);
            Route::post('mute', [DirectMessageController::class, 'mute']);
            Route::post('unmute', [DirectMessageController::class, 'unmute']);
            Route::delete('message', [DirectMessageController::class, 'delete']);
            Route::post('media', [DirectMessageController::class, 'mediaUpload']);
            Route::post('lookup', [DirectMessageController::class, 'composeLookup']);
            Route::post('read', [DirectMessageController::class, 'read']);
        });

        Route::group(['prefix' => 'v2'], function() {
            Route::get('config', [ApiController::class, 'siteConfiguration']);
            Route::get('discover', [InternalApiController::class, 'discover']);
            Route::get('discover/posts', [InternalApiController::class, 'discoverPosts'])->middleware('auth:api');
            Route::get('profile/{username}/status/{postid}', [PublicApiController::class, 'status']);
            Route::get('profile/{username}/status/{postid}/state', [PublicApiController::class, 'statusState']);
            Route::get('comments/{username}/status/{postId}', [PublicApiController::class, 'statusComments']);
            Route::get('status/{id}/replies', [InternalApiController::class, 'statusReplies']);
            Route::post('moderator/action', [InternalApiController::class, 'modAction']);
            Route::get('discover/categories', [InternalApiController::class, 'discoverCategories']);
            Route::get('discover/tag', [DiscoverController::class, 'getHashtags']);
            Route::get('statuses/{id}/replies', [ApiV1Controller::class, 'statusReplies']);
            Route::get('statuses/{id}/state', [ApiV1Controller::class, 'statusState']);
        });

        Route::group(['prefix' => 'pixelfed'], function() {
            Route::group(['prefix' => 'v1'], function() {
                Route::get('accounts/verify_credentials', [ApiController::class, 'verifyCredentials']);
                Route::get('accounts/relationships', [ApiV1Controller::class, 'accountRelationshipsById']);
                Route::get('accounts/search', [ApiV1Controller::class, 'accountSearch']);
                Route::get('accounts/{id}/statuses', [PublicApiController::class, 'accountStatuses']);
                Route::post('accounts/{id}/block', [ApiV1Controller::class, 'accountBlockById']);
                Route::post('accounts/{id}/unblock', [ApiV1Controller::class, 'accountUnblockById']);
                Route::post('accounts/{id}/remove_from_followers', [ApiV1Controller::class, 'accountRemoveFollowById']);
                Route::get('statuses/{id}', [PublicApiController::class, 'getStatus']);
                Route::post('statuses/{id}/pin', [PublicApiController::class, 'statusPin']);
                Route::post('statuses/{id}/unpin', [PublicApiController::class, 'statusUnpin']);
                Route::get('accounts/{id}', [PublicApiController::class, 'account']);
                Route::post('avatar/update', [ApiController::class, 'avatarUpdate']);
                Route::get('custom_emojis', [ApiV1Controller::class, 'customEmojis']);
                Route::get('notifications', [ApiController::class, 'notifications']);
                Route::get('timelines/public', [PublicApiController::class, 'publicTimelineApi']);
                Route::get('timelines/home', [PublicApiController::class, 'homeTimelineApi']);
                Route::get('timelines/network', [PublicApiController::class, 'networkTimelineApi']);
                Route::get('newsroom/timeline', [NewsroomController::class, 'timelineApi']);
                Route::post('newsroom/markasread', [NewsroomController::class, 'markAsRead']);
                Route::get('favourites', [BaseApiController::class, 'accountLikes']);
                Route::get('mutes', [AccountController::class, 'accountMutes']);
                Route::get('blocks', [AccountController::class, 'accountBlocks']);
            });

            Route::group(['prefix' => 'v2'], function() {
                Route::get('config', [ApiController::class, 'siteConfiguration']);
                Route::get('discover', [InternalApiController::class, 'discover']);
                Route::get('discover/posts', [InternalApiController::class, 'discoverPosts']);
                Route::get('discover/profiles', [DiscoverController::class, 'profilesDirectoryApi']);
                Route::get('profile/{username}/status/{postid}', [PublicApiController::class, 'status']);
                Route::get('comments/{username}/status/{postId}', [PublicApiController::class, 'statusComments']);
                Route::post('moderator/action', [InternalApiController::class, 'modAction']);
                Route::get('discover/categories', [InternalApiController::class, 'discoverCategories']);
                Route::get('discover/tag', [DiscoverController::class, 'getHashtags']);
                Route::get('discover/posts/trending', [DiscoverController::class, 'trendingApi']);
                Route::get('discover/posts/hashtags', [DiscoverController::class, 'trendingHashtags']);
                Route::get('discover/posts/places', [DiscoverController::class, 'trendingPlaces']);
                Route::get('seasonal/yir', [SeasonalController::class, 'getData']);
                Route::post('seasonal/yir', [SeasonalController::class, 'store']);
                Route::get('mutes', [AccountController::class, 'accountMutesV2']);
                Route::get('blocks', [AccountController::class, 'accountBlocksV2']);
                Route::get('filters', [AccountController::class, 'accountFiltersV2']);
                Route::post('status/compose', [InternalApiController::class, 'composePost']);
                Route::get('status/{id}/replies', [InternalApiController::class, 'statusReplies']);
                Route::post('status/{id}/archive', [ApiController::class, 'archive']);
                Route::post('status/{id}/unarchive', [ApiController::class, 'unarchive']);
                Route::get('statuses/archives', [ApiController::class, 'archivedPosts']);
                Route::get('discover/memories', [DiscoverController::class, 'myMemories']);
                Route::get('discover/account-insights', [DiscoverController::class, 'accountInsightsPopularPosts']);
                Route::get('discover/server-timeline', [DiscoverController::class, 'serverTimeline']);
                Route::get('discover/meta', [DiscoverController::class, 'enabledFeatures']);
                Route::post('discover/admin/features', [DiscoverController::class, 'updateFeatures']);
            });

            Route::get('discover/accounts/popular', [DiscoverController::class, 'discoverAccountsPopular']);
            Route::post('web/change-language.json', [SpaController::class, 'updateLanguage']);
        });

        Route::group(['prefix' => 'local'], function () {
            // Route::post('status/compose', [InternalApiController::class, 'composePost'])->middleware('throttle:maxPostsPerHour,60')->middleware('throttle:maxPostsPerDay,1440');
            Route::post('discover/tag/subscribe', [HashtagFollowController::class, 'store']);
            Route::get('discover/tag/list', [HashtagFollowController::class, 'getTags']);
            // Route::get('profile/sponsor/{id}', [ProfileSponsorController::class, 'get']);
            Route::get('bookmarks', [InternalApiController::class, 'bookmarks']);
            Route::get('collection/items/{id}', [CollectionController::class, 'getItems']);
            Route::post('collection/item', [CollectionController::class, 'storeId']);
            Route::delete('collection/item', [CollectionController::class, 'deleteId']);
            Route::get('collection/{id}', [CollectionController::class, 'getCollection']);
            Route::post('collection/{id}', [CollectionController::class, 'store']);
            Route::delete('collection/{id}', [CollectionController::class, 'delete']);
            Route::post('collection/{id}/publish', [CollectionController::class, 'publish']);
            Route::get('profile/collections/{id}', [CollectionController::class, 'getUserCollections']);

            Route::post('compose/tag/untagme', [MediaTagController::class, 'untagProfile']);

            Route::post('import/ig', [ImportPostController::class, 'store']);
            Route::get('import/ig/config', [ImportPostController::class, 'getConfig']);
            Route::post('import/ig/media', [ImportPostController::class, 'storeMedia']);
            Route::post('import/ig/existing', [ImportPostController::class, 'getImportedFiles']);
            Route::post('import/ig/posts', [ImportPostController::class, 'getImportedPosts']);
            Route::post('import/ig/processing', [ImportPostController::class, 'getProcessingCount']);
        });

        Route::group(['prefix' => 'web/stories'], function () {
            Route::get('v1/recent', [StoryController::class, 'recent']);
            Route::get('v1/viewers', [StoryController::class, 'viewers']);
            Route::get('v1/profile/{id}', [StoryController::class, 'profile']);
            Route::get('v1/exists/{id}', [StoryController::class, 'exists']);
            Route::get('v1/poll/results', [StoryController::class, 'pollResults']);
            Route::post('v1/viewed', [StoryController::class, 'viewed']);
            Route::post('v1/react', [StoryController::class, 'react']);
            Route::post('v1/comment', [StoryController::class, 'comment']);
            Route::post('v1/publish/poll', [StoryController::class, 'publishStoryPoll']);
            Route::post('v1/poll/vote', [StoryController::class, 'storyPollVote']);
            Route::post('v1/report', [StoryController::class, 'storeReport']);
            Route::post('v1/add', [StoryController::class, 'apiV1Add']);
            Route::post('v1/crop', [StoryController::class, 'cropPhoto']);
            Route::post('v1/publish', [StoryController::class, 'publishStory']);
            Route::delete('v1/delete/{id}', [StoryController::class, 'apiV1Delete']);
        });

        Route::group(['prefix' => 'portfolio'], function () {
            Route::post('self/curated.json', [PortfolioController::class, 'storeCurated']);
            Route::post('self/settings.json', [PortfolioController::class, 'getSettings']);
            Route::get('account/settings.json', [PortfolioController::class, 'getAccountSettings']);
            Route::post('self/update-settings.json', [PortfolioController::class, 'storeSettings']);
            Route::get('{username}/feed', [PortfolioController::class, 'getFeed']);
        });
    });
});
