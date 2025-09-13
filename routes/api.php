<?php

use App\Http\Controllers\Api\V1\TagsController;

$middleware = ['auth:api', 'validemail'];

Route::post('/f/inbox', 'FederationController@sharedInbox');
Route::post('/users/{username}/inbox', 'FederationController@userInbox');
Route::get('i/actor', 'InstanceActorController@profile');
Route::post('i/actor/inbox', 'InstanceActorController@inbox');
Route::get('i/actor/outbox', 'InstanceActorController@outbox');
Route::get('/stories/{username}/{id}', 'StoryController@getActivityObject');

Route::get('.well-known/webfinger', 'FederationController@webfinger')->name('well-known.webfinger');
Route::get('.well-known/nodeinfo', 'FederationController@nodeinfoWellKnown')->name('well-known.nodeinfo');
Route::get('.well-known/host-meta', 'FederationController@hostMeta')->name('well-known.hostMeta');
Route::redirect('.well-known/change-password', '/settings/password');
Route::get('api/nodeinfo/2.0.json', 'FederationController@nodeinfo');
Route::get('api/service/health-check', 'HealthCheckController@get');
Route::post('api/auth/app-code-verify', 'AppRegisterController@verifyCode')->middleware('throttle:app-code-verify');
Route::post('api/auth/onboarding', 'AppRegisterController@onboarding')->middleware('throttle:app-code-verify');
Route::get('storage/m/_v2/{pid}/{mhash}/{uhash}/{f}', 'MediaController@fallbackRedirect');

Route::prefix('api/v0/groups')->middleware($middleware)->group(function () {
    Route::get('config', 'Groups\GroupsApiController@getConfig');
    Route::post('permission/create', 'Groups\CreateGroupsController@checkCreatePermission');
    Route::post('create', 'Groups\CreateGroupsController@storeGroup');

    Route::post('search/invite/friends/send', 'Groups\GroupsSearchController@inviteFriendsToGroup');
    Route::post('search/invite/friends', 'Groups\GroupsSearchController@searchFriendsToInvite');
    Route::post('search/global', 'Groups\GroupsSearchController@searchGlobalResults');
    Route::post('search/lac', 'Groups\GroupsSearchController@searchLocalAutocomplete');
    Route::post('search/addrec', 'Groups\GroupsSearchController@searchAddRecent');
    Route::get('search/getrec', 'Groups\GroupsSearchController@searchGetRecent');
    Route::get('comments', 'Groups\GroupsCommentController@getComments');
    Route::post('comment', 'Groups\GroupsCommentController@storeComment');
    Route::post('comment/photo', 'Groups\GroupsCommentController@storeCommentPhoto');
    Route::post('comment/delete', 'Groups\GroupsCommentController@deleteComment');
    Route::get('discover/popular', 'Groups\GroupsDiscoverController@getDiscoverPopular');
    Route::get('discover/new', 'Groups\GroupsDiscoverController@getDiscoverNew');
    Route::post('delete', 'Groups\GroupsMetaController@deleteGroup');
    Route::post('status/new', 'Groups\GroupsPostController@storePost');
    Route::post('status/delete', 'Groups\GroupsPostController@deletePost');
    Route::post('status/like', 'Groups\GroupsPostController@likePost');
    Route::post('status/unlike', 'Groups\GroupsPostController@unlikePost');
    Route::get('topics/list', 'Groups\GroupsTopicController@groupTopics');
    Route::get('topics/tag', 'Groups\GroupsTopicController@groupTopicTag');
    Route::get('accounts/{gid}/{pid}', 'Groups\GroupsApiController@getGroupAccount');
    Route::get('categories/list', 'Groups\GroupsApiController@getGroupCategories');
    Route::get('category/list', 'Groups\GroupsApiController@getGroupsByCategory');
    Route::get('self/recommended/list', 'Groups\GroupsApiController@getRecommendedGroups');
    Route::get('self/list', 'Groups\GroupsApiController@getSelfGroups');
    Route::get('media/list', 'Groups\GroupsPostController@getGroupMedia');
    Route::get('members/list', 'Groups\GroupsMemberController@getGroupMembers');
    Route::get('members/requests', 'Groups\GroupsMemberController@getGroupMemberJoinRequests');
    Route::post('members/request', 'Groups\GroupsMemberController@handleGroupMemberJoinRequest');
    Route::get('members/get', 'Groups\GroupsMemberController@getGroupMember');
    Route::get('member/intersect/common', 'Groups\GroupsMemberController@getGroupMemberCommonIntersections');
    Route::get('status', 'Groups\GroupsPostController@getStatus');
    Route::post('like', 'GroupController@likePost');
    Route::post('comment/like', 'Groups\GroupsCommentController@likePost');
    Route::post('comment/unlike', 'Groups\GroupsCommentController@unlikePost');
    Route::get('self/feed', 'Groups\GroupsFeedController@getSelfFeed');
    Route::get('self/notifications', 'Groups\GroupsNotificationsController@selfGlobalNotifications');
    Route::get('{id}/user/{pid}/feed', 'Groups\GroupsFeedController@getGroupProfileFeed');
    Route::get('{id}/feed', 'Groups\GroupsFeedController@getGroupFeed');
    Route::get('{id}/atabs', 'Groups\GroupsAdminController@getAdminTabs');
    Route::get('{id}/admin/interactions', 'Groups\GroupsAdminController@getInteractionLogs');
    Route::get('{id}/admin/blocks', 'Groups\GroupsAdminController@getBlocks');
    Route::post('{id}/admin/blocks/add', 'Groups\GroupsAdminController@addBlock');
    Route::post('{id}/admin/blocks/undo', 'Groups\GroupsAdminController@undoBlock');
    Route::post('{id}/admin/blocks/export', 'Groups\GroupsAdminController@exportBlocks');
    Route::get('{id}/reports/list', 'Groups\GroupsAdminController@getReportList');

    Route::get('{id}/members/interaction-limits', 'GroupController@getMemberInteractionLimits');
    Route::post('{id}/invite/check', 'GroupController@groupMemberInviteCheck');
    Route::post('{id}/invite/accept', 'GroupController@groupMemberInviteAccept');
    Route::post('{id}/invite/decline', 'GroupController@groupMemberInviteDecline');
    Route::post('{id}/members/interaction-limits', 'GroupController@updateMemberInteractionLimits');
    Route::post('{id}/report/action', 'GroupController@reportAction');
    Route::post('{id}/report/create', 'GroupController@reportCreate');
    Route::post('{id}/admin/mbs', 'GroupController@metaBlockSearch');
    Route::post('{id}/join', 'GroupController@joinGroup');
    Route::post('{id}/cjr', 'GroupController@cancelJoinRequest');
    Route::post('{id}/leave', 'GroupController@groupLeave');
    Route::post('{id}/settings', 'GroupController@updateGroup');
    Route::get('{id}/likes/{sid}', 'GroupController@showStatusLikes');
    Route::get('{id}', 'GroupController@getGroup');
});

Route::group(['prefix' => 'api'], function () use ($middleware) {

    Route::group(['prefix' => 'v1'], function () use ($middleware) {
        Route::post('apps', 'Api\ApiV1Controller@apps');
        Route::get('apps/verify_credentials', 'Api\ApiV1Controller@getApp')->middleware($middleware);
        Route::get('instance', 'Api\ApiV1Controller@instance');
        Route::get('instance/peers', 'Api\ApiV1Controller@instancePeers');
        Route::get('bookmarks', 'Api\ApiV1Controller@bookmarks')->middleware($middleware);

        Route::get('accounts/verify_credentials', 'Api\ApiV1Controller@verifyCredentials')->middleware($middleware);
        Route::match(['post', 'patch'], 'accounts/update_credentials', 'Api\ApiV1Controller@accountUpdateCredentials')->middleware($middleware);
        Route::get('accounts/relationships', 'Api\ApiV1Controller@accountRelationshipsById')->middleware($middleware);
        Route::get('accounts/search', 'Api\ApiV1Controller@accountSearch')->middleware($middleware);
        Route::get('accounts/{id}/statuses', 'Api\ApiV1Controller@accountStatusesById')->middleware($middleware);
        Route::get('accounts/{id}/following', 'Api\ApiV1Controller@accountFollowingById')->middleware($middleware);
        Route::get('accounts/{id}/followers', 'Api\ApiV1Controller@accountFollowersById')->middleware($middleware);
        Route::post('accounts/{id}/follow', 'Api\ApiV1Controller@accountFollowById')->middleware($middleware);
        Route::post('accounts/{id}/unfollow', 'Api\ApiV1Controller@accountUnfollowById')->middleware($middleware);
        Route::post('accounts/{id}/block', 'Api\ApiV1Controller@accountBlockById')->middleware($middleware);
        Route::post('accounts/{id}/unblock', 'Api\ApiV1Controller@accountUnblockById')->middleware($middleware);
        Route::post('accounts/{id}/remove_from_followers', 'Api\ApiV1Controller@accountRemoveFollowById')->middleware($middleware);
        Route::post('accounts/{id}/pin', 'Api\ApiV1Controller@accountEndorsements')->middleware($middleware);
        Route::post('accounts/{id}/unpin', 'Api\ApiV1Controller@accountEndorsements')->middleware($middleware);
        Route::post('accounts/{id}/mute', 'Api\ApiV1Controller@accountMuteById')->middleware($middleware);
        Route::post('accounts/{id}/unmute', 'Api\ApiV1Controller@accountUnmuteById')->middleware($middleware);
        Route::get('accounts/{id}/lists', 'Api\ApiV1Controller@accountListsById')->middleware($middleware);
        Route::get('lists/{id}/accounts', 'Api\ApiV1Controller@accountListsById')->middleware($middleware);
        Route::get('accounts/{id}', 'Api\ApiV1Controller@accountById')->middleware($middleware);

        Route::post('avatar/update', 'ApiController@avatarUpdate')->middleware($middleware);
        Route::get('blocks', 'Api\ApiV1Controller@accountBlocks')->middleware($middleware);
        Route::get('conversations', 'Api\ApiV1Controller@conversations')->middleware($middleware);
        Route::get('custom_emojis', 'Api\ApiV1Controller@customEmojis');
        Route::get('domain_blocks', 'Api\V1\DomainBlockController@index')->middleware($middleware);
        Route::post('domain_blocks', 'Api\V1\DomainBlockController@store')->middleware($middleware);
        Route::delete('domain_blocks', 'Api\V1\DomainBlockController@delete')->middleware($middleware);
        Route::get('endorsements', 'Api\ApiV1Controller@accountEndorsements')->middleware($middleware);
        Route::get('favourites', 'Api\ApiV1Controller@accountFavourites')->middleware($middleware);
        Route::get('filters', 'Api\ApiV1Controller@accountFilters')->middleware($middleware);
        Route::get('follow_requests', 'Api\ApiV1Controller@accountFollowRequests')->middleware($middleware);
        Route::post('follow_requests/{id}/authorize', 'Api\ApiV1Controller@accountFollowRequestAccept')->middleware($middleware);
        Route::post('follow_requests/{id}/reject', 'Api\ApiV1Controller@accountFollowRequestReject')->middleware($middleware);
        Route::get('lists', 'Api\ApiV1Controller@accountLists')->middleware($middleware);
        Route::post('media', 'Api\ApiV1Controller@mediaUpload')->middleware($middleware);
        Route::get('media/{id}', 'Api\ApiV1Controller@mediaGet')->middleware($middleware);
        Route::put('media/{id}', 'Api\ApiV1Controller@mediaUpdate')->middleware($middleware);
        Route::get('mutes', 'Api\ApiV1Controller@accountMutes')->middleware($middleware);
        Route::get('notifications', 'Api\ApiV1Controller@accountNotifications')->middleware($middleware);
        Route::get('suggestions', 'Api\ApiV1Controller@accountSuggestions')->middleware($middleware);

        Route::post('statuses/{id}/favourite', 'Api\ApiV1Controller@statusFavouriteById')->middleware($middleware);
        Route::post('statuses/{id}/unfavourite', 'Api\ApiV1Controller@statusUnfavouriteById')->middleware($middleware);
        Route::get('statuses/{id}/context', 'Api\ApiV1Controller@statusContext')->middleware($middleware);
        Route::get('statuses/{id}/card', 'Api\ApiV1Controller@statusCard')->middleware($middleware);
        Route::get('statuses/{id}/reblogged_by', 'Api\ApiV1Controller@statusRebloggedBy')->middleware($middleware);
        Route::get('statuses/{id}/favourited_by', 'Api\ApiV1Controller@statusFavouritedBy')->middleware($middleware);
        Route::post('statuses/{id}/reblog', 'Api\ApiV1Controller@statusShare')->middleware($middleware);
        Route::post('statuses/{id}/unreblog', 'Api\ApiV1Controller@statusUnshare')->middleware($middleware);
        Route::post('statuses/{id}/bookmark', 'Api\ApiV1Controller@bookmarkStatus')->middleware($middleware);
        Route::post('statuses/{id}/unbookmark', 'Api\ApiV1Controller@unbookmarkStatus')->middleware($middleware);
        Route::post('statuses/{id}/pin', 'Api\ApiV1Controller@statusPin')->middleware($middleware);
        Route::post('statuses/{id}/unpin', 'Api\ApiV1Controller@statusUnpin')->middleware($middleware);
        Route::delete('statuses/{id}', 'Api\ApiV1Controller@statusDelete')->middleware($middleware);
        Route::get('statuses/{id}', 'Api\ApiV1Controller@statusById')->middleware($middleware);
        Route::post('statuses', 'Api\ApiV1Controller@statusCreate')->middleware($middleware);

        Route::get('timelines/home', 'Api\ApiV1Controller@timelineHome')->middleware($middleware);
        Route::get('timelines/public', 'Api\ApiV1Controller@timelinePublic')->middleware($middleware);
        Route::get('timelines/tag/{hashtag}', 'Api\ApiV1Controller@timelineHashtag')->middleware($middleware);
        Route::get('discover/posts', 'Api\ApiV1Controller@discoverPosts')->middleware($middleware);

        Route::get('preferences', 'Api\ApiV1Controller@getPreferences')->middleware($middleware);
        Route::get('trends', 'Api\ApiV1Controller@getTrends')->middleware($middleware);
        Route::get('announcements', 'Api\ApiV1Controller@getAnnouncements')->middleware($middleware);
        Route::get('markers', 'Api\ApiV1Controller@getMarkers')->middleware($middleware);
        Route::post('markers', 'Api\ApiV1Controller@setMarkers')->middleware($middleware);

        Route::get('followed_tags', [TagsController::class, 'getFollowedTags'])->middleware($middleware);
        Route::post('tags/{id}/follow', [TagsController::class, 'followHashtag'])->middleware($middleware);
        Route::post('tags/{id}/unfollow', [TagsController::class, 'unfollowHashtag'])->middleware($middleware);
        Route::get('tags/{id}/related', [TagsController::class, 'relatedTags'])->middleware($middleware);
        Route::get('tags/{id}', [TagsController::class, 'getHashtag'])->middleware($middleware);

        Route::get('statuses/{id}/history', 'StatusEditController@history')->middleware($middleware);
        Route::put('statuses/{id}', 'StatusEditController@store')->middleware($middleware);

        Route::group(['prefix' => 'admin'], function () use ($middleware) {
            Route::get('domain_blocks', 'Api\V1\Admin\DomainBlocksController@index')->middleware($middleware);
            Route::post('domain_blocks', 'Api\V1\Admin\DomainBlocksController@create')->middleware($middleware);
            Route::get('domain_blocks/{id}', 'Api\V1\Admin\DomainBlocksController@show')->middleware($middleware);
            Route::put('domain_blocks/{id}', 'Api\V1\Admin\DomainBlocksController@update')->middleware($middleware);
            Route::delete('domain_blocks/{id}', 'Api\V1\Admin\DomainBlocksController@delete')->middleware($middleware);
        })->middleware($middleware);
    });

    Route::group(['prefix' => 'v2'], function () use ($middleware) {
        Route::get('search', 'Api\ApiV2Controller@search')->middleware($middleware);
        Route::post('media', 'Api\ApiV2Controller@mediaUploadV2')->middleware($middleware);
        Route::get('streaming/config', 'Api\ApiV2Controller@getWebsocketConfig');
        Route::get('instance', 'Api\ApiV2Controller@instance');

        Route::get('filters', 'CustomFilterController@index')->middleware($middleware);
        Route::get('filters/{id}', 'CustomFilterController@show')->middleware($middleware);
        Route::post('filters', 'CustomFilterController@store')->middleware($middleware);
        Route::put('filters/{id}', 'CustomFilterController@update')->middleware($middleware);
        Route::delete('filters/{id}', 'CustomFilterController@delete')->middleware($middleware);
    });

    Route::group(['prefix' => 'v1.1'], function () use ($middleware) {
        Route::post('report', 'Api\ApiV1Dot1Controller@report')->middleware($middleware);

        Route::group(['prefix' => 'accounts'], function () use ($middleware) {
            Route::get('timelines/home', 'Api\ApiV1Controller@timelineHome')->middleware($middleware);
            Route::delete('avatar', 'Api\ApiV1Dot1Controller@deleteAvatar')->middleware($middleware);
            Route::get('{id}/posts', 'Api\ApiV1Dot1Controller@accountPosts')->middleware($middleware);
            Route::post('change-password', 'Api\ApiV1Dot1Controller@accountChangePassword')->middleware($middleware);
            Route::get('login-activity', 'Api\ApiV1Dot1Controller@accountLoginActivity')->middleware($middleware);
            Route::get('two-factor', 'Api\ApiV1Dot1Controller@accountTwoFactor')->middleware($middleware);
            Route::get('emails-from-pixelfed', 'Api\ApiV1Dot1Controller@accountEmailsFromPixelfed')->middleware($middleware);
            Route::get('apps-and-applications', 'Api\ApiV1Dot1Controller@accountApps')->middleware($middleware);
            Route::get('mutuals/{id}', 'Api\ApiV1Dot1Controller@getMutualAccounts')->middleware($middleware);
            Route::get('username/{username}', 'Api\ApiV1Dot1Controller@accountUsernameToId')->middleware($middleware);
        });

        Route::group(['prefix' => 'collections'], function () use ($middleware) {
            Route::get('accounts/{id}', 'CollectionController@getUserCollections')->middleware($middleware);
            Route::get('items/{id}', 'CollectionController@getItems')->middleware($middleware);
            Route::get('view/{id}', 'CollectionController@getCollection')->middleware($middleware);
            Route::post('add', 'CollectionController@storeId')->middleware($middleware);
            Route::post('update/{id}', 'CollectionController@store')->middleware($middleware);
            Route::delete('delete/{id}', 'CollectionController@delete')->middleware($middleware);
            Route::post('remove', 'CollectionController@deleteId')->middleware($middleware);
            Route::get('self', 'CollectionController@getSelfCollections')->middleware($middleware);
        });

        Route::group(['prefix' => 'direct'], function () use ($middleware) {
            Route::get('thread', 'DirectMessageController@thread')->middleware($middleware);
            Route::post('thread/send', 'DirectMessageController@create')->middleware($middleware);
            Route::delete('thread/message', 'DirectMessageController@delete')->middleware($middleware);
            Route::post('thread/mute', 'DirectMessageController@mute')->middleware($middleware);
            Route::post('thread/unmute', 'DirectMessageController@unmute')->middleware($middleware);
            Route::post('thread/media', 'DirectMessageController@mediaUpload')->middleware($middleware);
            Route::post('thread/read', 'DirectMessageController@read')->middleware($middleware);
            Route::post('lookup', 'DirectMessageController@composeLookup')->middleware($middleware);
            Route::get('compose/mutuals', 'DirectMessageController@composeMutuals')->middleware($middleware);
        });

        Route::group(['prefix' => 'archive'], function () use ($middleware) {
            Route::post('add/{id}', 'Api\ApiV1Dot1Controller@archive')->middleware($middleware);
            Route::post('remove/{id}', 'Api\ApiV1Dot1Controller@unarchive')->middleware($middleware);
            Route::get('list', 'Api\ApiV1Dot1Controller@archivedPosts')->middleware($middleware);
        });

        Route::group(['prefix' => 'places'], function () use ($middleware) {
            Route::get('posts/{id}/{slug}', 'Api\ApiV1Dot1Controller@placesById')->middleware($middleware);
        });

        Route::group(['prefix' => 'stories'], function () use ($middleware) {
            Route::get('carousel', 'Stories\StoryApiV1Controller@carousel')->middleware($middleware);
            Route::post('add', 'Stories\StoryApiV1Controller@add')->middleware($middleware);
            Route::post('publish', 'Stories\StoryApiV1Controller@publish')->middleware($middleware);
            Route::post('seen', 'Stories\StoryApiV1Controller@viewed')->middleware($middleware);
            Route::post('self-expire/{id}', 'Stories\StoryApiV1Controller@delete')->middleware($middleware);
            Route::post('comment', 'Stories\StoryApiV1Controller@comment')->middleware($middleware);
        });

        Route::group(['prefix' => 'compose'], function () use ($middleware) {
            Route::get('search/location', 'ComposeController@searchLocation')->middleware($middleware);
            Route::get('settings', 'ComposeController@composeSettings')->middleware($middleware);
        });

        Route::group(['prefix' => 'discover'], function () use ($middleware) {
            Route::get('accounts/popular', 'Api\ApiV1Controller@discoverAccountsPopular')->middleware($middleware);
            Route::get('posts/trending', 'DiscoverController@trendingApi')->middleware($middleware);
            Route::get('posts/hashtags', 'DiscoverController@trendingHashtags')->middleware($middleware);
            Route::get('posts/network/trending', 'DiscoverController@discoverNetworkTrending')->middleware($middleware);
        });

        Route::group(['prefix' => 'directory'], function () {
            Route::get('listing', 'PixelfedDirectoryController@get');
        });

        Route::group(['prefix' => 'auth'], function () {
            Route::get('iarpfc', 'Api\ApiV1Dot1Controller@inAppRegistrationPreFlightCheck');
            Route::post('iar', 'Api\ApiV1Dot1Controller@inAppRegistration');
            Route::post('iarc', 'Api\ApiV1Dot1Controller@inAppRegistrationConfirm');
            Route::get('iarer', 'Api\ApiV1Dot1Controller@inAppRegistrationEmailRedirect');

            Route::post('invite/admin/verify', 'AdminInviteController@apiVerifyCheck')->middleware('throttle:20,120');
            Route::post('invite/admin/uc', 'AdminInviteController@apiUsernameCheck')->middleware('throttle:20,120');
            Route::post('invite/admin/ec', 'AdminInviteController@apiEmailCheck')->middleware('throttle:10,1440');
        });

        Route::group(['prefix' => 'push'], function () use ($middleware) {
            Route::get('state', 'Api\ApiV1Dot1Controller@getPushState')->middleware($middleware);
            Route::post('compare', 'Api\ApiV1Dot1Controller@comparePush')->middleware($middleware);
            Route::post('update', 'Api\ApiV1Dot1Controller@updatePush')->middleware($middleware);
            Route::post('disable', 'Api\ApiV1Dot1Controller@disablePush')->middleware($middleware);
        });

        Route::post('status/create', 'Api\ApiV1Dot1Controller@statusCreate')->middleware($middleware);
        Route::get('nag/state', 'Api\ApiV1Dot1Controller@nagState');
    });

    Route::group(['prefix' => 'v1.2'], function () use ($middleware) {
        Route::group(['prefix' => 'stories'], function () use ($middleware) {
            Route::get('viewers', 'Stories\StoryApiV1Controller@viewers')->middleware($middleware);
            Route::post('publish', 'Stories\StoryApiV1Controller@publishNext')->middleware($middleware);
            Route::get('carousel', 'Stories\StoryApiV1Controller@carouselNext')->middleware($middleware);
            Route::get('mention-autocomplete', 'Stories\StoryApiV1Controller@mentionAutocomplete')->middleware($middleware);
        });
    });

    Route::group(['prefix' => 'live'], function () {
        // Route::post('create_stream', 'LiveStreamController@createStream')->middleware($middleware);
        // Route::post('stream/edit', 'LiveStreamController@editStream')->middleware($middleware);
        // Route::get('active/list', 'LiveStreamController@getActiveStreams')->middleware($middleware);
        // Route::get('accounts/stream', 'LiveStreamController@getUserStream')->middleware($middleware);
        // Route::get('accounts/stream/guest', 'LiveStreamController@getUserStreamAsGuest');
        // Route::delete('accounts/stream', 'LiveStreamController@deleteStream')->middleware($middleware);
        // Route::get('chat/latest', 'LiveStreamController@getLatestChat')->middleware($middleware);
        // Route::post('chat/message', 'LiveStreamController@addChatComment')->middleware($middleware);
        // Route::post('chat/delete', 'LiveStreamController@deleteChatComment')->middleware($middleware);
        // Route::post('chat/ban-user', 'LiveStreamController@banChatUser')->middleware($middleware);
        // Route::post('chat/pin', 'LiveStreamController@pinChatComment')->middleware($middleware);
        // Route::post('chat/unpin', 'LiveStreamController@unpinChatComment')->middleware($middleware);
        // Route::get('config', 'LiveStreamController@getConfig');
        // Route::post('broadcast/publish', 'LiveStreamController@clientBroadcastPublish')->middleware($middleware);
        // Route::post('broadcast/finish', 'LiveStreamController@clientBroadcastFinish')->middleware($middleware);
    });

    Route::group(['prefix' => 'admin'], function () use ($middleware) {
        Route::post('moderate/post/{id}', 'Api\ApiV1Dot1Controller@moderatePost')->middleware($middleware);
        Route::get('supported', 'Api\AdminApiController@supported')->middleware($middleware);
        Route::get('stats', 'Api\AdminApiController@getStats')->middleware($middleware);

        Route::get('autospam/list', 'Api\AdminApiController@autospam')->middleware($middleware);
        Route::post('autospam/handle', 'Api\AdminApiController@autospamHandle')->middleware($middleware);
        Route::get('mod-reports/list', 'Api\AdminApiController@modReports')->middleware($middleware);
        Route::post('mod-reports/handle', 'Api\AdminApiController@modReportHandle')->middleware($middleware);
        Route::get('config', 'Api\AdminApiController@getConfiguration')->middleware($middleware);
        Route::post('config/update', 'Api\AdminApiController@updateConfiguration')->middleware($middleware);
        Route::get('users/list', 'Api\AdminApiController@getUsers')->middleware($middleware);
        Route::get('users/get', 'Api\AdminApiController@getUser')->middleware($middleware);
        Route::post('users/action', 'Api\AdminApiController@userAdminAction')->middleware($middleware);
        Route::get('instances/list', 'Api\AdminApiController@instances')->middleware($middleware);
        Route::get('instances/get', 'Api\AdminApiController@getInstance')->middleware($middleware);
        Route::post('instances/moderate', 'Api\AdminApiController@moderateInstance')->middleware($middleware);
        Route::post('instances/refresh-stats', 'Api\AdminApiController@refreshInstanceStats')->middleware($middleware);
        Route::get('instance/stats', 'Api\AdminApiController@getAllStats')->middleware($middleware);
    });

    Route::group(['prefix' => 'landing/v1'], function () {
        Route::get('directory', 'LandingController@getDirectoryApi');
    });

    Route::group(['prefix' => 'pixelfed'], function () use ($middleware) {
        Route::group(['prefix' => 'v1'], function () use ($middleware) {
            Route::post('report', 'Api\ApiV1Dot1Controller@report')->middleware($middleware);

            Route::group(['prefix' => 'accounts'], function () use ($middleware) {
                Route::get('timelines/home', 'Api\ApiV1Controller@timelineHome')->middleware($middleware);
                Route::delete('avatar', 'Api\ApiV1Dot1Controller@deleteAvatar')->middleware($middleware);
                Route::get('{id}/posts', 'Api\ApiV1Dot1Controller@accountPosts')->middleware($middleware);
                Route::post('change-password', 'Api\ApiV1Dot1Controller@accountChangePassword')->middleware($middleware);
                Route::get('login-activity', 'Api\ApiV1Dot1Controller@accountLoginActivity')->middleware($middleware);
                Route::get('two-factor', 'Api\ApiV1Dot1Controller@accountTwoFactor')->middleware($middleware);
                Route::get('emails-from-pixelfed', 'Api\ApiV1Dot1Controller@accountEmailsFromPixelfed')->middleware($middleware);
                Route::get('apps-and-applications', 'Api\ApiV1Dot1Controller@accountApps')->middleware($middleware);
            });

            Route::group(['prefix' => 'archive'], function () use ($middleware) {
                Route::post('add/{id}', 'Api\ApiV1Dot1Controller@archive')->middleware($middleware);
                Route::post('remove/{id}', 'Api\ApiV1Dot1Controller@unarchive')->middleware($middleware);
                Route::get('list', 'Api\ApiV1Dot1Controller@archivedPosts')->middleware($middleware);
            });

            Route::group(['prefix' => 'collections'], function () use ($middleware) {
                Route::get('accounts/{id}', 'CollectionController@getUserCollections')->middleware($middleware);
                Route::get('items/{id}', 'CollectionController@getItems')->middleware($middleware);
                Route::get('view/{id}', 'CollectionController@getCollection')->middleware($middleware);
                Route::post('add', 'CollectionController@storeId')->middleware($middleware);
                Route::post('update/{id}', 'CollectionController@store')->middleware($middleware);
                Route::delete('delete/{id}', 'CollectionController@delete')->middleware($middleware);
                Route::post('remove', 'CollectionController@deleteId')->middleware($middleware);
                Route::get('self', 'CollectionController@getSelfCollections')->middleware($middleware);
            });

            Route::group(['prefix' => 'compose'], function () use ($middleware) {
                Route::get('search/location', 'ComposeController@searchLocation')->middleware($middleware);
                Route::get('settings', 'ComposeController@composeSettings')->middleware($middleware);
            });

            Route::group(['prefix' => 'direct'], function () use ($middleware) {
                Route::get('thread', 'DirectMessageController@thread')->middleware($middleware);
                Route::post('thread/send', 'DirectMessageController@create')->middleware($middleware);
                Route::delete('thread/message', 'DirectMessageController@delete')->middleware($middleware);
                Route::post('thread/mute', 'DirectMessageController@mute')->middleware($middleware);
                Route::post('thread/unmute', 'DirectMessageController@unmute')->middleware($middleware);
                Route::post('thread/media', 'DirectMessageController@mediaUpload')->middleware($middleware);
                Route::post('thread/read', 'DirectMessageController@read')->middleware($middleware);
                Route::post('lookup', 'DirectMessageController@composeLookup')->middleware($middleware);
            });

            Route::group(['prefix' => 'discover'], function () use ($middleware) {
                Route::get('accounts/popular', 'Api\ApiV1Controller@discoverAccountsPopular')->middleware($middleware);
                Route::get('posts/trending', 'DiscoverController@trendingApi')->middleware($middleware);
                Route::get('posts/hashtags', 'DiscoverController@trendingHashtags')->middleware($middleware);
            });

            Route::group(['prefix' => 'directory'], function () {
                Route::get('listing', 'PixelfedDirectoryController@get');
            });

            Route::group(['prefix' => 'places'], function () use ($middleware) {
                Route::get('posts/{id}/{slug}', 'Api\ApiV1Dot1Controller@placesById')->middleware($middleware);
            });

            Route::get('web/settings', 'Api\ApiV1Dot1Controller@getWebSettings')->middleware($middleware);
            Route::post('web/settings', 'Api\ApiV1Dot1Controller@setWebSettings')->middleware($middleware);
            Route::get('app/settings', 'UserAppSettingsController@get')->middleware($middleware);
            Route::post('app/settings', 'UserAppSettingsController@store')->middleware($middleware);

            Route::group(['prefix' => 'stories'], function () use ($middleware) {
                Route::get('carousel', 'Stories\StoryApiV1Controller@carousel')->middleware($middleware);
                Route::get('self-carousel', 'Stories\StoryApiV1Controller@selfCarousel')->middleware($middleware);
                Route::post('add', 'Stories\StoryApiV1Controller@add')->middleware($middleware);
                Route::post('publish', 'Stories\StoryApiV1Controller@publish')->middleware($middleware);
                Route::post('seen', 'Stories\StoryApiV1Controller@viewed')->middleware($middleware);
                Route::post('self-expire/{id}', 'Stories\StoryApiV1Controller@delete')->middleware($middleware);
                Route::post('comment', 'Stories\StoryApiV1Controller@comment')->middleware($middleware);
                Route::get('viewers', 'Stories\StoryApiV1Controller@viewers')->middleware($middleware);
            });
        });
    });
});
