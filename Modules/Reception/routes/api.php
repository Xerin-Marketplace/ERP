<?php

use Illuminate\Support\Facades\Route;
use Modules\Reception\Http\Controllers\ReceptionController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('receptions', ReceptionController::class)->names('reception');
});
