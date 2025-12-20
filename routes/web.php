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
    Route::get('/smart-file-organization', 'smartFileOrganization');
});

// Blog Routes
Route::controller(\App\Http\Controllers\BlogController::class)->group(function () {
    Route::get('/blog', 'index')->name('blog.index');
    Route::get('/blog/{slug}', 'show')->name('blog.show');
});

Route::prefix('api/v1')->group(function () {
    // 📜 Text Sharing Routes
    Route::controller(ShareController::class)->middleware('throttle:save-text')->group(function () {
        Route::post('/text', 'saveText')->name('share.store.text');
        // Route::post('/download-zip', 'downloadZip')->name('share.download.zip');
        Route::post('/email-files', 'emailFiles')->name('share.email.files');
    });

    // 📁 Media File Routes (Independent from ShareText)
    Route::controller(\App\Http\Controllers\MediaController::class)->middleware('throttle:save-text')->group(function () {
        Route::post('/media', 'store')->name('media.store');
        Route::delete('/media/{uuid?}', 'destroy')->name('media.destroy');
        Route::delete('/media', 'destroyAll')->name('media.destroy.all');
        Route::post('/media/download-zip', 'downloadZip')->name('media.download');
    });

    // Routes without rate limiting
    Route::controller(ShareController::class)->group(function () {
        Route::get('/text', 'getText')->name('share.get.text');
        Route::get('/ip-info', 'getIpInfo')->name('share.ip.info');
    });

    // Media routes without rate limiting
    Route::controller(\App\Http\Controllers\MediaController::class)->group(function () {
        Route::get('/media', 'index')->name('media.index');
        Route::get('/media/ip-info', 'getIpInfo')->name('media.ip.info');
    });

    Route::controller(\App\Http\Controllers\FeedBackController::class)->group(function () {
        Route::post('/submit-feedback', 'store')->name('feedback.store');
    });
});

// One-time download route
Route::get('/download/{uuid}', [\App\Http\Controllers\MediaController::class, 'download'])->name('media.download.file');

// SEO Routes
Route::get('/sitemap.xml', function () {
    $sitemap = view('sitemap')->render();
    return response($sitemap, 200, [
        'Content-Type' => 'application/xml'
    ]);
})->name('sitemap');
