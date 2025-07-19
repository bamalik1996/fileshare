<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\ShareController;
use Illuminate\Support\Facades\Route;

Route::controller(HomeController::class)->group(function () {
    Route::get('/', 'index');
    Route::get('/about-us', 'aboutUs');
    Route::get('/feedback', 'feedback');
});

Route::prefix('api/v1')->group(function () {
    // 📜 Text Sharing Routes
    Route::controller(ShareController::class)->middleware('throttle:save-text')->group(function () {
        Route::post('/text', 'saveText')->name('share.store.text');
        Route::post('/media', 'saveMedia')->name('share.store.media');
        Route::delete('/media/{uuid?}', 'deleteMedia')->name('share.delete.media');
    });

    // Routes without rate limiting
    Route::controller(ShareController::class)->group(function () {
        Route::get('/text', 'getText')->name('share.get.text');
        Route::get('/ip-info', 'getIpInfo')->name('share.ip.info');
        Route::get('/share/get/media', 'getMedia')->name('share.get.media');
        Route::delete('/share/media',  'deleteMedia');
    });
});
