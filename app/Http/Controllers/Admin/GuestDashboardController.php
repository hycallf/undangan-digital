<?php

namespace App\Http\Controllers\Admin;

use App\Models\Event;
use App\Models\Guest;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Maatwebsite\Excel\Facades\Excel; // <-- Tambahkan ini
use Maatwebsite\Excel\Excel as ExcelType;
use App\Exports\GuestsExport;       // <-- Tambahkan ini
use App\Imports\GuestsImport;       // <-- Tambahkan ini
use Illuminate\Support\Str;
use PDF; // Dari barryvdh/laravel-dompdf
use Sabre\VObject;
use Illuminate\Support\Facades\Log;

class GuestDashboardController extends Controller
{
    use AuthorizesRequests;
    public function show(Event $event)
    {
        // Otorisasi
        $this->authorize('view', $event);

        // Load tamu dengan relasi event
        $event->load(['groom', 'bride','guests']);

        return Inertia::render('Guests/Show', [
            'event' => $event,
            'guests' => $event->guests
        ]);
    }

    public function export(Event $event, $type)
    {
        $this->authorize('view', $event);

        $fileName = 'daftar-tamu-' . Str::slug($event->groom->nickname . '-' . $event->bride->nickname);
        $guests = $event->guests()->get();

        try {
            if ($type === 'pdf') {
                // Pastikan view ada dan tidak ada error
                if (!view()->exists('pdf.guests')) {
                    // Jika view tidak ada, buat response error yang lebih jelas
                    return redirect()->back()->withErrors([
                        'export_error' => 'Template PDF belum dibuat. Silakan buat file resources/views/pdf/guests.blade.php terlebih dahulu.'
                    ]);
                }

                $pdf = PDF::loadView('pdf.guests', [
                    'guests' => $guests,
                    'event' => $event
                ]);

                return $pdf->download($fileName . '.pdf');
            }

            // PERBAIKI: Pastikan ini adalah xlsx, bukan format lain
            if ($type === 'xlsx') {

                $export = new GuestsExport($event->id);
                $collection = $export->collection();


                return Excel::download($export, $fileName . '.xlsx');
            }

            // Jika type tidak dikenali
            return redirect()->back()->withErrors([
                'export_error' => 'Format export tidak didukung: ' . $type
            ]);

        } catch (\Exception $e) {
            \Log::error('Export error: ' . $e->getMessage(), [
                'event_id' => $event->id,
                'type' => $type,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()->withErrors([
                'export_error' => 'Gagal membuat file export: ' . $e->getMessage()
            ]);
        }
    }


    // METHOD BARU UNTUK IMPORT
    public function import(Request $request, Event $event)
    {
        $this->authorize('update', $event);

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv,vcf',
            'mode' => 'required|in:add,replace', // Validasi mode import
        ]);

        // Jika mode 'replace', hapus semua tamu lama terlebih dahulu
        if ($request->mode === 'replace') {
            $event->guests()->delete();
        }

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());

        if ($extension === 'vcf') {
            // Logika untuk import VCF
            $vcardString = $file->get();
            $vcard = VObject\Reader::read($vcardString);

            if (isset($vcard->FN)) { // Handle VCF tunggal
                 $this->createGuestFromVCard($vcard, $event->id);
            } elseif (strpos($vcardString, 'BEGIN:VCARD') !== false) { // Handle VCF gabungan
                $vcards = VObject\Reader::readMulti($vcardString);
                foreach ($vcards as $vcardItem) {
                    $this->createGuestFromVCard($vcardItem, $event->id);
                }
            }

        } else {
            // Logika untuk import Excel/CSV (tetap sama)
            Excel::import(new GuestsImport($event->id), $file);
        }

        return redirect()->back()->with('success', 'Daftar tamu berhasil diimpor!');
    }

    // Helper function untuk membuat tamu dari data VCF
    private function createGuestFromVCard($vcard, $eventId) {
        $name = isset($vcard->FN) ? (string)$vcard->FN : 'Nama tidak ditemukan';
        $phone = null;
        if (isset($vcard->TEL)) {
            foreach ($vcard->TEL as $tel) {
                if (strpos($tel['TYPE'], 'cell') !== false || !isset($tel['TYPE'])) {
                    $phone = (string)$tel;
                    break;
                }
            }
        }

        Guest::create([
            'event_id' => $eventId,
            'name' => $name,
            'phone' => $phone,
            'qr_code_token' => Str::uuid()->toString(),
            'confirmation_status' => 'pending',
            'attendance_status' => 'planned',
        ]);
    }
}
