<?php
use Illuminate\Support\Facades\Route;
use Modules\Sales\Http\Controllers\SalesApiController;

Route::prefix('v1/sales')->middleware(['auth:sanctum'])->group(function () {
    Route::get('dashboard', [SalesApiController::class,'dashboard']);
    Route::get('forecast', [SalesApiController::class,'forecast']);
    Route::get('analytics', [SalesApiController::class,'analytics']);
    Route::get('opportunities', [SalesApiController::class,'opportunities']);
    Route::post('opportunities', [SalesApiController::class,'storeOpportunity']);
    Route::post('opportunities/{opportunity}/move', [SalesApiController::class,'moveOpportunity']);
    Route::post('simulate', [SalesApiController::class,'simulate']);
    Route::get('partners/{partner}/performance', [SalesApiController::class,'partnerPerformance']);
});
