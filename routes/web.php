<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\CheckInController;
// use App\Http\Controllers\EventController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\UsersController;
use App\Http\Controllers\Admin\EventController;
use App\Http\Controllers\Admin\GuestDashboardController;
use App\Http\Controllers\ScannerController;
use App\Http\Controllers\Receptionist\DashboardController as ReceptionistDashboardController;


Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});


Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    // Route untuk halaman dashboard utama admin
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    Route::post('events/{event}', [EventController::class, 'update'])->name('events.update');
    Route::get('/events/{event}/guests', [GuestDashboardController::class, 'show'])
         ->name('events.guests.show');
    Route::resource('events', EventController::class)->middleware('reception.access');


    Route::get('/events/{event}/guests/export/{type}', [GuestDashboardController::class, 'export'])->name('events.guests.export');
    Route::post('/events/{event}/guests/import', [GuestDashboardController::class, 'import'])->name('events.guests.import');
});

Route::middleware(['auth', 'verified', 'reception.access'])->group(function () {
    Route::get('/dashboard', [ReceptionistDashboardController::class, 'index'])->name('dashboard');

    Route::get('events/{event}/guests', [GuestDashboardController::class, 'show'])->name('events.guests.show');

    Route::get('/events', [EventController::class, 'Index'])->name('events.index');

    Route::get('/check-in/{event}', [CheckInController::class, 'scanner'])->name('checkin.scanner');
});

// Halaman utama untuk menampilkan undangan
Route::get('/undangan/{event:slug}', [InvitationController::class, 'show'])->name('invitation.show');

// Endpoint untuk memproses form RSVP & Ucapan
Route::post('/undangan/{event:slug}/rsvp', [InvitationController::class, 'store'])->name('invitation.rsvp.store');

// routes/web.php
Route::get('/undangan/{event:slug}/save-calendar', [InvitationController::class, 'saveToCalendar'])->name('invitation.save-calendar');

// Halaman sukses yang menampilkan QR Code
Route::get('/sukses/{guest:qr_code_token}', [InvitationController::class, 'success'])->name('invitation.success');

// Halaman untuk mengedit RSVP & Ucapan
Route::get('/edit/{guest:qr_code_token}', [InvitationController::class, 'edit'])->name('invitation.rsvp.edit');

// Endpoint untuk memproses update RSVP & Ucapan
Route::put('/update/{guest:qr_code_token}', [InvitationController::class, 'update'])->name('invitation.rsvp.update');


Route::get('/debug-excel/{event}', function($eventId) {
    try {
        $event = App\Models\Event::findOrFail($eventId);
        $export = new App\Exports\GuestsExport($event->id);

        // Test 1: Collection
        $collection = $export->collection();
        echo "Collection count: " . $collection->count() . "<br>";

        // Test 2: Headings
        if (method_exists($export, 'headings')) {
            echo "Headings: " . implode(', ', $export->headings()) . "<br>";
        }

        // Test 3: First mapped item
        if ($collection->isNotEmpty()) {
            $firstMapped = $export->map($collection->first());
            echo "First mapped: " . implode(', ', $firstMapped) . "<br>";
        }

        // Test 4: Direct Excel download
        echo "Testing Excel download...<br>";
        return Excel::download($export, 'test-export.xlsx');

    } catch (\Exception $e) {
        dd([
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
})->middleware('auth');

require __DIR__.'/auth.php';
