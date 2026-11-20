<?php
use Illuminate\Support\Facades\Route;
use Modules\Shipment\Http\Controllers\Api\ShipmentApiController;

Route::middleware(['auth:sanctum'])->prefix('v1')->name('api.shipment.')->group(function () {
    Route::get('shipments', [ShipmentApiController::class,'index'])->name('index');
    Route::post('shipments', [ShipmentApiController::class,'store'])->name('store');
    Route::get('shipments/{shipment}', [ShipmentApiController::class,'show'])->name('show');
    Route::post('shipments/{shipment}/transition', [ShipmentApiController::class,'transition'])->name('transition');
    Route::post('shipments/{shipment}/allocate', [ShipmentApiController::class,'allocate'])->name('allocate');
    Route::get('shipments/{shipment}/readiness', [ShipmentApiController::class,'readiness'])->name('readiness');
    Route::post('shipments/{shipment}/handover', [ShipmentApiController::class,'handover'])->name('handover');
    Route::get('shipments/{shipment}/track', [ShipmentApiController::class,'track'])->name('track');
});
