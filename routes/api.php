<?php

use Illuminate\Http\Request;

$middleware = ['auth:api','validemail'];

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

Route::group(['prefix' => 'api'], function() use($middleware) {

	Route::group(['prefix' => 'v1'], function() use($middleware) {
		Route::post('apps', 'Api\ApiV1Controller@apps');
		Route::get('apps/verify_credentials', 'Api\ApiV1Controller@getApp')->middleware($middleware);
		Route::get('instance', 'Api\ApiV1Controller@instance');
		Route::get('bookmarks', 'Api\ApiV1Controller@bookmarks')->middleware($middleware);

		Route::get('accounts/verify_credentials', 'Api\ApiV1Controller@verifyCredentials')->middleware($middleware);
		Route::patch('accounts/update_credentials', 'Api\ApiV1Controller@accountUpdateCredentials')->middleware($middleware);
		Route::get('accounts/relationships', 'Api\ApiV1Controller@accountRelationshipsById')->middleware($middleware);
		Route::get('accounts/search', 'Api\ApiV1Controller@accountSearch')->middleware($middleware);
		Route::get('accounts/{id}/statuses', 'Api\ApiV1Controller@accountStatusesById')->middleware($middleware);
		Route::get('accounts/{id}/following', 'Api\ApiV1Controller@accountFollowingById')->middleware($middleware);
		Route::get('accounts/{id}/followers', 'Api\ApiV1Controller@accountFollowersById')->middleware($middleware);
		Route::post('accounts/{id}/follow', 'Api\ApiV1Controller@accountFollowById')->middleware($middleware);
		Route::post('accounts/{id}/unfollow', 'Api\ApiV1Controller@accountUnfollowById')->middleware($middleware);
		Route::post('accounts/{id}/block', 'Api\ApiV1Controller@accountBlockById')->middleware($middleware);
		Route::post('accounts/{id}/unblock', 'Api\ApiV1Controller@accountUnblockById')->middleware($middleware);
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
		Route::get('domain_blocks', 'Api\ApiV1Controller@accountDomainBlocks')->middleware($middleware);
		Route::post('domain_blocks', 'Api\ApiV1Controller@accountDomainBlocks')->middleware($middleware);
		Route::delete('domain_blocks', 'Api\ApiV1Controller@accountDomainBlocks')->middleware($middleware);
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
		Route::delete('statuses/{id}', 'Api\ApiV1Controller@statusDelete')->middleware($middleware);
		Route::get('statuses/{id}', 'Api\ApiV1Controller@statusById')->middleware($middleware);
		Route::post('statuses', 'Api\ApiV1Controller@statusCreate')->middleware($middleware);

		Route::get('timelines/home', 'Api\ApiV1Controller@timelineHome')->middleware($middleware);
		Route::get('timelines/public', 'Api\ApiV1Controller@timelinePublic')->middleware($middleware);
		Route::get('timelines/tag/{hashtag}', 'Api\ApiV1Controller@timelineHashtag');
		Route::get('discover/posts', 'Api\ApiV1Controller@discoverPosts')->middleware($middleware);

		Route::get('preferences', 'Api\ApiV1Controller@getPreferences')->middleware($middleware);
		Route::get('trends', 'Api\ApiV1Controller@getTrends')->middleware($middleware);
		Route::get('announcements', 'Api\ApiV1Controller@getAnnouncements')->middleware($middleware);
		Route::get('markers', 'Api\ApiV1Controller@getMarkers')->middleware($middleware);
		Route::post('markers', 'Api\ApiV1Controller@setMarkers')->middleware($middleware);
	});

	Route::group(['prefix' => 'v2'], function() use($middleware) {
		Route::get('search', 'Api\ApiV1Controller@searchV2')->middleware($middleware);
		Route::post('media', 'Api\ApiV1Controller@mediaUploadV2')->middleware($middleware);
	});

	Route::group(['prefix' => 'live'], function() use($middleware) {
		Route::post('create_stream', 'LiveStreamController@createStream')->middleware($middleware);
		Route::post('stream/edit', 'LiveStreamController@editStream')->middleware($middleware);
		Route::get('active/list', 'LiveStreamController@getActiveStreams')->middleware($middleware);
		Route::get('accounts/stream', 'LiveStreamController@getUserStream')->middleware($middleware);
		Route::delete('accounts/stream', 'LiveStreamController@deleteStream')->middleware($middleware);
		Route::get('chat/latest', 'LiveStreamController@getLatestChat')->middleware($middleware);
		Route::post('chat/message', 'LiveStreamController@addChatComment')->middleware($middleware);
		Route::post('chat/delete', 'LiveStreamController@deleteChatComment')->middleware($middleware);
		Route::get('config', 'LiveStreamController@getConfig')->middleware($middleware);
		Route::post('broadcast/publish', 'LiveStreamController@clientBroadcastPublish');
		Route::post('broadcast/finish', 'LiveStreamController@clientBroadcastFinish');
	});
});
