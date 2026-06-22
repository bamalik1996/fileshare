<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\LimitsController;
use App\Http\Controllers\ShareController;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;

Route::controller(HomeController::class)->group(function () {
    Route::get('/', 'index');
    Route::get('/how-it-works', 'howItWorks');
    Route::get('/faq', 'faq');
    Route::get('/feedback', 'feedback');
    Route::get('/coming-soon', 'comingSoon');
    // Route::get('/smart-file-organization', 'smartFileOrganization');
    Route::get('/sitemap.xml', [\App\Http\Controllers\SitemapController::class, 'index']);
});

// Blog Routes
Route::controller(\App\Http\Controllers\BlogController::class)->group(function () {
    Route::get('/blog', 'index')->name('blog.index');
    Route::get('/blog/{slug}', 'show')->name('blog.show');
});

Route::prefix('api/v1')->middleware('reject.e2ee.keys')->group(function () {
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

    // Limits endpoint — returns per-principal upload and active-file limits
    // so the upload page can display the correct ceilings before the user
    // picks a file (Requirement 13.7). No authentication required: guest IP
    // users must be able to call this endpoint before they have created any
    // share. The ResolvePrincipal middleware (bound globally) determines
    // whether the caller is an IP, Account, or ApiKey principal and the
    // controller returns the appropriate tier of limits accordingly.
    Route::get('/limits', [LimitsController::class, 'index'])->name('limits.index');

    Route::get('/shares/{id}/state', [\App\Http\Controllers\ShareStateController::class, 'show'])
        ->name('shares.state');

    Route::post('/shares/{share}/verify-password', [\App\Http\Controllers\SharePasswordController::class, 'verifyShare'])
        ->middleware(['share.password.throttle'])
        ->name('shares.verify-password');

    Route::post('/rooms/{code}/verify-password', [\App\Http\Controllers\SharePasswordController::class, 'verifyRoom'])
        ->middleware(['share.password.throttle'])
        ->name('rooms.verify-password');

    Route::post('/share/password', [\App\Http\Controllers\SharePasswordController::class, 'update'])
        ->middleware('throttle:save-text')
        ->name('share.password.update');

    Route::prefix('rooms/{code}/clipboard')->controller(\App\Http\Controllers\RoomClipboardController::class)->group(function () {
        Route::get('/', 'show')->name('rooms.clipboard.show');
        Route::post('/', 'update')->name('rooms.clipboard.update');
        Route::post('/presence', 'presence')->name('rooms.clipboard.presence');
    });

    // Chunked / resumable upload endpoints (Requirement 9, task 14.2).
    Route::prefix('chunked-upload')->controller(\App\Http\Controllers\ChunkedUploadController::class)->group(function () {
        Route::post('/start', 'start')->name('chunked-upload.start');
        Route::post('/chunk', 'chunk')->name('chunked-upload.chunk');
        Route::get('/status/{sessionId}', 'status')->name('chunked-upload.status');
        Route::post('/complete', 'complete')->name('chunked-upload.complete');
    });
});

// One-time download route
Route::get('/download/{uuid}', [\App\Http\Controllers\MediaController::class, 'download'])->name('media.download.file');

// Rooms (Requirement 7).
//   POST /rooms     creates a new Room with optional expiry + password.
//   GET  /r/{code}  joins an existing Room by its 6-character code.
//
// The GET path is wrapped in `room.code.throttle` so a saturated per-IP
// bucket is short-circuited with HTTP 429 before any code lookup is
// performed (Requirement 7.8). The POST path is *not* throttled by this
// middleware: the bucket counts invalid *submissions*, not Room
// creations, and creating a Room is independent of attempting to join
// one. Standard route-level throttle:throttle:save-text could be
// applied later if Room creation needs its own ceiling.
Route::post('/rooms', [\App\Http\Controllers\RoomController::class, 'store'])
    ->name('room.store');

Route::get('/r/{code}', [\App\Http\Controllers\RoomController::class, 'show'])
    ->middleware('room.code.throttle')
    ->name('room.show');

// QR code endpoint for any active Share, resolved by uuid OR public_slug.
// `?download=1` returns the PNG as an attachment (Requirement 1.4); any
// other value renders inline. On generation failure the controller
// returns the URL-text fallback view (Requirement 1.5) without offering
// a download.
Route::get('/qr/{slug}', [\App\Http\Controllers\QrCodeController::class, 'show'])
    ->name('qr.show');

// Share view (owner / room recipient).
Route::get('/s/{share}', [\App\Http\Controllers\ShareViewController::class, 'show'])
    ->middleware(['share.password.throttle', 'share.password'])
    ->name('share.show');

// Public gallery (Requirement 17).
Route::get('/p/{slug}', [\App\Http\Controllers\PublicShareController::class, 'show'])
    ->middleware(['share.password.throttle', 'share.password:slug'])
    ->name('public.share.show');

// Account authentication (Requirement 16).
Route::controller(\App\Http\Controllers\AuthController::class)->group(function () {

    Route::middleware('guest:account')->group(function () {

        Route::get('/auth/register', 'showRegister')->name('auth.register');
        Route::post('/auth/register', 'register')
            ->middleware('throttle:5,1');

        Route::get('/auth/login', 'showLogin')->name('auth.login');
        Route::post('/auth/login', 'login')
            ->middleware('throttle:5,1');

        Route::get('/auth/forgot-password', 'showForgotPassword')->name('auth.forgot');
        Route::post('/auth/forgot-password', 'forgotPassword')
            ->middleware('throttle:3,1');

        Route::get('/auth/reset-password/{token}', 'showResetPassword')->name('password.reset');
        Route::post('/auth/reset-password', 'resetPassword')
            ->name('password.update')
            ->middleware('throttle:5,1');
    });

    Route::post('/auth/logout', 'logout')
        ->name('auth.logout')
        ->middleware('auth:account');
});

Route::middleware('auth:account')->group(function () {
    Route::get('/auth/email/verify', [\App\Http\Controllers\EmailVerificationController::class, 'notice'])
        ->name('verification.notice');
    Route::post('/auth/email/verification-notification', [\App\Http\Controllers\EmailVerificationController::class, 'send'])
        ->middleware('throttle:6,1')
        ->name('verification.send');
});

Route::get('/auth/email/verify/{id}/{hash}', [\App\Http\Controllers\EmailVerificationController::class, 'verify'])
    ->middleware('signed')
    ->name('verification.verify');

Route::middleware(['auth:account', 'account.verified'])->group(function () {
    Route::get('/account/shares', [\App\Http\Controllers\AccountController::class, 'shares'])
        ->name('account.shares');
    Route::delete('/account', [\App\Http\Controllers\AccountController::class, 'destroy'])
        ->name('account.destroy');
    Route::post('/account/shares/{share}/favourite', [\App\Http\Controllers\AccountController::class, 'favourite'])
        ->name('account.shares.favourite');
    Route::post('/account/shares/{share}/public/enable', [\App\Http\Controllers\AccountController::class, 'enablePublic'])
        ->name('account.shares.public.enable');
    Route::post('/account/shares/{share}/public/disable', [\App\Http\Controllers\AccountController::class, 'disablePublic'])
        ->name('account.shares.public.disable');
});

Route::get('/manifest.webmanifest', [\App\Http\Controllers\ManifestController::class, 'show'])
    ->name('manifest.show');
// Route::get('/docs/api', [\App\Http\Controllers\DocsController::class, 'show'])
//     ->name('docs.api');

require __DIR__ . '/api_v2.php';


// // SEO Routes
// Route::get('/sitemap.xml', function () {
//     $sitemap = view('sitemap')->render();
//     return response($sitemap, 200, [
//         'Content-Type' => 'application/xml'
//     ]);
// })->name('sitemap');
