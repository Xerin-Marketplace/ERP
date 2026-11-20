<?php
use Illuminate\Support\Facades\Route;
use Modules\Portal\Http\Controllers\Api\PortalApiController;

Route::prefix('v1')->middleware('throttle:20,1')->group(function () {
    Route::post('track', [PortalApiController::class, 'guestTrack'])->name('api.track');
});

Route::middleware(['auth:sanctum'])->prefix('v1/portal')->name('api.portal.')->group(function () {
    Route::get('dashboard', [PortalApiController::class,'dashboard'])->name('dashboard');
    Route::get('tracking', [PortalApiController::class,'tracking'])->name('tracking');
    Route::get('statement', [PortalApiController::class,'statement'])->name('statement');
    Route::post('tickets', [PortalApiController::class,'storeTicket'])->name('tickets.store');
    Route::post('claims', [PortalApiController::class,'storeClaim'])->name('claims.store');
});
