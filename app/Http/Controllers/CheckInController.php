<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Guest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class CheckInController extends Controller
{
    // Menampilkan halaman dengan kamera scanner
    public function scanner(Event $event)
    {
        return Inertia::render('CheckIn/Index', [
            'event' => [
                'id' => $event->id,
                'groom_name' => $event->groom->nickname ?? $event->groom->name,
                'bride_name' => $event->bride->nickname ?? $event->bride->name,
                'event_name' => $event->event_name,
            ]
        ]);
    }

    // Memproses hasil scan QR (via API)
    public function processScan(Request $request)
    {
        $request->validate(['qr_code_token' => 'required|string']);

        Log::info('QR Scan attempt', [
            'token' => $request->qr_code_token,
            'user_agent' => $request->header('User-Agent')
        ]);

        $guest = Guest::with('event')->where('qr_code_token', $request->qr_code_token)->first();

        // Kasus 1: Token tidak ditemukan
        if (!$guest) {
            Log::warning('QR Code not found', ['token' => $request->qr_code_token]);
            return response()->json([
                'status' => 'error',
                'message' => 'QR Code tidak valid atau sudah kedaluwarsa.'
            ], 404);
        }

        // Kasus 2: Tamu sudah check-in sebelumnya
        if ($guest->attendance_status === 'present') {
            Log::info('Guest already checked in', [
                'guest_id' => $guest->id,
                'guest_name' => $guest->name,
                'check_in_time' => $guest->check_in_time
            ]);

            return response()->json([
                'status' => 'warning',
                'message' => 'Tamu ini sudah melakukan check-in.',
                'guest' => [
                    'name' => $guest->name,
                    'check_in_time' => Carbon::parse($guest->check_in_time)->format('d/m/Y H:i:s')
                ]
            ], 200); // 200 OK, bukan 409 untuk handling yang lebih baik
        }

        // Kasus 3: Tamu dalam status pending_photo (sedang proses foto)
        if ($guest->attendance_status === 'pending_photo') {
            Log::info('Guest in pending photo status', [
                'guest_id' => $guest->id,
                'guest_name' => $guest->name
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Silakan lanjutkan untuk mengambil foto.',
                'guest' => [
                    'name' => $guest->name,
                    'photo_required' => true,
                    'guest_id' => $guest->id,
                ],
            ]);
        }

        // Kasus 4: Sukses check-in pertama kali
        $guest->attendance_status = 'pending_photo';
        $guest->check_in_time = now();
        $guest->save();

        Log::info('Guest checked in successfully', [
            'guest_id' => $guest->id,
            'guest_name' => $guest->name,
            'event_id' => $guest->event_id
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Check-in berhasil! Silakan ambil foto untuk melengkapi proses.',
            'guest' => [
                'name' => $guest->name,
                'photo_required' => true,
                'guest_id' => $guest->id,
            ],
        ]);
    }

    public function uploadPhoto(Request $request)
    {
        $request->validate([
            'guest_id' => 'required|exists:guests,id',
            'photo' => 'required|image|mimes:jpeg,png,jpg|max:5120', // Max 5MB
        ]);

        try {
            $guest = Guest::with('event')->findOrFail($request->guest_id);

            // Pastikan guest dalam status pending_photo
            if ($guest->attendance_status !== 'pending_photo') {
                Log::warning('Invalid guest status for photo upload', [
                    'guest_id' => $guest->id,
                    'current_status' => $guest->attendance_status
                ]);

                return response()->json([
                    'status' => 'error',
                    'message' => 'Status tamu tidak valid untuk upload foto.'
                ], 400);
            }

            // Hapus foto lama jika ada
            if ($guest->photo_path) {
                Storage::disk('public')->delete($guest->photo_path);
            }

            // Ambil path folder unik dari relasi event
            $eventFolderPath = $guest->event->storage_path;

            // Definisikan path untuk foto tamu
            $guestPhotoPath = $eventFolderPath . '/guest';

            $file = $request->file('photo');

            // Generate nama file dengan timestamp untuk menghindari duplikasi
            $fileName = 'checkin-' . $guest->id . '-' . now()->format('YmdHis') . '.' . $file->extension();

            // Simpan file ke path yang benar
            $path = $file->storeAs($guestPhotoPath, $fileName, 'public');

            // Update database
            $guest->photo_path = $path;
            $guest->attendance_status = 'present';
            $guest->save();

            Log::info('Photo uploaded successfully', [
                'guest_id' => $guest->id,
                'guest_name' => $guest->name,
                'photo_path' => $path,
                'file_size' => $file->getSize()
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Foto berhasil diunggah! Check-in selesai.',
                'guest' => [
                    'name' => $guest->name,
                    'check_in_time' => Carbon::parse($guest->check_in_time)->format('d/m/Y H:i:s')
                ],
                'photo_url' => Storage::url($path),
            ]);

        } catch (\Exception $e) {
            Log::error('Photo upload failed', [
                'guest_id' => $request->guest_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengunggah foto: ' . $e->getMessage()
            ], 500);
        }
    }
}
