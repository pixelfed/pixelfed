<?php

use App\Http\Controllers\Api\v2026\Admin\ConfigCache as AdminConfigCacheController;
use Illuminate\Support\Facades\Route;

$middleware = ['auth:sanctum,api'];

Route::prefix('api')->group(function () use ($middleware) {
    Route::prefix('v2026')->group(function () use ($middleware) {
        Route::prefix('admin')->group(function () use ($middleware) {
            Route::get('config', [AdminConfigCacheController::class, 'index'])->middleware($middleware);
            Route::post('config', [AdminConfigCacheController::class, 'store'])->middleware($middleware);
            Route::get('config/{key}', [AdminConfigCacheController::class, 'show'])->where('key', '.*')->middleware($middleware);
            Route::post('config/{key}', [AdminConfigCacheController::class, 'update'])->where('key', '.*')->middleware($middleware);
        });
    });
});
