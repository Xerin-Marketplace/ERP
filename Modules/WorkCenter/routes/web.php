<?php
use Illuminate\Support\Facades\Route;
use Modules\WorkCenter\Http\Controllers\WorkCenterController;

Route::middleware(['web','auth','verified','account.active'])
    ->prefix('work')->name('work.')->group(function () {
        Route::get('dashboard', [WorkCenterController::class,'dashboard'])->name('dashboard');
        // reports
        Route::post('reports', [WorkCenterController::class,'createReport'])->name('reports.create')->middleware('permission:work.reports.create');
        Route::post('reports/{report}/content', [WorkCenterController::class,'saveContent'])->name('reports.content');
        Route::post('reports/{report}/upload', [WorkCenterController::class,'uploadVersion'])->name('reports.upload');
        Route::post('reports/{report}/submit', [WorkCenterController::class,'submitReport'])->name('reports.submit');
        Route::post('reports/{report}/review', [WorkCenterController::class,'reviewReport'])->name('reports.review')->middleware('permission:work.reports.review');
        Route::get('reports/{report}', [WorkCenterController::class,'showReport'])->name('reports.show');
        Route::get('reports/{report}/pdf/{version}', [WorkCenterController::class,'previewPdf'])->name('reports.pdf');
        // documents
        Route::post('documents', [WorkCenterController::class,'uploadDocument'])->name('docs.upload')->middleware('permission:work.documents.upload');
        Route::post('documents/{document}/versions', [WorkCenterController::class,'documentVersion'])->name('docs.version');
        Route::post('documents/{document}/share', [WorkCenterController::class,'shareDocument'])->name('docs.share');
        // tasks
        Route::get('tasks/mine', [WorkCenterController::class,'myTasks'])->name('tasks.mine');
        Route::post('tasks', [WorkCenterController::class,'assignTask'])->name('tasks.assign')->middleware('permission:work.tasks.assign');
        Route::post('tasks/{task}/move', [WorkCenterController::class,'taskMove'])->name('tasks.move');
        Route::post('tasks/{task}/progress', [WorkCenterController::class,'taskProgress'])->name('tasks.progress');
        Route::post('tasks/{task}/review', [WorkCenterController::class,'taskReview'])->name('tasks.review')->middleware('permission:work.tasks.review');
    });
