<?php

Route::domain(config('portfolio.domain'))->group(function () {
    Route::redirect('redirect/home', config('app.url'));
    Route::get('/', 'PortfolioController@index');
    Route::post('api/portfolio/self/curated.json', 'PortfolioController@storeCurated');
    Route::post('api/portfolio/self/settings.json', 'PortfolioController@getSettings');
    Route::get('api/portfolio/account/settings.json', 'PortfolioController@getAccountSettings');
    Route::post('api/portfolio/self/update-settings.json', 'PortfolioController@storeSettings');
    Route::get('api/portfolio/{username}/feed', 'PortfolioController@getFeed');

    Route::prefix(config('portfolio.path'))->group(function() {
        Route::get('/', 'PortfolioController@index');
        Route::get('settings', 'PortfolioController@settings')->name('portfolio.settings');
        Route::post('settings', 'PortfolioController@store');
        Route::get('{username}/{id}', 'PortfolioController@showPost');
        Route::get('{username}', 'PortfolioController@show');

        Route::fallback(function () {
            return view('errors.404');
        });
    });
});
