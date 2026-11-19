<?php

use Illuminate\Support\Facades\Route;
use Modules\Core\Http\Controllers\CoreController;

Route::middleware(['auth:sanctum', 'throttle:api'])->prefix('v1/core')->group(function () {
    Route::get('status', [CoreController::class, 'status'])->name('core.status');
});
