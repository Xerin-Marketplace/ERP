<?php
use Illuminate\Support\Facades\Route;
use Modules\Arrival\Http\Controllers\ArrivalController;

Route::middleware(['web','auth'])->prefix('arrival')->name('arrival.')->group(function () {
    Route::get('dashboard', [ArrivalController::class,'dashboard'])->name('dashboard');
    Route::get('/', [ArrivalController::class,'index'])->name('index');
    Route::get('create', [ArrivalController::class,'create'])->name('create');
    Route::post('/', [ArrivalController::class,'store'])->name('store');
    Route::get('agents', [ArrivalController::class,'agents'])->name('agents');
    Route::post('agents', [ArrivalController::class,'storeAgent'])->name('agents.store');
    Route::get('batches', [ArrivalController::class,'batches'])->name('batches');
    Route::post('batches', [ArrivalController::class,'storeBatch'])->name('batches.store');
    Route::get('{arrival}', [ArrivalController::class,'show'])->name('show');
    Route::post('{arrival}/transition', [ArrivalController::class,'transition'])->name('transition');
    Route::post('{arrival}/reconcile', [ArrivalController::class,'reconcile'])->name('reconcile');
    Route::post('{arrival}/customs-case', [ArrivalController::class,'openCase'])->name('customs.open');
    Route::post('cases/{case}/transition', [ArrivalController::class,'caseTransition'])->name('cases.transition');
    Route::post('{arrival}/charge', [ArrivalController::class,'charge'])->name('charge');
    Route::post('{arrival}/collection', [ArrivalController::class,'requestCollection'])->name('collection.request');
    Route::post('collections/{collection}/approve', [ArrivalController::class,'approveCollection'])->name('collections.approve');
    Route::post('collections/{collection}/confirm', [ArrivalController::class,'confirmCollection'])->name('collections.confirm');
    Route::post('{arrival}/receive', [ArrivalController::class,'receive'])->name('receive');
});
