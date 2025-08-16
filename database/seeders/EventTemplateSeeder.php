<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\EventTemplate;

class EventTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        EventTemplate::updateOrCreate(
            ['code' => 'ulems_template'],
            [
                'name' => 'Ulems Template',
                'view_component' => 'Invitation/Templates/Ulems/UlemsTemplate',
                'thumbnail_path' => 'images/templates/ulems/ulems_thumb.jpg',
                'default_options' => [
                    "colors" => [
                        "primary" => "#1f2937",
                        "secondary" => "#f3f4f6",
                        "accent" => "#4f46e5",
                        "text_dark" => "#111827",
                        "text_light" => "#ffffff",
                        "text_muted" => "#6b7280"
                    ],
                    "fonts" => [
                        "display" => "'Sacramento', cursive",
                        // Font utama yang otomatis mendeteksi tulisan Arab
                        "body" => "'Josefin Sans', 'Noto Naskh Arabic', sans-serif"
                    ],
                    "button" => [
                        "background_color" => "var(--color-primary)",
                        "text_color" => "var(--color-text_light)",
                        "border_radius" => "9999px"
                    ]
                ]
            ]
        );

        EventTemplate::updateOrCreate(
            ['code' => 'classic_gold_template'],
            [
                'name' => 'Classic Gold Template',
                'view_component' => 'Invitation/Templates/ClassicGold/ClassicGoldTemplate',
                'thumbnail_path' => 'images/templates/ulems/ulems_thumb.jpg', // CONTOH
                'default_options' => [ /* ... */ ]
            ]
        );
    }
}
