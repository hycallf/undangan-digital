<?php

namespace App\Http\Controllers\Admin;

use App\Models\Event;
use App\Models\Guest;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Excel as ExcelType;
use App\Exports\GuestsExport;
use App\Exports\GuestsTemplateExport;
use App\Imports\GuestsImport;
use Illuminate\Support\Str;
use PDF; // Dari barryvdh/laravel-dompdf
use Sabre\VObject;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

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

    public function destroy(Event $event, Guest $guest)
    {
        $this->authorize('update', $event);

        // Pastikan guest belongs to event
        if ($guest->event_id !== $event->id) {
            return redirect()->back()->withErrors([
                'error' => 'Tamu tidak ditemukan dalam event ini.'
            ]);
        }

        try {
            // Hapus foto jika ada
            if ($guest->photo_path) {
                \Storage::disk('public')->delete($guest->photo_path);
            }

            $guestName = $guest->name;
            $guest->delete();

            Log::info('Guest deleted', [
                'guest_id' => $guest->id,
                'guest_name' => $guestName,
                'event_id' => $event->id,
                'deleted_by' => auth()->id()
            ]);

            return redirect()->back()->with('success', "Tamu {$guestName} berhasil dihapus.");

        } catch (\Exception $e) {
            Log::error('Failed to delete guest', [
                'guest_id' => $guest->id,
                'event_id' => $event->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()->withErrors([
                'error' => 'Gagal menghapus tamu: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * NEW: Bulk delete guests
     */
    public function bulkDelete(Request $request, Event $event)
    {
        $this->authorize('update', $event);

        $request->validate([
            'guest_ids' => 'required|array|min:1',
            'guest_ids.*' => 'required|integer|exists:guests,id'
        ]);

        try {
            DB::beginTransaction();

            $guestIds = $request->input('guest_ids');

            // Pastikan semua guest belongs to event
            $guests = Guest::whereIn('id', $guestIds)
                          ->where('event_id', $event->id)
                          ->get();

            if ($guests->count() !== count($guestIds)) {
                throw new \Exception('Beberapa tamu tidak ditemukan dalam event ini.');
            }

            $deletedCount = 0;
            $deletedNames = [];

            foreach ($guests as $guest) {
                // Hapus foto jika ada
                if ($guest->photo_path) {
                    \Storage::disk('public')->delete($guest->photo_path);
                }

                $deletedNames[] = $guest->name;
                $guest->delete();
                $deletedCount++;
            }

            DB::commit();

            Log::info('Bulk delete completed', [
                'deleted_count' => $deletedCount,
                'deleted_guests' => $deletedNames,
                'event_id' => $event->id,
                'deleted_by' => auth()->id()
            ]);

            return redirect()->back()->with('success', "{$deletedCount} tamu berhasil dihapus.");

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Bulk delete failed', [
                'guest_ids' => $request->input('guest_ids'),
                'event_id' => $event->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()->withErrors([
                'error' => 'Gagal menghapus tamu: ' . $e->getMessage()
            ]);
        }
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

            if ($type === 'template') {
                Log::info('Template export request', ['event_id' => $event->id]);

                $templateExport = new GuestsTemplateExport();
                return Excel::download($templateExport, 'template-import-tamu.xlsx');
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

    // METHOD BARU UNTUK IMPORT - DENGAN PERBAIKAN VCF DAN SWEETALERT
    public function import(Request $request, Event $event)
    {
        $this->authorize('update', $event);

        // PERBAIKAN: Custom validation untuk VCF
        $request->validate([
            'file' => [
                'required',
                'file',
                'max:10240', // 10MB
                function ($attribute, $value, $fail) {
                    $extension = strtolower($value->getClientOriginalExtension());
                    $mimeType = $value->getMimeType();

                    // Allowed extensions
                    $allowedExtensions = ['xlsx', 'xls', 'csv', 'vcf'];

                    // Allowed MIME types (VCF bisa punya berbagai MIME type)
                    $allowedMimeTypes = [
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'application/vnd.ms-excel',
                        'text/csv',
                        'text/x-vcard',
                        'text/vcard',
                        'application/octet-stream', // VCF kadang detected sebagai ini
                        'text/plain', // VCF kadang detected sebagai ini
                    ];

                    if (!in_array($extension, $allowedExtensions)) {
                        $fail('File harus berformat: ' . implode(', ', $allowedExtensions));
                        return;
                    }

                    // Khusus untuk VCF, cek isi file jika MIME type tidak cocok
                    if ($extension === 'vcf') {
                        $content = file_get_contents($value->getPathname());
                        if (!str_contains($content, 'BEGIN:VCARD')) {
                            $fail('File VCF tidak valid - tidak ditemukan format vCard yang benar.');
                            return;
                        }
                    } else {
                        // Untuk file Excel/CSV, cek MIME type
                        if (!in_array($mimeType, $allowedMimeTypes)) {
                            $fail('Format file tidak didukung. MIME type: ' . $mimeType);
                            return;
                        }
                    }
                },
            ],
            'mode' => 'required|in:add,replace',
        ]);

        try {
            $guestsBefore = $event->guests()->count();

            // Jika mode 'replace', hapus semua tamu lama terlebih dahulu
            if ($request->mode === 'replace') {
                $deletedCount = $event->guests()->count();
                $event->guests()->delete();
                Log::info("Deleted {$deletedCount} existing guests for replace mode");
                $guestsBefore = 0; // Reset counter karena data dihapus
            }

            $file = $request->file('file');
            $extension = strtolower($file->getClientOriginalExtension());
            $importedCount = 0;

            if ($extension === 'vcf') {
                // Handle VCF files
                $importedCount = $this->importVCF($file, $event->id);

            } else {
                // Handle Excel/CSV files
                Log::info('Starting Excel/CSV import', [
                    'file_name' => $file->getClientOriginalName(),
                    'extension' => $extension,
                    'event_id' => $event->id
                ]);

                // Perform import
                Excel::import(new GuestsImport($event->id), $file);

                // Count guests after import
                $guestsAfter = $event->guests()->count();
                $importedCount = $guestsAfter - $guestsBefore;

                Log::info('Import completed', [
                    'guests_before' => $guestsBefore,
                    'guests_after' => $guestsAfter,
                    'imported_count' => $importedCount
                ]);
            }

            // TAMBAHAN: Return dengan data untuk SweetAlert
            if ($importedCount > 0) {
                return redirect()->back()->with([
                    'success' => true,
                    'message' => 'Import berhasil!',
                    'imported_count' => $importedCount,
                    'mode' => $request->mode,
                    'total_guests' => $event->guests()->count()
                ]);
            } else {
                return redirect()->back()->with([
                    'warning' => true,
                    'message' => 'Import selesai, tetapi tidak ada data tamu yang valid ditemukan.',
                    'imported_count' => 0,
                    'total_guests' => $event->guests()->count()
                ]);
            }

        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            $errorMessages = [];

            foreach ($failures as $failure) {
                $errorMessages[] = "Baris {$failure->row()}: " . implode(', ', $failure->errors());
            }

            Log::error('Import validation error', ['errors' => $errorMessages]);

            return redirect()->back()->with([
                'error' => true,
                'message' => 'Error validasi data',
                'details' => $errorMessages
            ]);

        } catch (\Exception $e) {
            Log::error('Import error', [
                'event_id' => $event->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()->with([
                'error' => true,
                'message' => 'Gagal mengimpor file',
                'details' => [$e->getMessage()]
            ]);
        }
    }

    /**
     * Import VCF files - Fixed untuk Sabre VObject tanpa readMulti()
     */
    private function importVCF($file, $eventId): int
    {
        $vcardString = $file->get();
        $importedCount = 0;

        try {
            Log::info('Starting VCF import', [
                'file_size' => strlen($vcardString),
                'event_id' => $eventId
            ]);

            // Cek apakah file berisi VCARD
            if (strpos($vcardString, 'BEGIN:VCARD') === false) {
                throw new \Exception('File tidak berisi data vCard yang valid');
            }

            // Split multiple VCARDs manually
            $vcardBlocks = $this->splitVCardString($vcardString);

            Log::info('Found vCard blocks', ['count' => count($vcardBlocks)]);

            foreach ($vcardBlocks as $vcardBlock) {
                if (!empty(trim($vcardBlock))) {
                    try {
                        $vcard = VObject\Reader::read($vcardBlock);
                        if ($this->createGuestFromVCard($vcard, $eventId)) {
                            $importedCount++;
                        }
                    } catch (\Exception $e) {
                        Log::warning('Failed to parse individual vCard', [
                            'error' => $e->getMessage(),
                            'vcard_preview' => substr($vcardBlock, 0, 100)
                        ]);
                        // Continue dengan vCard berikutnya
                    }
                }
            }

            Log::info('VCF import completed', [
                'imported_count' => $importedCount,
                'event_id' => $eventId
            ]);

        } catch (\Exception $e) {
            Log::error('VCF import error', [
                'error' => $e->getMessage(),
                'event_id' => $eventId,
                'file_preview' => substr($vcardString, 0, 200)
            ]);
            throw new \Exception('Format VCF tidak valid: ' . $e->getMessage());
        }

        return $importedCount;
    }

    /**
     * Split vCard string into individual vCard blocks
     */
    private function splitVCardString($vcardString): array
    {
        $vcardBlocks = [];
        $lines = explode("\n", $vcardString);
        $currentVCard = '';
        $inVCard = false;

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === 'BEGIN:VCARD') {
                $inVCard = true;
                $currentVCard = $line . "\n";
            } elseif ($line === 'END:VCARD' && $inVCard) {
                $currentVCard .= $line . "\n";
                $vcardBlocks[] = $currentVCard;
                $currentVCard = '';
                $inVCard = false;
            } elseif ($inVCard) {
                $currentVCard .= $line . "\n";
            }
        }

        // Handle case where last vCard doesn't end properly
        if (!empty($currentVCard) && $inVCard) {
            $currentVCard .= "END:VCARD\n";
            $vcardBlocks[] = $currentVCard;
        }

        return $vcardBlocks;
    }

    /**
     * Helper function untuk membuat tamu dari data VCF - Enhanced version
     */
    private function createGuestFromVCard($vcard, $eventId): bool
    {
        try {
            // Extract name dengan berbagai cara
            $name = $this->extractNameFromVCard($vcard);

            // Skip jika tidak ada nama yang valid
            if (empty($name)) {
                Log::info('Skipping VCard - no valid name found');
                return false;
            }

            // Extract phone number
            $phone = $this->extractPhoneFromVCard($vcard);

            // Skip jika tidak ada nomor telepon yang valid
            if (empty($phone)) {
                Log::info('Skipping VCard - no valid phone found', ['name' => $name]);
                return false;
            }

            // Cek duplikat berdasarkan nama dan nomor telepon
            $existingGuest = Guest::where('event_id', $eventId)
                ->where(function($query) use ($name, $phone) {
                    $query->where('name', $name)
                          ->orWhere('phone', $phone);
                })
                ->first();

            if ($existingGuest) {
                Log::info('Skipping VCard - duplicate found', [
                    'name' => $name,
                    'phone' => $phone
                ]);
                return false;
            }

            Guest::create([
                'event_id' => $eventId,
                'name' => trim($name),
                'phone' => $phone,
                'qr_code_token' => Str::uuid()->toString(),
                'confirmation_status' => 'pending',
                'attendance_status' => 'planned',
            ]);

            Log::info('Guest created from VCard', [
                'name' => $name,
                'phone' => $phone
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Error creating guest from VCard', [
                'error' => $e->getMessage(),
                'event_id' => $eventId
            ]);
            return false;
        }
    }

    /**
     * Extract name from vCard dengan berbagai method
     */
    private function extractNameFromVCard($vcard): ?string
    {
        $name = null;

        // Method 1: FN (Formatted Name) - prioritas tertinggi
        if (isset($vcard->FN) && !empty((string)$vcard->FN)) {
            $name = (string)$vcard->FN;
        }

        // Method 2: N (Structured Name) jika FN kosong
        if (empty($name) && isset($vcard->N)) {
            $nameComponents = $vcard->N->getParts();
            $namePartsFiltered = array_filter($nameComponents, function($part) {
                return !empty(trim($part));
            });
            if (!empty($namePartsFiltered)) {
                $name = implode(' ', $namePartsFiltered);
            }
        }

        // Method 3: ORG (Organization) sebagai fallback
        if (empty($name) && isset($vcard->ORG)) {
            $name = (string)$vcard->ORG;
        }

        // Method 4: NICKNAME sebagai fallback terakhir
        if (empty($name) && isset($vcard->NICKNAME)) {
            $name = (string)$vcard->NICKNAME;
        }

        // Clean dan validate
        if (!empty($name)) {
            $name = trim($name);
            // Skip jika nama hanya berisi angka atau karakter aneh
            if (preg_match('/^[\d\-\+\s()\.]+$/', $name)) {
                return null;
            }
            return $name;
        }

        return null;
    }

    /**
     * Extract phone number from vCard
     */
    private function extractPhoneFromVCard($vcard): ?string
    {
        if (!isset($vcard->TEL)) {
            return null;
        }

        $phones = [];

        // Collect all phone numbers
        foreach ($vcard->TEL as $tel) {
            $phoneNumber = (string)$tel;
            if (!empty($phoneNumber)) {
                $cleaned = $this->cleanPhoneNumber($phoneNumber);
                if ($cleaned) {
                    // Prioritize mobile/cell phones
                    $type = isset($tel['TYPE']) ? strtolower((string)$tel['TYPE']) : '';
                    if (strpos($type, 'cell') !== false || strpos($type, 'mobile') !== false) {
                        return $cleaned; // Return immediately for mobile
                    }
                    $phones[] = $cleaned;
                }
            }
        }

        // Return first valid phone if no mobile found
        return !empty($phones) ? $phones[0] : null;
    }

    /**
     * Clean and format phone number (sama seperti di GuestsImport)
     */
    private function cleanPhoneNumber($phone): ?string
    {
        if (empty($phone)) {
            return null;
        }

        // Convert to string if numeric
        $phone = (string)$phone;

        // Hapus semua karakter non-digit kecuali +
        $cleaned = preg_replace('/[^\d+]/', '', $phone);

        // Skip jika kosong setelah dibersihkan atau terlalu pendek
        if (empty($cleaned) || strlen($cleaned) < 8) {
            return null;
        }

        // Normalisasi format Indonesia
        if (str_starts_with($cleaned, '08')) {
            $cleaned = '+62' . substr($cleaned, 1);
        } elseif (str_starts_with($cleaned, '8')) {
            $cleaned = '+62' . $cleaned;
        } elseif (str_starts_with($cleaned, '62') && !str_starts_with($cleaned, '+62')) {
            $cleaned = '+' . $cleaned;
        }

        // Validasi panjang nomor (Indonesia: +62 + 8-12 digit)
        if (str_starts_with($cleaned, '+62') && strlen($cleaned) >= 11 && strlen($cleaned) <= 15) {
            return $cleaned;
        } elseif (strlen($cleaned) >= 10 && strlen($cleaned) <= 15) {
            return $cleaned;
        }

        return null;
    }
}
