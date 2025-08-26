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

    // Template pesan untuk setiap agama/tema
    public function getInvitationTemplate(): array
    {
        return match($this) {
            self::ISLAMIC => [
                'greeting' => 'Assalamu\'alaikum Warahmatullahi Wabarakatuh',
                'opening' => 'Dengan memohon Ridho dan Rahmat Allah SWT, kami mengundang Bapak/Ibu/Saudara/i untuk menghadiri acara pernikahan kami:',
                'blessing' => 'Jazakumullahu khairan atas kehadiran dan doa restunya. Barakallahu fiikum.',
                'closing' => 'Wassalamu\'alaikum Warahmatullahi Wabarakatuh'
            ],
            self::CHRISTIAN => [
                'greeting' => 'Shalom, Damai Kristus menyertai',
                'opening' => 'Dengan sukacita dan berkat Tuhan, kami mengundang Bapak/Ibu/Saudara/i untuk merayakan hari bahagia kami:',
                'blessing' => 'Kiranya kasih Kristus senantiasa menyertai kita semua. Terima kasih atas berkat dan doanya.',
                'closing' => 'Tuhan Yesus memberkati'
            ],
            self::HINDU => [
                'greeting' => 'Om Swastiastu',
                'opening' => 'Dengan memohon berkah Ida Sang Hyang Widhi Wasa, kami mengundang Bapak/Ibu/Saudara/i untuk menghadiri upacara pernikahan kami:',
                'blessing' => 'Semoga Ida Sang Hyang Widhi Wasa senantiasa melimpahkan berkah-Nya. Terima kasih atas kehadiran dan doanya.',
                'closing' => 'Om Santhi Santhi Santhi Om'
            ],
            self::BUDDHA => [
                'greeting' => 'Namo Buddhaya',
                'opening' => 'Dengan penuh syukur kepada Sang Buddha, kami mengundang Bapak/Ibu/Saudara/i untuk menghadiri perayaan pernikahan kami:',
                'blessing' => 'Semoga keberkahan dan kebahagiaan senantiasa menyertai kita semua. Sadhu Sadhu Sadhu.',
                'closing' => 'Semoga semua makhluk berbahagia'
            ],
            self::CHINESE => [
                'greeting' => '恭喜发财 (Gong Xi Fa Cai)',
                'opening' => 'Dengan hati yang penuh syukur, kami mengundang Bapak/Ibu/Saudara/i untuk menghadiri upacara pernikahan kami:',
                'blessing' => '愿你们百年好合，白头偕老 (Semoga hidup harmonis dan bahagia selamanya). Terima kasih atas kehadiran dan doanya.',
                'closing' => '祝福满满 (Penuh berkah)'
            ],
            self::GENERAL => [
                'greeting' => 'Salam sejahtera untuk kita semua',
                'opening' => 'Dengan penuh kegembiraan, kami mengundang Bapak/Ibu/Saudara/i untuk menghadiri perayaan pernikahan kami:',
                'blessing' => 'Kehadiran dan doa restu dari Anda adalah berkah terindah bagi kami.',
                'closing' => 'Dengan penuh cinta dan hormat'
            ]
        };
    }

    // Template lengkap untuk WhatsApp
    public function getWhatsAppTemplate(array $eventData): string
    {
        $template = $this->getInvitationTemplate();

        return "{$template['greeting']}\n\n" .
               "{$template['opening']}\n\n" .
               "*{$eventData['groom_name']} & {$eventData['bride_name']}*\n\n" .
               "📅 *Tanggal:* {$eventData['ceremony_date']}\n" .
               "⏰ *Waktu:* {$eventData['ceremony_time']}\n" .
               "📍 *Tempat:* {$eventData['ceremony_location']}\n" .
               "📍 *Alamat:* {$eventData['ceremony_address']}\n\n" .
               "🔗 *Link Undangan Digital:*\n{$eventData['invitation_link']}\n\n" .
               "Silakan klik link di atas untuk:\n" .
               "✅ Melihat undangan lengkap\n" .
               "✅ Konfirmasi kehadiran (RSVP)\n" .
               "✅ Melihat lokasi di peta\n" .
               "✅ Simpan ke kalender\n\n" .
               "{$template['blessing']}\n\n" .
               "{$template['closing']}\n\n" .
               "— {$eventData['groom_nickname']} & {$eventData['bride_nickname']}";
    }

    // Mendapatkan nama tema yang lebih friendly
    public function getDisplayName(): string
    {
        return match($this) {
            self::ISLAMIC => 'Islami',
            self::CHRISTIAN => 'Kristiani',
            self::HINDU => 'Hindu',
            self::BUDDHA => 'Buddha',
            self::CHINESE => 'Tionghoa',
            self::GENERAL => 'Umum'
        };
    }
}
