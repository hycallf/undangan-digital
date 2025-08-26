<?php
use App\Http\Controllers\CheckInController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web'])->group(function () {
    // Endpoint untuk scan QR (perlu auth untuk keamanan)
    Route::post('/check-in/scan', [CheckInController::class, 'processScan'])
        ->name('api.checkin.scan')
        ->middleware('auth');

    // Endpoint untuk upload photo
    Route::post('/check-in/upload-photo', [CheckInController::class, 'uploadPhoto'])
        ->name('api.checkin.upload')
        ->middleware('auth');
});


// Alternative: Jika masih ada masalah dengan auth, sementara disable auth untuk testing
/*
Route::middleware(['web'])->group(function () {
    Route::post('/check-in/scan', [CheckInController::class, 'processScan'])
        ->name('api.checkin.scan');

    Route::post('/check-in/upload-photo', [CheckInController::class, 'uploadPhoto'])
        ->name('api.checkin.upload');
});
*/
