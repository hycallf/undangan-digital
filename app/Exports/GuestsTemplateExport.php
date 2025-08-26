<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class GuestsTemplateExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles
{
    /**
     * @return array
     */
    public function array(): array
    {
        // Template data dengan contoh
        return [
            [
                'Ahmad Budi',
                '+6281234567890',
            ],
            [
                'Siti Aisyah',
                '08567890123',
            ],
            [
                'Joko Widodo',
                '081234567890',
            ],
        ];
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'Nama',
            'No. Telepon',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style header
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E2E8F0']
                ]
            ],

            // Add instructions
            'A5' => [
                'font' => ['bold' => true, 'color' => ['rgb' => '059669']],
            ]
        ];
    }
}
