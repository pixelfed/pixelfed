<?php

use Illuminate\Http\Request;

Route::post('/users/{username}/inbox', 'FederationController@userInbox');


Route::group(['prefix' => 'api'], function() {
	Route::group(['prefix' => 'v1'], function() {
		Route::post('apps', 'Api\ApiV1Controller@apps');
		Route::get('instance', 'Api\ApiV1Controller@instance');
		Route::get('statuses/{id}', 'Api\ApiV1Controller@statusById');
		Route::get('statuses/{id}/context', 'Api\ApiV1Controller@context');
	});
});
