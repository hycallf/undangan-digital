<?php

namespace App\Exports;

use App\Models\Guest;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
// 1. Impor concern dan kelas yang dibutuhkan untuk event dan styling
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

// 2. Tambahkan interface WithEvents
class GuestsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithEvents
{
    protected $eventId;
    private $rowNumber = 1;

    public function __construct(int $eventId)
    {
        $this->eventId = $eventId;
    }

    public function collection()
    {
        return Guest::where('event_id', $this->eventId)->get();
    }

    public function headings(): array
    {
        return [
            'Nama',
            'No. Telepon',
            'Status Kehadiran', // <-- Kolom baru
        ];
    }

    public function map($guest): array
    {
        // Terjemahkan status ke Bahasa Indonesia
        $statusText = match ($guest->attendance_status) {
            'present' => 'Hadir',
            'planned' => 'Direncanakan',
            'absent' => 'Tidak Hadir',
            default => '-',
        };

        return [
            $guest->name,
            $guest->phone,
            $statusText, // <-- Tambahkan data status
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Membuat baris header (baris 1) menjadi tebal
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    // 3. Daftarkan event 'AfterSheet' untuk styling kustom
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                // Ambil semua tamu untuk event ini
                $guests = Guest::where('event_id', $this->eventId)->get();

                // Mulai dari baris ke-2 (karena baris 1 adalah header)
                $row = 2;
                foreach ($guests as $guest) {
                    $cellCoordinate = 'C' . $row; // Kolom C adalah 'Status Kehadiran'
                    $cell = $event->sheet->getDelegate()->getCell($cellCoordinate);

                    $color = match ($guest->attendance_status) {
                        'present' => 'C6EFCE', // Hijau muda
                        'planned' => 'FFEB9C', // Kuning muda
                        'absent' => 'FFC7CE',  // Merah muda
                        default => 'FFFFFF',   // Putih
                    };

                    $cell->getStyle()->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setARGB($color);

                    $row++;
                }
            },
        ];
    }
}
