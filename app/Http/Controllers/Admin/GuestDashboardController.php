<?php

namespace App\Http\Controllers\Admin;

use App\Models\Event;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class GuestDashboardController extends Controller
{
    use AuthorizesRequests;
    public function show(Event $event)
    {
        // Otorisasi
        $this->authorize('view', $event);

        // Load tamu dengan relasi event
        $event->load('guests');

        return Inertia::render('Admin/Guests/Show', [
            'event' => $event,
            'guests' => $event->guests
        ]);
    }
}
