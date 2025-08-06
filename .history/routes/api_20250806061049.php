<?php
use App\Http\Controllers\CheckInController;

Route::get('/admin/check-in/{event}', [CheckInController::class, 'scanner'])
    ->middleware(['auth', 'verified']) // Pastikan hanya user terotentikasi
    ->name('checkin.scanner');
