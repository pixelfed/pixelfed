<?php

use App\Http\Controllers\AdminInviteController;
use App\Http\Controllers\Api\AdminApiController;
use App\Http\Controllers\Api\ApiV1Controller;
use App\Http\Controllers\Api\ApiV1Dot1Controller;
use App\Http\Controllers\Api\ApiV2Controller;
use App\Http\Controllers\Api\V1\Admin\DomainBlocksController;
use App\Http\Controllers\Api\V1\DomainBlockController;
use App\Http\Controllers\Api\V1\TagsController;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\AppRegisterController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\ComposeController;
use App\Http\Controllers\CustomFilterController;
use App\Http\Controllers\DirectMessageController;
use App\Http\Controllers\DiscoverController;
use App\Http\Controllers\FederationController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\Groups\CreateGroupsController;
use App\Http\Controllers\Groups\GroupsAdminController;
use App\Http\Controllers\Groups\GroupsApiController;
use App\Http\Controllers\Groups\GroupsCommentController;
use App\Http\Controllers\Groups\GroupsDiscoverController;
use App\Http\Controllers\Groups\GroupsFeedController;
use App\Http\Controllers\Groups\GroupsMemberController;
use App\Http\Controllers\Groups\GroupsMetaController;
use App\Http\Controllers\Groups\GroupsNotificationsController;
use App\Http\Controllers\Groups\GroupsPostController;
use App\Http\Controllers\Groups\GroupsSearchController;
use App\Http\Controllers\Groups\GroupsTopicController;
use App\Http\Controllers\HealthCheckController;
use App\Http\Controllers\InstanceActorController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\LiveStreamController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\PixelfedDirectoryController;
use App\Http\Controllers\StatusEditController;
use App\Http\Controllers\Stories\StoryApiV1Controller;
use App\Http\Controllers\StoryController;
use App\Http\Controllers\UserAppSettingsController;
use App\Http\Controllers\VinylHubAccountEdgeController;
use App\Http\Controllers\VinylHubStatusOperationController;

$middleware = ['auth:sanctum,api', 'validemail'];

Route::prefix('api/v1/internal/vinylhub/account-edge')->middleware('vinylhub.service')->group(function () {
    Route::post('provision', [VinylHubAccountEdgeController::class, 'provision']);
    Route::post('read', [VinylHubAccountEdgeController::class, 'read']);
    Route::post('credential/renew', [VinylHubAccountEdgeController::class, 'renew']);
    Route::post('credential/revoke', [VinylHubAccountEdgeController::class, 'revoke']);
    Route::post('suspend', [VinylHubAccountEdgeController::class, 'suspend']);
    Route::post('resume', [VinylHubAccountEdgeController::class, 'resume']);
    Route::post('delete', [VinylHubAccountEdgeController::class, 'delete']);
    Route::post('delete-status', [VinylHubAccountEdgeController::class, 'deleteStatus']);
});

Route::prefix('api/v1/internal/vinylhub/status-operation')->middleware('vinylhub.service')->group(function () {
    Route::post('create', [VinylHubStatusOperationController::class, 'create']);
    Route::post('read', [VinylHubStatusOperationController::class, 'read']);
});

Route::post('/f/inbox', [FederationController::class, 'sharedInbox']);
Route::post('/users/{username}/inbox', [FederationController::class, 'userInbox']);
Route::get('i/actor', [InstanceActorController::class, 'profile']);
Route::post('i/actor/inbox', [InstanceActorController::class, 'inbox']);
Route::get('i/actor/outbox', [InstanceActorController::class, 'outbox']);
Route::get('/stories/{username}/{id}', [StoryController::class, 'getActivityObject']);

Route::get('.well-known/webfinger', [FederationController::class, 'webfinger'])->name('well-known.webfinger');
Route::get('.well-known/nodeinfo', [FederationController::class, 'nodeinfoWellKnown'])->name('well-known.nodeinfo');
Route::get('.well-known/host-meta', [FederationController::class, 'hostMeta'])->name('well-known.hostMeta');
Route::redirect('.well-known/change-password', '/settings/password');
Route::get('api/nodeinfo/2.0.json', [FederationController::class, 'nodeinfo']);
Route::get('api/service/health-check', [HealthCheckController::class, 'get']);
Route::post('api/auth/app-code-verify', [AppRegisterController::class, 'verifyCode'])->middleware('throttle:app-code-verify');
Route::post('api/auth/onboarding', [AppRegisterController::class, 'onboarding'])->middleware('throttle:app-code-verify');
Route::get('storage/m/_v2/{pid}/{mhash}/{uhash}/{f}', [MediaController::class, 'fallbackRedirect']);

Route::prefix('api/v0/groups')->middleware($middleware)->group(function () {
    Route::get('config', [GroupsApiController::class, 'getConfig']);
    Route::post('permission/create', [CreateGroupsController::class, 'checkCreatePermission']);
    Route::post('create', [CreateGroupsController::class, 'storeGroup']);

    Route::post('search/invite/friends/send', [GroupsSearchController::class, 'inviteFriendsToGroup']);
    Route::post('search/invite/friends', [GroupsSearchController::class, 'searchFriendsToInvite']);
    Route::post('search/global', [GroupsSearchController::class, 'searchGlobalResults']);
    Route::post('search/lac', [GroupsSearchController::class, 'searchLocalAutocomplete']);
    Route::post('search/addrec', [GroupsSearchController::class, 'searchAddRecent']);
    Route::get('search/getrec', [GroupsSearchController::class, 'searchGetRecent']);
    Route::get('comments', [GroupsCommentController::class, 'getComments']);
    Route::post('comment', [GroupsCommentController::class, 'storeComment']);
    Route::post('comment/photo', [GroupsCommentController::class, 'storeCommentPhoto']);
    Route::post('comment/delete', [GroupsCommentController::class, 'deleteComment']);
    Route::get('discover/popular', [GroupsDiscoverController::class, 'getDiscoverPopular']);
    Route::get('discover/new', [GroupsDiscoverController::class, 'getDiscoverNew']);
    Route::post('delete', [GroupsMetaController::class, 'deleteGroup']);
    Route::post('status/new', [GroupsPostController::class, 'storePost']);
    Route::post('status/delete', [GroupsPostController::class, 'deletePost']);
    Route::post('status/like', [GroupsPostController::class, 'likePost']);
    Route::post('status/unlike', [GroupsPostController::class, 'unlikePost']);
    Route::get('topics/list', [GroupsTopicController::class, 'groupTopics']);
    Route::get('topics/tag', [GroupsTopicController::class, 'groupTopicTag']);
    Route::get('accounts/{gid}/{pid}', [GroupsApiController::class, 'getGroupAccount']);
    Route::get('categories/list', [GroupsApiController::class, 'getGroupCategories']);
    Route::get('category/list', [GroupsApiController::class, 'getGroupsByCategory']);
    Route::get('self/recommended/list', [GroupsApiController::class, 'getRecommendedGroups']);
    Route::get('self/list', [GroupsApiController::class, 'getSelfGroups']);
    Route::get('media/list', [GroupsPostController::class, 'getGroupMedia']);
    Route::get('members/list', [GroupsMemberController::class, 'getGroupMembers']);
    Route::get('members/requests', [GroupsMemberController::class, 'getGroupMemberJoinRequests']);
    Route::post('members/request', [GroupsMemberController::class, 'handleGroupMemberJoinRequest']);
    Route::get('members/get', [GroupsMemberController::class, 'getGroupMember']);
    Route::get('member/intersect/common', [GroupsMemberController::class, 'getGroupMemberCommonIntersections']);
    Route::get('status', [GroupsPostController::class, 'getStatus']);
    Route::post('like', [GroupController::class, 'likePost']);
    Route::post('comment/like', [GroupsCommentController::class, 'likePost']);
    Route::post('comment/unlike', [GroupsCommentController::class, 'unlikePost']);
    Route::get('self/feed', [GroupsFeedController::class, 'getSelfFeed']);
    Route::get('self/notifications', [GroupsNotificationsController::class, 'selfGlobalNotifications']);
    Route::get('{id}/user/{pid}/feed', [GroupsFeedController::class, 'getGroupProfileFeed']);
    Route::get('{id}/feed', [GroupsFeedController::class, 'getGroupFeed']);
    Route::get('{id}/atabs', [GroupsAdminController::class, 'getAdminTabs']);
    Route::get('{id}/admin/interactions', [GroupsAdminController::class, 'getInteractionLogs']);
    Route::get('{id}/admin/blocks', [GroupsAdminController::class, 'getBlocks']);
    Route::post('{id}/admin/blocks/add', [GroupsAdminController::class, 'addBlock']);
    Route::post('{id}/admin/blocks/undo', [GroupsAdminController::class, 'undoBlock']);
    Route::post('{id}/admin/blocks/export', [GroupsAdminController::class, 'exportBlocks']);
    Route::get('{id}/reports/list', [GroupsAdminController::class, 'getReportList']);

    Route::get('{id}/members/interaction-limits', [GroupController::class, 'getMemberInteractionLimits']);
    Route::post('{id}/invite/check', [GroupController::class, 'groupMemberInviteCheck']);
    Route::post('{id}/invite/accept', [GroupController::class, 'groupMemberInviteAccept']);
    Route::post('{id}/invite/decline', [GroupController::class, 'groupMemberInviteDecline']);
    Route::post('{id}/members/interaction-limits', [GroupController::class, 'updateMemberInteractionLimits']);
    Route::post('{id}/report/action', [GroupController::class, 'reportAction']);
    Route::post('{id}/report/create', [GroupController::class, 'reportCreate']);
    Route::post('{id}/admin/mbs', [GroupController::class, 'metaBlockSearch']);
    Route::post('{id}/join', [GroupController::class, 'joinGroup']);
    Route::post('{id}/cjr', [GroupController::class, 'cancelJoinRequest']);
    Route::post('{id}/leave', [GroupController::class, 'groupLeave']);
    Route::post('{id}/settings', [GroupController::class, 'updateGroup']);
    Route::get('{id}/likes/{sid}', [GroupController::class, 'showStatusLikes']);
    Route::get('{id}', [GroupController::class, 'getGroup']);
});

Route::group(['prefix' => 'api'], function () use ($middleware) {

    Route::group(['prefix' => 'v1'], function () use ($middleware) {
        Route::post('apps', [ApiV1Controller::class, 'apps']);
        Route::get('apps/verify_credentials', [ApiV1Controller::class, 'getApp'])->middleware($middleware);
        Route::get('instance', [ApiV1Controller::class, 'instance']);
        Route::get('instance/peers', [ApiV1Controller::class, 'instancePeers']);
        Route::get('bookmarks', [ApiV1Controller::class, 'bookmarks'])->middleware($middleware);

        Route::get('accounts/verify_credentials', [ApiV1Controller::class, 'verifyCredentials'])->middleware($middleware);
        Route::match(['post', 'patch'], 'accounts/update_credentials', [ApiV1Controller::class, 'accountUpdateCredentials'])->middleware($middleware);
        Route::get('accounts/relationships', [ApiV1Controller::class, 'accountRelationshipsById'])->middleware($middleware);
        Route::get('accounts/lookup', [ApiV1Controller::class, 'accountLookupById'])->middleware('throttle:account-lookup');
        Route::get('accounts/search', [ApiV1Controller::class, 'accountSearch'])->middleware($middleware);
        Route::get('accounts/{id}/statuses', [ApiV1Controller::class, 'accountStatusesById'])->middleware($middleware);
        Route::get('accounts/{id}/following', [ApiV1Controller::class, 'accountFollowingById'])->middleware($middleware);
        Route::get('accounts/{id}/followers', [ApiV1Controller::class, 'accountFollowersById'])->middleware($middleware);
        Route::post('accounts/{id}/follow', [ApiV1Controller::class, 'accountFollowById'])->middleware($middleware);
        Route::post('accounts/{id}/unfollow', [ApiV1Controller::class, 'accountUnfollowById'])->middleware($middleware);
        Route::post('accounts/{id}/block', [ApiV1Controller::class, 'accountBlockById'])->middleware($middleware);
        Route::post('accounts/{id}/unblock', [ApiV1Controller::class, 'accountUnblockById'])->middleware($middleware);
        Route::post('accounts/{id}/remove_from_followers', [ApiV1Controller::class, 'accountRemoveFollowById'])->middleware($middleware);
        Route::post('accounts/{id}/pin', [ApiV1Controller::class, 'accountEndorsements'])->middleware($middleware);
        Route::post('accounts/{id}/unpin', [ApiV1Controller::class, 'accountEndorsements'])->middleware($middleware);
        Route::post('accounts/{id}/mute', [ApiV1Controller::class, 'accountMuteById'])->middleware($middleware);
        Route::post('accounts/{id}/unmute', [ApiV1Controller::class, 'accountUnmuteById'])->middleware($middleware);
        Route::get('accounts/{id}/lists', [ApiV1Controller::class, 'accountListsById'])->middleware($middleware);
        Route::get('lists/{id}/accounts', [ApiV1Controller::class, 'accountListsById'])->middleware($middleware);
        Route::get('accounts/{id}', [ApiV1Controller::class, 'accountById'])->middleware($middleware);

        Route::post('avatar/update', [ApiController::class, 'avatarUpdate'])->middleware($middleware);
        Route::get('blocks', [ApiV1Controller::class, 'accountBlocks'])->middleware($middleware);
        Route::get('conversations', [ApiV1Controller::class, 'conversations'])->middleware($middleware);
        Route::get('custom_emojis', [ApiV1Controller::class, 'customEmojis']);
        Route::get('domain_blocks', [DomainBlockController::class, 'index'])->middleware($middleware);
        Route::post('domain_blocks', [DomainBlockController::class, 'store'])->middleware($middleware);
        Route::delete('domain_blocks', [DomainBlockController::class, 'delete'])->middleware($middleware);
        Route::get('endorsements', [ApiV1Controller::class, 'accountEndorsements'])->middleware($middleware);
        Route::get('favourites', [ApiV1Controller::class, 'accountFavourites'])->middleware($middleware);
        Route::get('filters', [ApiV1Controller::class, 'accountFilters'])->middleware($middleware);
        Route::get('follow_requests', [ApiV1Controller::class, 'accountFollowRequests'])->middleware($middleware);
        Route::post('follow_requests/{id}/authorize', [ApiV1Controller::class, 'accountFollowRequestAccept'])->middleware($middleware);
        Route::post('follow_requests/{id}/reject', [ApiV1Controller::class, 'accountFollowRequestReject'])->middleware($middleware);
        Route::get('lists', [ApiV1Controller::class, 'accountLists'])->middleware($middleware);
        Route::post('media', [ApiV1Controller::class, 'mediaUpload'])->middleware($middleware);
        Route::get('media/{id}', [ApiV1Controller::class, 'mediaGet'])->middleware($middleware);
        Route::put('media/{id}', [ApiV1Controller::class, 'mediaUpdate'])->middleware($middleware);
        Route::get('mutes', [ApiV1Controller::class, 'accountMutes'])->middleware($middleware);
        Route::get('notifications', [ApiV1Controller::class, 'accountNotifications'])->middleware($middleware);
        Route::get('suggestions', [ApiV1Controller::class, 'accountSuggestions'])->middleware($middleware);

        Route::post('statuses/{id}/favourite', [ApiV1Controller::class, 'statusFavouriteById'])->middleware($middleware);
        Route::post('statuses/{id}/unfavourite', [ApiV1Controller::class, 'statusUnfavouriteById'])->middleware($middleware);
        Route::get('statuses/{id}/context', [ApiV1Controller::class, 'statusContext'])->middleware($middleware);
        Route::get('statuses/{id}/card', [ApiV1Controller::class, 'statusCard'])->middleware($middleware);
        Route::get('statuses/{id}/reblogged_by', [ApiV1Controller::class, 'statusRebloggedBy'])->middleware($middleware);
        Route::get('statuses/{id}/favourited_by', [ApiV1Controller::class, 'statusFavouritedBy'])->middleware($middleware);
        Route::post('statuses/{id}/reblog', [ApiV1Controller::class, 'statusShare'])->middleware($middleware);
        Route::post('statuses/{id}/unreblog', [ApiV1Controller::class, 'statusUnshare'])->middleware($middleware);
        Route::post('statuses/{id}/bookmark', [ApiV1Controller::class, 'bookmarkStatus'])->middleware($middleware);
        Route::post('statuses/{id}/unbookmark', [ApiV1Controller::class, 'unbookmarkStatus'])->middleware($middleware);
        Route::post('statuses/{id}/pin', [ApiV1Controller::class, 'statusPin'])->middleware($middleware);
        Route::post('statuses/{id}/unpin', [ApiV1Controller::class, 'statusUnpin'])->middleware($middleware);
        Route::delete('statuses/{id}', [ApiV1Controller::class, 'statusDelete'])->middleware($middleware);
        Route::get('statuses/{id}', [ApiV1Controller::class, 'statusById'])->middleware($middleware);
        Route::post('statuses', [ApiV1Controller::class, 'statusCreate'])->middleware($middleware);

        Route::get('timelines/home', [ApiV1Controller::class, 'timelineHome'])->middleware($middleware);
        Route::get('timelines/public', [ApiV1Controller::class, 'timelinePublic'])->middleware($middleware);
        Route::get('timelines/tag/{hashtag}', [ApiV1Controller::class, 'timelineHashtag'])->middleware($middleware);
        Route::get('discover/posts', [ApiV1Controller::class, 'discoverPosts'])->middleware($middleware);

        Route::get('preferences', [ApiV1Controller::class, 'getPreferences'])->middleware($middleware);
        Route::get('trends', [ApiV1Controller::class, 'getTrends'])->middleware($middleware);
        Route::get('announcements', [ApiV1Controller::class, 'getAnnouncements'])->middleware($middleware);
        Route::get('markers', [ApiV1Controller::class, 'getMarkers'])->middleware($middleware);
        Route::post('markers', [ApiV1Controller::class, 'setMarkers'])->middleware($middleware);

        Route::get('followed_tags', [TagsController::class, 'getFollowedTags'])->middleware($middleware);
        Route::post('tags/{id}/follow', [TagsController::class, 'followHashtag'])->middleware($middleware);
        Route::post('tags/{id}/unfollow', [TagsController::class, 'unfollowHashtag'])->middleware($middleware);
        Route::get('tags/{id}/related', [TagsController::class, 'relatedTags'])->middleware($middleware);
        Route::get('tags/{id}', [TagsController::class, 'getHashtag'])->middleware($middleware);

        Route::get('statuses/{id}/history', [StatusEditController::class, 'history'])->middleware($middleware);
        Route::put('statuses/{id}', [StatusEditController::class, 'store'])->middleware($middleware);

        Route::group(['prefix' => 'admin'], function () use ($middleware) {
            Route::get('domain_blocks', [DomainBlocksController::class, 'index'])->middleware($middleware);
            Route::post('domain_blocks', [DomainBlocksController::class, 'create'])->middleware($middleware);
            Route::get('domain_blocks/{id}', [DomainBlocksController::class, 'show'])->middleware($middleware);
            Route::put('domain_blocks/{id}', [DomainBlocksController::class, 'update'])->middleware($middleware);
            Route::delete('domain_blocks/{id}', [DomainBlocksController::class, 'delete'])->middleware($middleware);
        })->middleware($middleware);
    });

    Route::group(['prefix' => 'v2'], function () use ($middleware) {
        Route::get('search', [ApiV2Controller::class, 'search'])->middleware($middleware);
        Route::post('media', [ApiV2Controller::class, 'mediaUploadV2'])->middleware($middleware);
        Route::get('streaming/config', [ApiV2Controller::class, 'getWebsocketConfig']);
        Route::get('instance', [ApiV2Controller::class, 'instance']);

        Route::get('filters', [CustomFilterController::class, 'index'])->middleware($middleware);
        Route::get('filters/{id}', [CustomFilterController::class, 'show'])->middleware($middleware);
        Route::post('filters', [CustomFilterController::class, 'store'])->middleware($middleware);
        Route::put('filters/{id}', [CustomFilterController::class, 'update'])->middleware($middleware);
        Route::delete('filters/{id}', [CustomFilterController::class, 'delete'])->middleware($middleware);
    });

    Route::group(['prefix' => 'v1.1'], function () use ($middleware) {
        Route::post('report', [ApiV1Dot1Controller::class, 'report'])->middleware($middleware);

        Route::group(['prefix' => 'accounts'], function () use ($middleware) {
            Route::get('timelines/home', [ApiV1Controller::class, 'timelineHome'])->middleware($middleware);
            Route::delete('avatar', [ApiV1Dot1Controller::class, 'deleteAvatar'])->middleware($middleware);
            Route::get('{id}/posts', [ApiV1Dot1Controller::class, 'accountPosts'])->middleware($middleware);
            Route::post('change-password', [ApiV1Dot1Controller::class, 'accountChangePassword'])->middleware($middleware);
            Route::get('login-activity', [ApiV1Dot1Controller::class, 'accountLoginActivity'])->middleware($middleware);
            Route::get('two-factor', [ApiV1Dot1Controller::class, 'accountTwoFactor'])->middleware($middleware);
            Route::get('emails-from-pixelfed', [ApiV1Dot1Controller::class, 'accountEmailsFromPixelfed'])->middleware($middleware);
            Route::get('apps-and-applications', [ApiV1Dot1Controller::class, 'accountApps'])->middleware($middleware);
            Route::get('mutuals/{id}', [ApiV1Dot1Controller::class, 'getMutualAccounts'])->middleware($middleware);
            Route::get('username/{username}', [ApiV1Dot1Controller::class, 'accountUsernameToId'])->middleware($middleware);
        });

        Route::group(['prefix' => 'collections'], function () use ($middleware) {
            Route::get('accounts/{id}', [CollectionController::class, 'getUserCollections'])->middleware($middleware);
            Route::get('items/{id}', [CollectionController::class, 'getItems'])->middleware($middleware);
            Route::get('view/{id}', [CollectionController::class, 'getCollection'])->middleware($middleware);
            Route::post('add', [CollectionController::class, 'storeId'])->middleware($middleware);
            Route::post('update/{id}', [CollectionController::class, 'store'])->middleware($middleware);
            Route::delete('delete/{id}', [CollectionController::class, 'delete'])->middleware($middleware);
            Route::post('remove', [CollectionController::class, 'deleteId'])->middleware($middleware);
            Route::get('self', [CollectionController::class, 'getSelfCollections'])->middleware($middleware);
        });

        Route::group(['prefix' => 'direct'], function () use ($middleware) {
            Route::get('thread', [DirectMessageController::class, 'thread'])->middleware($middleware);
            Route::post('thread/send', [DirectMessageController::class, 'create'])->middleware($middleware);
            Route::delete('thread/message', [DirectMessageController::class, 'delete'])->middleware($middleware);
            Route::post('thread/mute', [DirectMessageController::class, 'mute'])->middleware($middleware);
            Route::post('thread/unmute', [DirectMessageController::class, 'unmute'])->middleware($middleware);
            Route::post('thread/media', [DirectMessageController::class, 'mediaUpload'])->middleware($middleware);
            Route::post('thread/read', [DirectMessageController::class, 'read'])->middleware($middleware);
            Route::post('lookup', [DirectMessageController::class, 'composeLookup'])->middleware($middleware);
            Route::get('compose/mutuals', [DirectMessageController::class, 'composeMutuals'])->middleware($middleware);
        });

        Route::group(['prefix' => 'archive'], function () use ($middleware) {
            Route::post('add/{id}', [ApiV1Dot1Controller::class, 'archive'])->middleware($middleware);
            Route::post('remove/{id}', [ApiV1Dot1Controller::class, 'unarchive'])->middleware($middleware);
            Route::get('list', [ApiV1Dot1Controller::class, 'archivedPosts'])->middleware($middleware);
        });

        Route::group(['prefix' => 'places'], function () use ($middleware) {
            Route::get('posts/{id}/{slug}', [ApiV1Dot1Controller::class, 'placesById'])->middleware($middleware);
        });

        Route::group(['prefix' => 'stories'], function () use ($middleware) {
            Route::get('carousel', [StoryApiV1Controller::class, 'carousel'])->middleware($middleware);
            Route::post('add', [StoryApiV1Controller::class, 'add'])->middleware($middleware);
            Route::post('publish', [StoryApiV1Controller::class, 'publish'])->middleware($middleware);
            Route::post('seen', [StoryApiV1Controller::class, 'viewed'])->middleware($middleware);
            Route::post('self-expire/{id}', [StoryApiV1Controller::class, 'delete'])->middleware($middleware);
            Route::post('comment', [StoryApiV1Controller::class, 'comment'])->middleware($middleware);
        });

        Route::group(['prefix' => 'compose'], function () use ($middleware) {
            Route::get('search/location', [ComposeController::class, 'searchLocation'])->middleware($middleware);
            Route::get('settings', [ComposeController::class, 'composeSettings'])->middleware($middleware);
        });

        Route::group(['prefix' => 'discover'], function () use ($middleware) {
            Route::get('accounts/popular', [ApiV1Controller::class, 'discoverAccountsPopular'])->middleware($middleware);
            Route::get('posts/trending', [DiscoverController::class, 'trendingApi'])->middleware($middleware);
            Route::get('posts/hashtags', [DiscoverController::class, 'trendingHashtags'])->middleware($middleware);
            Route::get('posts/network/trending', [DiscoverController::class, 'discoverNetworkTrending'])->middleware($middleware);
        });

        Route::group(['prefix' => 'directory'], function () {
            Route::get('listing', [PixelfedDirectoryController::class, 'get']);
        });

        Route::group(['prefix' => 'auth'], function () {
            Route::get('iarpfc', [ApiV1Dot1Controller::class, 'inAppRegistrationPreFlightCheck']);
            Route::post('iar', [ApiV1Dot1Controller::class, 'inAppRegistration']);
            Route::post('iarc', [ApiV1Dot1Controller::class, 'inAppRegistrationConfirm']);
            Route::get('iarer', [ApiV1Dot1Controller::class, 'inAppRegistrationEmailRedirect']);

            Route::post('invite/admin/verify', [AdminInviteController::class, 'apiVerifyCheck'])->middleware('throttle:20,120');
            Route::post('invite/admin/uc', [AdminInviteController::class, 'apiUsernameCheck'])->middleware('throttle:20,120');
            Route::post('invite/admin/ec', [AdminInviteController::class, 'apiEmailCheck'])->middleware('throttle:10,1440');
        });

        Route::group(['prefix' => 'push'], function () use ($middleware) {
            Route::get('state', [ApiV1Dot1Controller::class, 'getPushState'])->middleware($middleware);
            Route::post('compare', [ApiV1Dot1Controller::class, 'comparePush'])->middleware($middleware);
            Route::post('update', [ApiV1Dot1Controller::class, 'updatePush'])->middleware($middleware);
            Route::post('disable', [ApiV1Dot1Controller::class, 'disablePush'])->middleware($middleware);
        });

        Route::post('status/create', [ApiV1Dot1Controller::class, 'statusCreate'])->middleware($middleware);
        Route::get('nag/state', [ApiV1Dot1Controller::class, 'nagState']);
    });

    Route::group(['prefix' => 'v1.2'], function () use ($middleware) {
        Route::group(['prefix' => 'stories'], function () use ($middleware) {
            Route::get('viewers', [StoryApiV1Controller::class, 'viewers'])->middleware($middleware);
            Route::post('publish', [StoryApiV1Controller::class, 'publishNext'])->middleware($middleware);
            Route::get('carousel', [StoryApiV1Controller::class, 'carouselNext'])->middleware($middleware);
            Route::get('mention-autocomplete', [StoryApiV1Controller::class, 'mentionAutocomplete'])->middleware($middleware);
        });
    });

    Route::group(['prefix' => 'live'], function () {
        // Route::post('create_stream', [LiveStreamController::class, 'createStream'])->middleware($middleware);
        // Route::post('stream/edit', [LiveStreamController::class, 'editStream'])->middleware($middleware);
        // Route::get('active/list', [LiveStreamController::class, 'getActiveStreams'])->middleware($middleware);
        // Route::get('accounts/stream', [LiveStreamController::class, 'getUserStream'])->middleware($middleware);
        // Route::get('accounts/stream/guest', [LiveStreamController::class, 'getUserStreamAsGuest']);
        // Route::delete('accounts/stream', [LiveStreamController::class, 'deleteStream'])->middleware($middleware);
        // Route::get('chat/latest', [LiveStreamController::class, 'getLatestChat'])->middleware($middleware);
        // Route::post('chat/message', [LiveStreamController::class, 'addChatComment'])->middleware($middleware);
        // Route::post('chat/delete', [LiveStreamController::class, 'deleteChatComment'])->middleware($middleware);
        // Route::post('chat/ban-user', [LiveStreamController::class, 'banChatUser'])->middleware($middleware);
        // Route::post('chat/pin', [LiveStreamController::class, 'pinChatComment'])->middleware($middleware);
        // Route::post('chat/unpin', [LiveStreamController::class, 'unpinChatComment'])->middleware($middleware);
        // Route::get('config', [LiveStreamController::class, 'getConfig']);
        // Route::post('broadcast/publish', [LiveStreamController::class, 'clientBroadcastPublish'])->middleware($middleware);
        // Route::post('broadcast/finish', [LiveStreamController::class, 'clientBroadcastFinish'])->middleware($middleware);
    });

    Route::group(['prefix' => 'admin'], function () use ($middleware) {
        Route::post('moderate/post/{id}', [ApiV1Dot1Controller::class, 'moderatePost'])->middleware($middleware);
        Route::get('supported', [AdminApiController::class, 'supported'])->middleware($middleware);
        Route::get('stats', [AdminApiController::class, 'getStats'])->middleware($middleware);

        Route::get('autospam/list', [AdminApiController::class, 'autospam'])->middleware($middleware);
        Route::post('autospam/handle', [AdminApiController::class, 'autospamHandle'])->middleware($middleware);
        Route::get('mod-reports/list', [AdminApiController::class, 'modReports'])->middleware($middleware);
        Route::post('mod-reports/handle', [AdminApiController::class, 'modReportHandle'])->middleware($middleware);
        Route::get('config', [AdminApiController::class, 'getConfiguration'])->middleware($middleware);
        Route::post('config/update', [AdminApiController::class, 'updateConfiguration'])->middleware($middleware);
        Route::get('users/list', [AdminApiController::class, 'getUsers'])->middleware($middleware);
        Route::get('users/get', [AdminApiController::class, 'getUser'])->middleware($middleware);
        Route::post('users/action', [AdminApiController::class, 'userAdminAction'])->middleware($middleware);
        Route::get('instances/list', [AdminApiController::class, 'instances'])->middleware($middleware);
        Route::get('instances/get', [AdminApiController::class, 'getInstance'])->middleware($middleware);
        Route::post('instances/moderate', [AdminApiController::class, 'moderateInstance'])->middleware($middleware);
        Route::post('instances/refresh-stats', [AdminApiController::class, 'refreshInstanceStats'])->middleware($middleware);
        Route::get('instance/stats', [AdminApiController::class, 'getAllStats'])->middleware($middleware);
    });

    Route::group(['prefix' => 'landing/v1'], function () {
        Route::get('directory', [LandingController::class, 'getDirectoryApi']);
    });

    Route::group(['prefix' => 'pixelfed'], function () use ($middleware) {
        Route::group(['prefix' => 'v1'], function () use ($middleware) {
            Route::post('report', [ApiV1Dot1Controller::class, 'report'])->middleware($middleware);

            Route::group(['prefix' => 'accounts'], function () use ($middleware) {
                Route::get('timelines/home', [ApiV1Controller::class, 'timelineHome'])->middleware($middleware);
                Route::delete('avatar', [ApiV1Dot1Controller::class, 'deleteAvatar'])->middleware($middleware);
                Route::get('{id}/posts', [ApiV1Dot1Controller::class, 'accountPosts'])->middleware($middleware);
                Route::post('change-password', [ApiV1Dot1Controller::class, 'accountChangePassword'])->middleware($middleware);
                Route::get('login-activity', [ApiV1Dot1Controller::class, 'accountLoginActivity'])->middleware($middleware);
                Route::get('two-factor', [ApiV1Dot1Controller::class, 'accountTwoFactor'])->middleware($middleware);
                Route::get('emails-from-pixelfed', [ApiV1Dot1Controller::class, 'accountEmailsFromPixelfed'])->middleware($middleware);
                Route::get('apps-and-applications', [ApiV1Dot1Controller::class, 'accountApps'])->middleware($middleware);
            });

            Route::group(['prefix' => 'archive'], function () use ($middleware) {
                Route::post('add/{id}', [ApiV1Dot1Controller::class, 'archive'])->middleware($middleware);
                Route::post('remove/{id}', [ApiV1Dot1Controller::class, 'unarchive'])->middleware($middleware);
                Route::get('list', [ApiV1Dot1Controller::class, 'archivedPosts'])->middleware($middleware);
            });

            Route::group(['prefix' => 'collections'], function () use ($middleware) {
                Route::get('accounts/{id}', [CollectionController::class, 'getUserCollections'])->middleware($middleware);
                Route::get('items/{id}', [CollectionController::class, 'getItems'])->middleware($middleware);
                Route::get('view/{id}', [CollectionController::class, 'getCollection'])->middleware($middleware);
                Route::post('add', [CollectionController::class, 'storeId'])->middleware($middleware);
                Route::post('update/{id}', [CollectionController::class, 'store'])->middleware($middleware);
                Route::delete('delete/{id}', [CollectionController::class, 'delete'])->middleware($middleware);
                Route::post('remove', [CollectionController::class, 'deleteId'])->middleware($middleware);
                Route::get('self', [CollectionController::class, 'getSelfCollections'])->middleware($middleware);
            });

            Route::group(['prefix' => 'compose'], function () use ($middleware) {
                Route::get('search/location', [ComposeController::class, 'searchLocation'])->middleware($middleware);
                Route::get('settings', [ComposeController::class, 'composeSettings'])->middleware($middleware);
            });

            Route::group(['prefix' => 'direct'], function () use ($middleware) {
                Route::get('thread', [DirectMessageController::class, 'thread'])->middleware($middleware);
                Route::post('thread/send', [DirectMessageController::class, 'create'])->middleware($middleware);
                Route::delete('thread/message', [DirectMessageController::class, 'delete'])->middleware($middleware);
                Route::post('thread/mute', [DirectMessageController::class, 'mute'])->middleware($middleware);
                Route::post('thread/unmute', [DirectMessageController::class, 'unmute'])->middleware($middleware);
                Route::post('thread/media', [DirectMessageController::class, 'mediaUpload'])->middleware($middleware);
                Route::post('thread/read', [DirectMessageController::class, 'read'])->middleware($middleware);
                Route::post('lookup', [DirectMessageController::class, 'composeLookup'])->middleware($middleware);
            });

            Route::group(['prefix' => 'discover'], function () use ($middleware) {
                Route::get('accounts/popular', [ApiV1Controller::class, 'discoverAccountsPopular'])->middleware($middleware);
                Route::get('posts/trending', [DiscoverController::class, 'trendingApi'])->middleware($middleware);
                Route::get('posts/hashtags', [DiscoverController::class, 'trendingHashtags'])->middleware($middleware);
            });

            Route::group(['prefix' => 'directory'], function () {
                Route::get('listing', [PixelfedDirectoryController::class, 'get']);
            });

            Route::group(['prefix' => 'places'], function () use ($middleware) {
                Route::get('posts/{id}/{slug}', [ApiV1Dot1Controller::class, 'placesById'])->middleware($middleware);
            });

            Route::get('web/settings', [ApiV1Dot1Controller::class, 'getWebSettings'])->middleware($middleware);
            Route::post('web/settings', [ApiV1Dot1Controller::class, 'setWebSettings'])->middleware($middleware);
            Route::get('app/settings', [UserAppSettingsController::class, 'get'])->middleware($middleware);
            Route::post('app/settings', [UserAppSettingsController::class, 'store'])->middleware($middleware);

            Route::group(['prefix' => 'stories'], function () use ($middleware) {
                Route::get('carousel', [StoryApiV1Controller::class, 'carousel'])->middleware($middleware);
                Route::get('self-carousel', [StoryApiV1Controller::class, 'selfCarousel'])->middleware($middleware);
                Route::post('add', [StoryApiV1Controller::class, 'add'])->middleware($middleware);
                Route::post('publish', [StoryApiV1Controller::class, 'publish'])->middleware($middleware);
                Route::post('seen', [StoryApiV1Controller::class, 'viewed'])->middleware($middleware);
                Route::post('self-expire/{id}', [StoryApiV1Controller::class, 'delete'])->middleware($middleware);
                Route::post('comment', [StoryApiV1Controller::class, 'comment'])->middleware($middleware);
                Route::get('viewers', [StoryApiV1Controller::class, 'viewers'])->middleware($middleware);
            });
        });
    });
});
