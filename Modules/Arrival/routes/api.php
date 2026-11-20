<?php
use Illuminate\Support\Facades\Route;
use Modules\Arrival\Http\Controllers\Api\ArrivalApiController;

Route::middleware(['auth:sanctum'])->prefix('v1')->name('api.arrival.')->group(function () {
    Route::get('arrivals', [ArrivalApiController::class,'index'])->name('index');
    Route::get('arrivals/{arrival}', [ArrivalApiController::class,'show'])->name('show');
    Route::post('arrivals/{arrival}/transition', [ArrivalApiController::class,'transition'])->name('transition');
    Route::post('arrivals/{arrival}/reconcile', [ArrivalApiController::class,'reconcile'])->name('reconcile');
    Route::get('batches/{batch}/handover', [ArrivalApiController::class,'handover'])->name('handover');
});
