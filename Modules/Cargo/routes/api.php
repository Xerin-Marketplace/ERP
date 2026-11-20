<?php
use Illuminate\Support\Facades\Route;
use Modules\Cargo\Http\Controllers\Api\CargoApiController as A;

Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    Route::get('cargo/dashboard', [A::class, 'dashboard'])->middleware('can:cargo.dashboard.view');
    Route::get('cargo/pre-alerts', [A::class, 'preAlerts'])->middleware('can:cargo.pre_alerts.view');
    Route::post('cargo/pre-alerts', [A::class, 'preAlertStore'])->middleware('can:cargo.pre_alerts.create');
    Route::get('cargo/receiving', [A::class, 'receivings'])->middleware('can:cargo.receiving.view');
    Route::post('cargo/receiving', [A::class, 'receivingStart'])->middleware('can:cargo.receiving.create');
    Route::post('cargo/receiving/{receiving}/confirm', [A::class, 'receivingConfirm'])->middleware('can:cargo.receiving.confirm');
    Route::get('cargo/packages', [A::class, 'packages'])->middleware('can:cargo.packages.view');
    Route::get('cargo/packages/{package}', [A::class, 'packageShow'])->middleware('can:cargo.packages.view');
    Route::post('cargo/packages/{package}/scan', [A::class, 'packageScan'])->middleware('can:cargo.packages.scan');
    Route::post('cargo/packages/{package}/measurements', [A::class, 'packageMeasure'])->middleware('can:cargo.weighing.create');
    Route::post('cargo/packages/{package}/inspection', [A::class, 'packageInspect'])->middleware('can:cargo.inspection.perform');
    Route::post('cargo/packages/{package}/move', [A::class, 'packageMove'])->middleware('can:warehouse.movements.create');
    Route::get('cargo/incidents', [A::class, 'incidents'])->middleware('can:cargo.incidents.view');
});
