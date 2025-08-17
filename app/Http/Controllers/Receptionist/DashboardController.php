<?php

namespace App\Http\Controllers\Receptionist;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DashboardController extends Controller
{
    /**
     * Menampilkan daftar event yang bisa diakses oleh resepsionis.
     */
    public function index()
    {
        // Untuk sekarang, kita tampilkan semua event.
        // Nanti bisa disesuaikan agar hanya menampilkan event yang ditugaskan.
        $events = Event::with(['groom', 'bride'])->latest()->get();

        return Inertia::render('Receptionist/Dashboard', [
            'events' => $events
        ]);
    }

    /**
     * Menampilkan halaman check-in untuk event tertentu.
     */
    public function show(Event $event)
    {
        // Eager load relasi yang dibutuhkan
        $event->load(['groom', 'bride', 'guests' => function ($query) {
            $query->orderBy('check_in_time', 'desc'); // Urutkan tamu berdasarkan waktu check-in
        }]);

        return Inertia::render('Receptionist/CheckIn', [
            'event' => $event
        ]);
    }
}
