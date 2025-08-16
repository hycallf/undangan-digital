<?php
use App\Http\Controllers\CheckInController;

Route::middleware('auth:sanctum')->group(function () {
    // Endpoint ini hanya bisa diakses oleh user yang sudah login
    Route::post('/check-in/scan', [CheckInController::class, 'processScan'])->name('api.checkin.scan');
    Route::post('/check-in/upload-photo', [CheckInController::class, 'uploadPhoto'])->name('api.checkin.upload');
});
