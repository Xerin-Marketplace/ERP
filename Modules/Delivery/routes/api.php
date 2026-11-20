<?php
use Illuminate\Support\Facades\Route;
use Modules\Delivery\Http\Controllers\Api\DeliveryApiController;

Route::middleware(['auth:sanctum'])->prefix('v1')->name('api.delivery.')->group(function () {
    Route::get('orders', [DeliveryApiController::class,'index'])->name('index');
    Route::get('orders/{order}', [DeliveryApiController::class,'show'])->name('show');
    Route::post('orders/{order}/transition', [DeliveryApiController::class,'transition'])->name('transition');
    Route::post('orders/{order}/confirm', [DeliveryApiController::class,'confirmDelivery'])->name('confirm');
    Route::get('orders/{order}/track', [DeliveryApiController::class,'track'])->name('track');
    Route::post('drivers/{driver}/availability', [DeliveryApiController::class,'driverAvailability'])->name('driver.availability');
    Route::post('drivers/{driver}/location', [DeliveryApiController::class,'driverLocation'])->name('driver.location');
    Route::post('assignments/{assignment}/respond', [DeliveryApiController::class,'driverRespond'])->name('assignments.respond');
});
