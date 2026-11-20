<?php
use Illuminate\Support\Facades\Route;
use Modules\Delivery\Http\Controllers\DeliveryController;

Route::middleware(['web','auth'])->prefix('delivery')->name('delivery.')->group(function () {
    Route::get('dashboard', [DeliveryController::class,'dashboard'])->name('dashboard');
    Route::get('/', [DeliveryController::class,'index'])->name('index');
    Route::get('create', [DeliveryController::class,'create'])->name('create');
    Route::post('/', [DeliveryController::class,'store'])->name('store');
    Route::get('drivers', [DeliveryController::class,'drivers'])->name('drivers');
    Route::post('drivers', [DeliveryController::class,'storeDriver'])->name('drivers.store');
    Route::post('drivers/{driver}/verify', [DeliveryController::class,'verifyDriver'])->name('drivers.verify');
    Route::get('vehicles', [DeliveryController::class,'vehicles'])->name('vehicles');
    Route::post('vehicles', [DeliveryController::class,'storeVehicle'])->name('vehicles.store');
    Route::get('services', [DeliveryController::class,'services'])->name('services');
    Route::post('services', [DeliveryController::class,'storeService'])->name('services.store');
    Route::post('zones', [DeliveryController::class,'storeZone'])->name('zones.store');
    Route::get('{order}', [DeliveryController::class,'show'])->name('show');
    Route::post('{order}/transition', [DeliveryController::class,'transition'])->name('transition');
    Route::post('{order}/assign', [DeliveryController::class,'assign'])->name('assign');
    Route::post('{order}/auto-assign', [DeliveryController::class,'autoAssign'])->name('auto-assign');
    Route::post('{order}/fail', [DeliveryController::class,'fail'])->name('fail');
    Route::post('{order}/reschedule', [DeliveryController::class,'reschedule'])->name('reschedule');
    Route::post('{order}/return', [DeliveryController::class,'initiateReturn'])->name('return');
    Route::post('returns/{return}/receive', [DeliveryController::class,'receiveReturn'])->name('returns.receive');
    Route::post('{order}/cod', [DeliveryController::class,'cod'])->name('cod');
    Route::post('cod/{cod}/reconcile', [DeliveryController::class,'reconcileCod'])->name('cod.reconcile');
});
