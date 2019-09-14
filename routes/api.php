<?php

use Illuminate\Http\Request;

Route::post('/users/{username}/inbox', 'FederationController@userInbox');

Route::post('/api/v1/apps', 'Api\ApiV1Controller@apps');
