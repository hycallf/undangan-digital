<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\CheckInController;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/admin/check-in/{event}', [CheckInController::class, 'scanner'])
    ->middleware(['auth', 'verified']) // Pastikan hanya user terotentikasi
    ->name('checkin.scanner');

// Halaman untuk menampilkan undangan
Route::get('/undangan/{event:slug}', [InvitationController::class, 'show'])->name('invitation.show');

// Endpoint untuk memproses form RSVP
Route::post('/undangan/{event:slug}/rsvp', [InvitationController::class, 'store'])->name('invitation.rsvp');

// Halaman sukses setelah RSVP, menampilkan QR Code
Route::get('/rsvp-success/{guest:qr_code_token}', [InvitationController::class, 'success'])->name('rsvp.success');
Route::get('/rsvp-success/{guest:qr_code_token}/print', [InvitationController::class, 'print'])->name('rsvp.print');

require __DIR__.'/auth.php';
