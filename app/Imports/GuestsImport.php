<?php

namespace App\Imports;

use App\Models\Guest;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Support\Facades\Log;

class GuestsImport implements ToModel, WithHeadingRow, WithValidation
{
    protected $eventId;

    public function __construct(int $eventId)
    {
        $this->eventId = $eventId;
    }

    /**
     * @param array $row
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        Log::info('Import row data', ['row' => $row]);

        // Skip baris kosong
        if ($this->isEmptyRow($row)) {
            Log::info('Skipping empty row');
            return null;
        }

        // Ekstrak nama dari berbagai format
        $name = $this->extractName($row);
        if (empty($name)) {
            Log::warning('Skipping row - no valid name found', ['row' => $row]);
            return null;
        }

        // Ekstrak nomor telepon dari berbagai format
        $phone = $this->extractPhone($row);

        // PERBAIKAN UTAMA: Skip jika tidak ada nomor telepon
        // Karena database constraint phone cannot be null
        if (empty($phone)) {
            Log::warning('Skipping row - no valid phone number found', [
                'name' => $name,
                'row' => $row
            ]);
            return null;
        }

        Log::info('Creating guest', [
            'name' => $name,
            'phone' => $phone,
            'event_id' => $this->eventId
        ]);

        return new Guest([
            'event_id' => $this->eventId,
            'name' => $name,
            'phone' => $phone,
            'qr_code_token' => Str::uuid()->toString(),
            'confirmation_status' => 'pending',
            'attendance_status' => 'planned',
        ]);
    }

    /**
     * Check if row is empty
     */
    private function isEmptyRow(array $row): bool
    {
        $keyFields = [
            'nama', 'name',
            'first_name', 'last_name', 'file_as',
            'full_name', 'display_name'
        ];

        foreach ($keyFields as $field) {
            if (!empty($row[$field])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Extract name from various formats
     */
    private function extractName(array $row): ?string
    {
        $namePriorities = [
            'nama',
            'name',
            'full_name',
            'display_name',
            'file_as',

            // PERBAIKAN: Handle Google Contacts format yang aneh
            // Dari log: first_name=62, last_name=813-8391-3996
            // Ini sebenarnya nomor telepon yang salah parse
            function($row) {
                $firstName = $row['first_name'] ?? '';
                $middleName = $row['middle_name'] ?? '';
                $lastName = $row['last_name'] ?? '';

                // Skip jika first_name hanya angka (kemungkinan nomor telepon)
                if (is_numeric($firstName) && empty($middleName) &&
                    (is_numeric($lastName) || preg_match('/^\d+[-\d]*$/', $lastName))) {
                    return null;
                }

                $parts = array_filter([$firstName, $middleName, $lastName]);
                return !empty($parts) ? implode(' ', $parts) : null;
            },

            'nickname',
        ];

        foreach ($namePriorities as $priority) {
            if (is_callable($priority)) {
                $name = $priority($row);
                if (!empty($name)) {
                    return trim($name);
                }
            } else {
                if (!empty($row[$priority])) {
                    $name = trim($row[$priority]);
                    // Skip jika nama hanya berisi angka atau karakter aneh
                    if (!preg_match('/^[\d\-\+\s]+$/', $name)) {
                        return $name;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Extract phone from various formats
     */
    private function extractPhone(array $row): ?string
    {
        $phonePriorities = [
            'phone',
            'no_telepon',
            'telepon',
            'no_telp',
            'phone_number',
            'mobile',
            'handphone',
            'hp',

            // Google Contacts format
            'phone_1_value',
            'phone_2_value',
            'phone_3_value',

            // Alternative Google format
            'phone_1___value',
            'phone_2___value',
            'phone_3___value',

            // PERBAIKAN: Handle case dimana nomor telepon salah parse ke first_name/last_name
            function($row) {
                $firstName = $row['first_name'] ?? '';
                $lastName = $row['last_name'] ?? '';

                // Jika first_name angka dan last_name seperti nomor telepon
                if (is_numeric($firstName) && preg_match('/^\d+[-\d]*$/', $lastName)) {
                    // Gabungkan dan bersihkan
                    $combined = $firstName . $lastName;
                    return $this->cleanPhoneNumber($combined);
                }

                return null;
            }
        ];

        foreach ($phonePriorities as $field) {
            if (is_callable($field)) {
                $phone = $field($row);
                if (!empty($phone)) {
                    return $phone;
                }
            } else {
                if (!empty($row[$field])) {
                    $phone = $this->cleanPhoneNumber($row[$field]);
                    if (!empty($phone)) {
                        return $phone;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Clean and format phone number
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

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            // Tidak ada validasi strict karena format bisa beragam
        ];
    }

    /**
     * Custom validation messages
     */
    public function customValidationMessages(): array
    {
        return [
            'required' => 'Field ini wajib diisi',
            'string' => 'Field ini harus berupa teks',
            'max' => 'Field ini maksimal :max karakter',
        ];
    }

    /**
     * Handle import failures
     */
    public function onFailure(\Maatwebsite\Excel\Validators\Failure ...$failures)
    {
        Log::error('Import failures', ['failures' => $failures]);
    }
}
