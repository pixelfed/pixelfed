<?php


use App\Http\Controllers\PortfolioController;
Route::domain(config('portfolio.domain'))->group(function () {
    Route::redirect('redirect/home', config('app.url'));
    Route::get('/', [PortfolioController::class, 'index']);
    Route::post('api/portfolio/self/curated.json', [PortfolioController::class, 'storeCurated']);
    Route::post('api/portfolio/self/settings.json', [PortfolioController::class, 'getSettings']);
    Route::get('api/portfolio/account/settings.json', [PortfolioController::class, 'getAccountSettings']);
    Route::post('api/portfolio/self/update-settings.json', [PortfolioController::class, 'storeSettings']);
    Route::get('api/portfolio/{username}/feed', [PortfolioController::class, 'getFeed']);

    Route::prefix(config('portfolio.path'))->group(function() {
        Route::get('/', [PortfolioController::class, 'index']);
        Route::get('settings', [PortfolioController::class, 'settings'])->name('portfolio.settings');
        Route::post('settings', [PortfolioController::class, 'store']);
        Route::get('{username}/{id}', [PortfolioController::class, 'showPost']);
        Route::get('{username}', [PortfolioController::class, 'show']);

        Route::fallback(function () {
            return view('errors.404');
        });
    });
});
