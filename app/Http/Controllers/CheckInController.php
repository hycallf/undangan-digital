<?php

namespace App\Http\Controllers;

// app/Http/Controllers/CheckInController.php
use App\Models\Event;
use App\Models\Guest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CheckInController extends Controller
{
    // Menampilkan halaman dengan kamera scanner
    public function scanner(Event $event)
    {
        return Inertia::render('CheckIn/Index', [
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
        $guest->attendance_status = 'pending_photo'; // Update status to pending photo
        $guest->check_in_time = now();
        $guest->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Check-in Berhasil! Mohon ambil foto.',
            'guest' => [
                'name' => $guest->name,
                'photo_required' => true, // Flag to indicate photo is needed
                'guest_id' => $guest->id, // Send guest ID for photo upload
            ],
        ]);
    }

    public function uploadPhoto(Request $request)
    {
        $request->validate([
            'guest_id' => 'required|exists:guests,id',
            'photo' => 'required|image|max:2048',
        ]);

        $guest = Guest::with('event')->findOrFail($request->guest_id); // Pastikan load relasi event

        // Ambil path folder unik dari relasi event
        $eventFolderPath = $guest->event->storage_path;

        // Definisikan path untuk foto tamu
        $guestPhotoPath = $eventFolderPath . '/guest';

        $file = $request->file('photo');
        $fileName = 'checkin-' . $guest->id . '-' . uniqid() . '.' . $file->extension();

        // Simpan file ke path yang benar
        $path = $file->storeAs($guestPhotoPath, $fileName, 'public');

        $guest->photo_path = $path; // Simpan path lengkapnya
        $guest->attendance_status = 'present';
        $guest->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Foto berhasil diunggah!',
            'photo_url' => Storage::url($path),
        ]);
    }
}
