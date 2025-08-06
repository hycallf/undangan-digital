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
        Schema::create('guests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('picture')->nullable(); // Foto tamu, bisa diisi dengan URL atau path lokal
            $table->text('message')->nullable();
            $table->string('qr_code_token')->unique()->nullable(); // Token unik untuk QR code
            // Status konfirmasi dari user saat RSVP
            $table->enum('confirmation_status', ['attending', 'not_attending', 'pending'])->default('pending');
            // Status kehadiran di lokasi acara
            $table->enum('attendance_status', ['planned', 'present', 'absent'])->default('planned');
            $table->timestamp('check_in_time')->nullable(); // Waktu saat QR di-scan
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guests');
    }
};
