<?php

use Illuminate\Http\Request;

Route::post('/users/{username}/inbox', 'FederationController@userInbox');


Route::group(['prefix' => 'api'], function() {
	Route::group(['prefix' => 'v1'], function() {
		Route::post('apps', 'Api\ApiV1Controller@apps');

		Route::get('accounts/verify_credentials', 'ApiController@verifyCredentials');
		Route::get('accounts/relationships', 'PublicApiController@relationships');
		Route::get('accounts/{id}/statuses', 'PublicApiController@accountStatuses');
		Route::get('accounts/{id}/following', 'PublicApiController@accountFollowing');
		Route::get('accounts/{id}/followers', 'PublicApiController@accountFollowers');
		Route::get('accounts/{id}', 'Api\ApiV1Controller@accountById');

		Route::get('instance', 'Api\ApiV1Controller@instance');
	});
});
