<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Siapa pemilik event ini
            $table->string('bride_name');
            $table->string('groom_name');
            $table->string('slug')->unique(); // Untuk URL yang cantik, misal: "pernikahan-budi-ani"
            $table->date('event_date');
            $table->text('location');
            $table->string('cover_image_path')->nullable();
            $table->string('primary_color')->default('#4a4a4a');
            $table->string('music_url')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
