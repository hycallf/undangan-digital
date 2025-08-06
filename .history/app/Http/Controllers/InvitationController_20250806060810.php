<?php

namespace App\Http\Controllers;

// app/Http/Controllers/InvitationController.php
use App\Models\Event;
use App\Models\Guest;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class InvitationController extends Controller
{
    // Menampilkan halaman undangan
    public function show(Event $event)
    {
        return Inertia::render('Invitation/Show', [
            'event' => $event
        ]);
    }

    // Menyimpan data RSVP
    public function store(Request $request, Event $event)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'message' => 'nullable|string',
            'confirmation_status' => 'required|in:attending,not_attending',
        ]);

        if ($request->confirmation_status === 'not_attending') {
            // Handle jika tidak hadir, mungkin hanya simpan data tanpa QR
            // Untuk sementara, kita fokus pada yang hadir
            return redirect()->back()->with('message', 'Terima kasih atas konfirmasinya.');
        }

        $guest = Guest::create([
            'event_id' => $event->id,
            'name' => $request->name,
            'message' => $request->message,
            'confirmation_status' => 'attending',
            'attendance_status' => 'planned', // Status awal: direncanakan hadir
            'qr_code_token' => Str::uuid()->toString(), // Generate token unik
        ]);

        // Redirect ke halaman sukses dengan membawa token
        return redirect()->route('rsvp.success', ['guest' => $guest->qr_code_token]);
    }

    // Menampilkan halaman sukses dengan QR Code
    public function success(Guest $guest)
    {
        return Inertia::render('Invitation/Success', [
            'guest' => $guest
        ]);
    }
}
