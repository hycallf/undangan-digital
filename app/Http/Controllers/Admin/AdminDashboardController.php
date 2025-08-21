<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Models\Event;
use App\Models\Guest;
use Carbon\Carbon;

class AdminDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        // Ambil semua event milik user beserta relasi yang dibutuhkan
        $events = $user->events()->with(['guests', 'ceremonies'])->get();

        // 1. Hitung total event aktif
        $totalActiveEvents = $events->where('is_active', true)->count();

        $totalGuest = Guest::all()->count();


        // 2. Siapkan data untuk grafik jumlah tamu per event
        $guestCountChartData = [
            'labels' => $events->map(fn($event) => $event->groom->nickname . ' & ' . $event->bride->nickname),
            'datasets' => [
                [
                    'label' => 'Total Tamu RSVP',
                    'backgroundColor' => '#4F46E5', // Warna Indigo
                    'data' => $events->map(fn($event) => $event->guests->count()),
                ],
            ],
        ];

        // 3. Siapkan data untuk chart kehadiran
        $totalRsvp = $events->sum(fn($event) => $event->guests->count());
        $totalPresent = $events->sum(fn($event) => $event->guests->where('attendance_status', 'present')->count());

        $attendanceChartData = [
            'labels' => ['Sudah Hadir', 'Belum Hadir'],
            'datasets' => [
                [
                    'backgroundColor' => ['#10B981', '#F59E0B'], // Warna Hijau & Kuning
                    'data' => [
                        $totalPresent,
                        $totalRsvp - $totalPresent,
                    ],
                ],
            ],
        ];

        $month = $request->query('month', Carbon::now()->format('m'));
        $year  = $request->query('year', Carbon::now()->format('Y'));

        // ambil semua event beserta ceremonies
        $events = Event::with('ceremonies')->get();

        // mapping ke format fullcalendar
        $calendarEvents = $events->flatMap(function ($event) {
            return $event->ceremonies->map(function ($ceremony) use ($event) {
                $start = $ceremony->ceremony_date . ' ' . $ceremony->start_time;
                $end = $ceremony->end_time
                    ? $ceremony->ceremony_date . ' ' . $ceremony->end_time
                    : $ceremony->ceremony_date . ' 23:59:59'; // full day kalau kosong

                return [
                    'id'    => $ceremony->id,
                    'title' => ucfirst($ceremony->name) . ' - ' . $event->groom->nickname . ' & ' . $event->bride->nickname, // misal "Pernikahan A - Resepsi"
                    'start' => $start,
                    'end'   => $end,
                    // 'allDay'=> $ceremony->end_time ? false : true,
                    'extendedProps' => [
                        'event_name' => 'Pernikahan '. $event->groom->nickname . ' & ' . $event->bride->nickname,
                        'ceremony_name' => $ceremony->name,
                        'location' => $ceremony->location ?? null,
                        'maps' => $ceremony->maps_url ?? null,
                    ]
                ];
            });
        });

        return Inertia::render('Dashboard/Admin', [
            'totalActiveEvents' => $totalActiveEvents,
            'guestCountChartData' => $guestCountChartData,
            'attendanceChartData' => $attendanceChartData,
            'totalGuest' => $totalGuest,
            'calendarEvents' => $calendarEvents,

        ]);
    }
}
