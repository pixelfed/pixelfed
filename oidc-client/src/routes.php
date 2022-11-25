<?php

Route::prefix(config('oidc.provider_name'))->middleware('web')->group(function () {
    Route::get('/sign-in', 'GCS\OIDCClient\Controllers\OIDCController@signin')
        ->name('oidc.signin');
    Route::get('/sign-out', 'GCS\OIDCClient\Controllers\OIDCController@signout')
        ->name('oidc.signout');
    Route::get('/callback', 'GCS\OIDCClient\Controllers\OIDCController@callback')
        ->name('oidc.callback');
});