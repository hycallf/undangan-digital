<?php

namespace App\Http\Controllers;

// app/Http/Controllers/CheckInController.php
use App\Models\Event;
use App\Models\Guest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class CheckInController extends Controller
{
    // Menampilkan halaman dengan kamera scanner
    public function scanner(Event $event)
    {
        return Inertia::render('Admin/CheckIn', [
            'event' => $event
        ]);
    }

    // Memproses hasil scan QR (via API)
    public function processScan(Request $request)
    {
        $request->validate(['qr_code_token' => 'required|string']);

        $guest = Guest::where('qr_code_token', $request->qr_code_token)->first();

        // Kasus 1: Token tidak ditemukan
        if (!$guest) {
            return response()->json(['status' => 'error', 'message' => 'QR Code tidak valid.'], 404);
        }

        // Kasus 2: Tamu sudah check-in sebelumnya
        if ($guest->attendance_status === 'present') {
            return response()->json([
                'status' => 'warning',
                'message' => 'Tamu ini sudah melakukan check-in.',
                'guest' => [
                    'name' => $guest->name,
                    'check_in_time' => Carbon::parse($guest->check_in_time)->format('H:i:s')
                ]
            ], 409); // 409 Conflict
        }

        // Kasus 3: Sukses
        $guest->attendance_status = 'present';
        $guest->check_in_time = now();
        $guest->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Check-in Berhasil!',
            'guest' => [
                'name' => $guest->name
            ]
        ]);
    }
}
