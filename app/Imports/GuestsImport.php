<?php

namespace App\Imports;

use App\Models\Guest;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class GuestsImport implements ToModel, WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */

    protected $eventId;

    public function __construct(int $eventId)
    {
        $this->eventId = $eventId;
    }

    public function model(array $row)
    {
        if (!isset($row['nama'])) {
            return null;
        }

        return new Guest([
            'event_id'          => $this->eventId,
            'name'              => $row['nama'],
            'phone'             => $row['no_telepon'] ?? null,
            'qr_code_token'     => Str::uuid()->toString(), // Generate QR token otomatis
            'confirmation_status' => 'pending',
            'attendance_status' => 'planned',
        ]);
    }
}
