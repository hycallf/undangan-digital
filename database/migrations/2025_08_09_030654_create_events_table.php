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
            $table->string('event_name');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('event_template_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('groom_partner_id')->constrained('partners')->onDelete('cascade');
            $table->foreignId('bride_partner_id')->constrained('partners')->onDelete('cascade');
            $table->string('slug')->unique();
            $table->string('storage_path')->unique();
            $table->string('cover_photo_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('theme')->default(App\Enums\EventTheme::GENERAL->value);
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
