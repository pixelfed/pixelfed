<?php

use Illuminate\Http\Request;

Route::post('/users/{username}/inbox', 'FederationController@userInbox');

Route::group(['prefix' => 'api'], function() {
	Route::group(['prefix' => 'v1'], function() {
		Route::post('apps', 'Api\ApiV1Controller@apps');
		Route::get('instance', 'Api\ApiV1Controller@instance');
		
		Route::get('accounts/verify_credentials', 'Api\ApiV1Controller@verifyCredentials')->middleware('auth:api');
		Route::patch('accounts/update_credentials', 'Api\ApiV1Controller@accountUpdateCredentials')->middleware('auth:api');
		Route::get('accounts/relationships', 'Api\ApiV1Controller@accountRelationshipsById')->middleware('auth:api');
		Route::get('accounts/search', 'Api\ApiV1Controller@accountSearch')->middleware('auth:api');
		Route::get('accounts/{id}/statuses', 'Api\ApiV1Controller@accountStatusesById')->middleware('auth:api');
		Route::get('accounts/{id}/following', 'Api\ApiV1Controller@accountFollowingById')->middleware('auth:api');
		Route::get('accounts/{id}/followers', 'Api\ApiV1Controller@accountFollowersById')->middleware('auth:api');
		Route::post('accounts/{id}/follow', 'Api\ApiV1Controller@accountFollowById')->middleware('auth:api');
		Route::post('accounts/{id}/unfollow', 'Api\ApiV1Controller@accountUnfollowById')->middleware('auth:api');
		Route::post('accounts/{id}/block', 'Api\ApiV1Controller@accountBlockById')->middleware('auth:api');
		Route::post('accounts/{id}/unblock', 'Api\ApiV1Controller@accountUnblockById')->middleware('auth:api');
		Route::post('accounts/{id}/pin', 'Api\ApiV1Controller@accountEndorsements')->middleware('auth:api');
		Route::post('accounts/{id}/unpin', 'Api\ApiV1Controller@accountEndorsements')->middleware('auth:api');
		Route::post('accounts/{id}/mute', 'Api\ApiV1Controller@accountMuteById')->middleware('auth:api');
		Route::post('accounts/{id}/unmute', 'Api\ApiV1Controller@accountUnmuteById')->middleware('auth:api');
		Route::get('accounts/{id}/lists', 'Api\ApiV1Controller@accountListsById')->middleware('auth:api');
		Route::get('lists/{id}/accounts', 'Api\ApiV1Controller@accountListsById')->middleware('auth:api');
		Route::get('accounts/{id}', 'Api\ApiV1Controller@accountById')->middleware('auth:api');

		Route::post('avatar/update', 'ApiController@avatarUpdate')->middleware('auth:api');
		Route::get('blocks', 'Api\ApiV1Controller@accountBlocks')->middleware('auth:api');
		Route::get('conversations', 'Api\ApiV1Controller@conversations')->middleware('auth:api');
		Route::get('custom_emojis', 'Api\ApiV1Controller@customEmojis');
		Route::get('domain_blocks', 'Api\ApiV1Controller@accountDomainBlocks')->middleware('auth:api');
		Route::post('domain_blocks', 'Api\ApiV1Controller@accountDomainBlocks')->middleware('auth:api');
		Route::delete('domain_blocks', 'Api\ApiV1Controller@accountDomainBlocks')->middleware('auth:api');
		Route::get('endorsements', 'Api\ApiV1Controller@accountEndorsements')->middleware('auth:api');
		Route::get('favourites', 'Api\ApiV1Controller@accountFavourites')->middleware('auth:api');
		Route::get('filters', 'Api\ApiV1Controller@accountFilters')->middleware('auth:api');
		Route::get('follow_requests', 'Api\ApiV1Controller@accountFollowRequests')->middleware('auth:api');
		Route::post('follow_requests/{id}/authorize', 'Api\ApiV1Controller@accountFollowRequestAccept')->middleware('auth:api');
		Route::post('follow_requests/{id}/reject', 'Api\ApiV1Controller@accountFollowRequestReject')->middleware('auth:api');
		Route::get('lists', 'Api\ApiV1Controller@accountLists')->middleware('auth:api');
		Route::post('media', 'Api\ApiV1Controller@mediaUpload')->middleware('auth:api');
		Route::put('media/{id}', 'Api\ApiV1Controller@mediaUpdate')->middleware('auth:api');
		Route::get('mutes', 'Api\ApiV1Controller@accountMutes')->middleware('auth:api');
		Route::get('notifications', 'Api\ApiV1Controller@accountNotifications')->middleware('auth:api');
		Route::get('suggestions', 'Api\ApiV1Controller@accountSuggestions')->middleware('auth:api');

		Route::post('statuses/{id}/favourite', 'Api\ApiV1Controller@statusFavouriteById')->middleware('auth:api');
		Route::post('statuses/{id}/unfavourite', 'Api\ApiV1Controller@statusUnfavouriteById')->middleware('auth:api');
		Route::get('statuses/{id}/context', 'Api\ApiV1Controller@statusContext')->middleware('auth:api');
		Route::get('statuses/{id}/card', 'Api\ApiV1Controller@statusCard')->middleware('auth:api');
		Route::get('statuses/{id}/reblogged_by', 'Api\ApiV1Controller@statusRebloggedBy')->middleware('auth:api');
		Route::get('statuses/{id}/favourited_by', 'Api\ApiV1Controller@statusFavouritedBy')->middleware('auth:api');
		Route::post('statuses/{id}/reblog', 'Api\ApiV1Controller@statusShare')->middleware('auth:api');
		Route::post('statuses/{id}/unreblog', 'Api\ApiV1Controller@statusUnshare')->middleware('auth:api');
		Route::delete('statuses/{id}', 'Api\ApiV1Controller@statusDelete')->middleware('auth:api');
		Route::get('statuses/{id}', 'Api\ApiV1Controller@statusById')->middleware('auth:api');
		Route::post('statuses', 'Api\ApiV1Controller@statusCreate')->middleware('auth:api');


		Route::get('timelines/home', 'Api\ApiV1Controller@timelineHome')->middleware('auth:api');
		Route::get('timelines/public', 'Api\ApiV1Controller@timelinePublic');
		Route::get('timelines/tag/{hashtag}', 'Api\ApiV1Controller@timelineHashtag')->middleware('auth:api');
	});
});
