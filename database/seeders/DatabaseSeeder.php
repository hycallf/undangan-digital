<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\EventTemplate;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();
        $this->call([
            AdminUserSeeder::class,
            EventTemplateSeeder::class,
            // Tambahkan seeder lain jika diperlukan
        ]);


    }
}
