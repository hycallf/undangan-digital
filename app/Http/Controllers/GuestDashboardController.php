<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Inertia\Inertia;

class GuestDashboardController extends Controller
{
    public function show(Event $event)
    {
        // Otorisasi
        $this->authorize('view', $event);

        // Load tamu dengan relasi event
        $event->load('guests');

        return Inertia::render('Guests/Show', [
            'event' => $event,
            'guests' => $event->guests
        ]);
    }
}
