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

        // Executive Command Center — same aggregations as the web pages.
        Route::prefix('executive')->name('api.executive.')->middleware('can:executive.command.view')->group(function () {
            Route::get('dashboard', [\App\Http\Controllers\Api\ExecutiveApiController::class, 'dashboard'])->name('dashboard');
            Route::get('financial', [\App\Http\Controllers\Api\ExecutiveApiController::class, 'financial'])->name('financial');
            Route::get('branches', [\App\Http\Controllers\Api\ExecutiveApiController::class, 'branches'])->name('branches');
            Route::get('departments', [\App\Http\Controllers\Api\ExecutiveApiController::class, 'departments'])->name('departments');
            Route::get('sales', [\App\Http\Controllers\Api\ExecutiveApiController::class, 'sales'])->name('sales');
            Route::get('employees', [\App\Http\Controllers\Api\ExecutiveApiController::class, 'employees'])->name('employees');
            Route::get('tasks', [\App\Http\Controllers\Api\ExecutiveApiController::class, 'tasks'])->name('tasks');
            Route::get('tenders', [\App\Http\Controllers\Api\ExecutiveApiController::class, 'tenders'])->name('tenders');
            Route::get('activity', [\App\Http\Controllers\Api\ExecutiveApiController::class, 'activity'])->name('activity');
        });
    });
});
