<?php

namespace App\Http\Controllers;

// app/Http/Controllers/InvitationController.php
use App\Models\Event;
use App\Models\Guest;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Spatie\IcalendarGenerator\Components\Calendar;
use Spatie\IcalendarGenerator\Components\Event as CalendarEvent;
use Carbon\Carbon;


class InvitationController extends Controller
{
    // Menampilkan halaman undangan
    // app/Http/Controllers/InvitationController.php
    public function show(Event $event, Request $request)
    {
        $event->load(['groom', 'bride', 'details', 'ceremonies', 'quotes', 'galleryPhotos', 'template', 'guests']);

        $guestName = $request->query('to');

        $templateComponent = $event->template ? $event->template->view_component : 'Invitation/Templates/Ulems/UlemsTemplate';

        // Ambil data style langsung dari relasi template yang sudah di-load
        $themeStyle = $event->template ? $event->template->default_options : [];

        $existingGuest = null;

        // Jika ada nama di URL, cari di database untuk event ini
        if ($guestName) {
            $existingGuest = Guest::where('event_id', $event->id)
                                ->where('name', $guestName)
                                ->first();
        }
        return Inertia::render($templateComponent, [
            'event' => $event,
            'themeStyle' => $themeStyle,
            'guestName' => $guestName,
            'existingGuest' => $existingGuest,
        ]);


    }

    // Menyimpan data RSVP
    public function store(Request $request, Event $event)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'confirmation_status' => 'required|in:attending,not_attending',
            'message' => 'nullable|string|max:1000',
        ]);

        // Logika untuk mencegah duplikasi (akan kita bahas di Fase 4)
        // Untuk sekarang, kita buat entri baru

        $guest = $event->guests()->create([
            'name' => $validated['name'],
            'confirmation_status' => $validated['confirmation_status'],
            'attendance_status' => $validated['confirmation_status'] === 'not_attending' ? 'absent' : 'planned',
            'message' => $validated['message'],
            'qr_code_token' => Str::uuid()->toString(), // Generate token unik
        ]);

        return redirect()->route('invitation.success', ['guest' => $guest->qr_code_token]);
    }

    // Menampilkan halaman sukses dengan QR Code
    public function success(Guest $guest)
    {
        $guest->load('event.groom', 'event.bride');

        return Inertia::render('Invitation/Success', [
            'guest' => $guest,
        ]);
    }

    public function edit(Guest $guest)
    {
        // Muat relasi event agar data event tersedia di form edit
        $guest->load('event');

        // Render halaman/komponen EditRsvp.vue dengan data guest yang ada
        return Inertia::render('Invitation/Partials/EditRsvp', [
            'guest' => $guest
        ]);
    }

    /**
     * Memproses update RSVP.
     */
    public function update(Request $request, Guest $guest)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'confirmation_status' => 'required|in:attending,not_attending',
            'message' => 'nullable|string|max:1000',
        ]);

        // Update data guest yang ada di database
        $guest->update($validated);

        // Arahkan kembali ke halaman sukses untuk menunjukkan data yang sudah diperbarui
        return redirect()->route('invitation.success', ['guest' => $guest->qr_code_token])
                         ->with('success', 'Jawaban Anda berhasil diperbarui!');
    }

    public function saveToCalendar(Event $event)
    {
        $event->load('ceremonies'); // Pastikan data acara dimuat
        $calendar = Calendar::create("Pernikahan {$event->groom->nickname} & {$event->bride->nickname}");

        foreach($event->ceremonies as $ceremony) {
            $startTime = Carbon::parse($ceremony->ceremony_date . ' ' . $ceremony->start_time);
            $endTime = $ceremony->end_time
                ? Carbon::parse($ceremony->ceremony_date . ' ' . $ceremony->end_time)
                : $startTime->copy()->addHours(2); // Default durasi 2 jam jika tidak ada end_time

            $calendar->event(
                CalendarEvent::create($ceremony->name)
                    ->startsAt($startTime)
                    ->endsAt($endTime)
                    ->address($ceremony->location . ', ' . $ceremony->address)
            );
        }

        return response($calendar->get(), 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="wedding-invitation.ics"',
        ]);
    }
}
