<?php

use Illuminate\Support\Facades\Route;
use Modules\Reception\Http\Controllers\Api\V1\AccessApiController;
use Modules\Reception\Http\Controllers\ReceptionController;

// Public access-request intake + tracking — throttled.
Route::prefix('v1')->middleware('throttle:30,1')->group(function () {
    Route::post('access-requests', [AccessApiController::class, 'store'])->name('api.access-requests.store');
    Route::get('access-requests/track', [AccessApiController::class, 'track'])->name('api.access-requests.track');
});

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('receptions', ReceptionController::class)->names('reception');

    Route::get('access-requests', [AccessApiController::class, 'index'])->name('api.access-requests.index')
        ->middleware('can:reception.access.view');
    Route::get('access-requests/{accessRequest}', [AccessApiController::class, 'show'])->name('api.access-requests.show')
        ->middleware('can:reception.access.view');
    Route::post('access-requests/{accessRequest}/route', [AccessApiController::class, 'route'])->name('api.access-requests.route')
        ->middleware('can:reception.access.route');
    Route::post('access-requests/{accessRequest}/reject', [AccessApiController::class, 'reject'])->name('api.access-requests.reject')
        ->middleware('can:reception.access.route');
    Route::post('access-approval-tasks/{task}/decide', [AccessApiController::class, 'decide'])->name('api.access-tasks.decide');
    Route::post('access-requests/{accessRequest}/provision', [AccessApiController::class, 'provision'])->name('api.access-requests.provision')
        ->middleware('can:reception.access.provision');
});
