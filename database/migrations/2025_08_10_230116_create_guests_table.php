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
            $table->string('unique_identifier')->nullable();
            $table->index(['event_id', 'unique_identifier']);
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('photo_path')->nullable(); // Foto tamu, bisa diisi dengan URL atau path lokal
            $table->text('message')->nullable();
            $table->string('qr_code_token')->unique()->nullable(); // Token unik untuk QR code
            // Status konfirmasi dari user saat RSVP
            $table->enum('confirmation_status', ['pending', 'confirmed', 'declined'])
                  ->default('pending');
            $table->timestamp('confirmed_at')->nullable();

            $table->enum('invitation_status', ['pending', 'sent', 'delivered', 'failed'])
                  ->default('pending');

            $table->timestamp('invitation_sent_at')->nullable();
            // Status kehadiran di lokasi acara
            $table->enum('attendance_status', ['planned', 'pending_photo', 'present', 'absent'])->default('planned');
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
