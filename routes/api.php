<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::post('/users/{username}/inbox', 'FederationController@userInbox');

Route::group(['prefix' => 'api/v2'], function() {
    Route::get('profile/{username}/status/{postid}', 'PublicApiController@status');
    Route::get('comments/{username}/status/{postId}', 'PublicApiController@statusComments');
});