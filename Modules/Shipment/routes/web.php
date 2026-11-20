<?php
use Illuminate\Support\Facades\Route;
use Modules\Shipment\Http\Controllers\{ShipmentController, AirlineController};

Route::middleware(['web','auth'])->prefix('shipment')->name('shipment.')->group(function () {
    Route::get('dashboard', [ShipmentController::class,'dashboard'])->name('dashboard');
    Route::get('/', [ShipmentController::class,'index'])->name('index');
    Route::get('create', [ShipmentController::class,'create'])->name('create');
    Route::post('/', [ShipmentController::class,'store'])->name('store');
    Route::get('exceptions', [ShipmentController::class,'exceptions'])->name('exceptions');
    Route::get('airlines', [AirlineController::class,'index'])->name('airlines');
    Route::post('airlines', [AirlineController::class,'store'])->name('airlines.store');
    Route::get('flights', [AirlineController::class,'flights'])->name('flights');
    Route::post('flights', [AirlineController::class,'storeFlight'])->name('flights.store');
    Route::post('flights/{flight}/event', [AirlineController::class,'flightEvent'])->name('flights.event');
    Route::get('{shipment}', [ShipmentController::class,'show'])->name('show');
    Route::post('{shipment}/transition', [ShipmentController::class,'transition'])->name('transition');
    Route::post('{shipment}/allocate', [ShipmentController::class,'allocate'])->name('allocate');
    Route::delete('{shipment}/packages/{package}', [ShipmentController::class,'unallocate'])->name('unallocate');
    Route::post('{shipment}/split', [ShipmentController::class,'split'])->name('split');
    Route::post('{shipment}/book', [ShipmentController::class,'book'])->name('book');
    Route::post('bookings/{booking}/confirm', [ShipmentController::class,'confirmBooking'])->name('bookings.confirm');
    Route::post('{shipment}/awb', [ShipmentController::class,'awb'])->name('awb');
    Route::post('awbs/{awb}/issue', [ShipmentController::class,'issueAwb'])->name('awbs.issue');
    Route::post('{shipment}/manifest', [ShipmentController::class,'manifest'])->name('manifest');
    Route::post('manifests/{manifest}/reconcile', [ShipmentController::class,'reconcile'])->name('manifests.reconcile');
    Route::post('manifests/{manifest}/approve', [ShipmentController::class,'approveManifest'])->name('manifests.approve');
    Route::post('{shipment}/dispatch', [ShipmentController::class,'dispatch'])->name('dispatch');
    Route::post('{shipment}/handover', [ShipmentController::class,'handover'])->name('handover');
});
