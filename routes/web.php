<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\ShareController;
use Illuminate\Support\Facades\Route;

Route::controller(HomeController::class)->group(function () {
    Route::get('/', 'index');
    Route::get('/how-it-works', 'howItWorks');
    Route::get('/faq', 'faq');
    Route::get('/feedback', 'feedback');
    Route::get('/coming-soon', 'comingSoon');
});

Route::prefix('api/v1')->group(function () {
    // 📜 Text Sharing Routes
    Route::controller(ShareController::class)->middleware('throttle:save-text')->group(function () {
        Route::post('/text', 'saveText')->name('share.store.text');
        Route::post('/media', 'saveMedia')->name('share.store.media');
        Route::delete('/media/{uuid?}', 'deleteMedia')->name('share.delete.media');
        Route::post('/download-zip', 'downloadZip')->name('share.download.zip');
        Route::post('/email-files', 'emailFiles')->name('share.email.files');
    });

    // Routes without rate limiting
    Route::controller(ShareController::class)->group(function () {
        Route::get('/text', 'getText')->name('share.get.text');
        Route::get('/ip-info', 'getIpInfo')->name('share.ip.info');
        Route::get('/share/get/media', 'getMedia')->name('share.get.media');
        Route::delete('/share/media',  'deleteMedia');
    });
});

// SEO Routes
Route::get('/sitemap.xml', function () {
    $sitemap = view('sitemap')->render();
    return response($sitemap, 200, [
        'Content-Type' => 'application/xml'
    ]);
})->name('sitemap');