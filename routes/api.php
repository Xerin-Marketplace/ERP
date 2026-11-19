<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\HealthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| XERIN GROUP ERP — API v1
|--------------------------------------------------------------------------
|
| All API routes live under /api/v1 and follow the shared response contract
| defined in App\Support\ApiResponse.
|
*/

Route::prefix('v1')->middleware('throttle:api')->group(function () {

    Route::get('health', HealthController::class);

    Route::post('auth/token', [AuthController::class, 'token'])
        ->middleware('throttle:5,1')
        ->name('api.auth.token');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('auth/me', [AuthController::class, 'me'])->name('api.auth.me');
        Route::post('auth/revoke', [AuthController::class, 'revoke'])->name('api.auth.revoke');
    });
});
