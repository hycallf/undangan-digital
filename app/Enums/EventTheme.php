<?php

namespace App\Enums;

// Gunakan Backed Enum agar setiap case memiliki nilai string
enum EventTheme: string
{
    case ISLAMIC = 'islamic';
    case GENERAL = 'general';
    case CHRISTIAN = 'christian';
    case HINDU = 'hindu';
    case BUDDHA = 'buddha';
    case CHINESE = 'chinese';

    // Fungsi helper untuk mendapatkan semua nilai
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
