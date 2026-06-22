<?php

use App\Http\Controllers\Api\V2\ApiKeyController;
use App\Http\Controllers\Api\V2\ShareApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v2')->group(function () {
    Route::middleware(['auth:account', 'account.verified'])->group(function () {
        Route::post('api-keys', [ApiKeyController::class, 'store']);
        Route::delete('api-keys/{id}', [ApiKeyController::class, 'destroy']);
    });

    Route::middleware(['api.key.auth', 'reject.e2ee.keys', 'throttle:api-v2'])->group(function () {
        Route::get('shares', [ShareApiController::class, 'index']);
        Route::post('shares', [ShareApiController::class, 'store']);
        Route::get('shares/{share}', [ShareApiController::class, 'show']);
    });
});
