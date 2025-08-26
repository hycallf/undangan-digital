<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Guest;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Jika kolom unique_identifier belum ada, tambahkan
        if (!Schema::hasColumn('guests', 'unique_identifier')) {
            Schema::table('guests', function (Blueprint $table) {
                $table->string('unique_identifier')->nullable()->after('event_id');
                $table->index(['event_id', 'unique_identifier']);
            });
        }

        // Populate unique_identifier untuk data yang sudah ada
        $this->populateExistingUniqueIdentifiers();

        // Setelah populate, buat unique_identifier required
        Schema::table('guests', function (Blueprint $table) {
            $table->string('unique_identifier')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('guests', 'unique_identifier')) {
            Schema::table('guests', function (Blueprint $table) {
                $table->dropIndex(['event_id', 'unique_identifier']);
                $table->dropColumn('unique_identifier');
            });
        }
    }

    /**
     * Populate unique identifiers untuk data existing
     */
    private function populateExistingUniqueIdentifiers(): void
    {
        $guests = Guest::whereNull('unique_identifier')->get();

        foreach ($guests as $guest) {
            $uniqueIdentifier = Guest::generateUniqueIdentifier(
                $guest->event_id,
                $guest->name,
                $guest->phone,
                $guest->id
            );

            $guest->update(['unique_identifier' => $uniqueIdentifier]);
        }
    }
};
