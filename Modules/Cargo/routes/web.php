<?php
use Illuminate\Support\Facades\Route;
use Modules\Cargo\Http\Controllers\CargoController as C;

Route::middleware(['auth', 'verified', 'account.active'])->prefix('cargo')->name('cargo.')->group(function () {
    Route::get('/dashboard', [C::class, 'dashboard'])->name('dashboard')->middleware('can:cargo.dashboard.view');

    Route::get('/pre-alerts', [C::class, 'preAlerts'])->name('pre-alerts.index')->middleware('can:cargo.pre_alerts.view');
    Route::post('/pre-alerts', [C::class, 'preAlertStore'])->name('pre-alerts.store')->middleware('can:cargo.pre_alerts.create');

    Route::get('/receiving', [C::class, 'receivings'])->name('receivings.index')->middleware('can:cargo.receiving.view');
    Route::post('/receiving', [C::class, 'receivingStart'])->name('receivings.start')->middleware('can:cargo.receiving.create');
    Route::get('/receiving/{receiving}', [C::class, 'receivingShow'])->name('receivings.show')->middleware('can:cargo.receiving.view');
    Route::post('/receiving/{receiving}/packages', [C::class, 'receivingPackages'])->name('receivings.packages')->middleware('can:cargo.receiving.create');
    Route::post('/receiving/{receiving}/confirm', [C::class, 'receivingConfirm'])->name('receivings.confirm')->middleware('can:cargo.receiving.confirm');

    Route::get('/packages', [C::class, 'packages'])->name('packages.index')->middleware('can:cargo.packages.view');
    Route::get('/packages/{package}', [C::class, 'packageShow'])->name('packages.show')->middleware('can:cargo.packages.view');
    Route::get('/packages/{package}/label', [C::class, 'packageLabel'])->name('packages.label')->middleware('can:cargo.packages.print_labels');
    Route::post('/packages/{package}/measure', [C::class, 'packageMeasure'])->name('packages.measure')->middleware('can:cargo.weighing.create');
    Route::post('/packages/{package}/inspect', [C::class, 'packageInspect'])->name('packages.inspect')->middleware('can:cargo.inspection.perform');
    Route::post('/packages/{package}/release', [C::class, 'packageRelease'])->name('packages.release')->middleware('can:cargo.inspection.approve');
    Route::post('/packages/{package}/identify', [C::class, 'packageIdentify'])->name('packages.identify')->middleware('can:cargo.receiving.manage_discrepancies');
    Route::post('/packages/{package}/move', [C::class, 'packageMove'])->name('packages.move')->middleware('can:warehouse.movements.create');
    Route::post('/packages/{package}/scan', [C::class, 'packageScan'])->name('packages.scan')->middleware('can:cargo.packages.scan');

    Route::get('/warehouses', [C::class, 'warehouses'])->name('warehouses.index')->middleware('can:warehouse.view');
    Route::post('/locations', [C::class, 'locationStore'])->name('locations.store')->middleware('can:warehouse.locations.manage');

    Route::get('/incidents', [C::class, 'incidents'])->name('incidents.index')->middleware('can:cargo.incidents.view');
    Route::post('/incidents/{incident}/resolve', [C::class, 'incidentResolve'])->name('incidents.resolve')->middleware('can:cargo.incidents.manage');
});
