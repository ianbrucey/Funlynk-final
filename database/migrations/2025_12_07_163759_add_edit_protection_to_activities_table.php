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
        Schema::table('activities', function (Blueprint $table) {
            // When first paid RSVP occurs, lock editing of critical fields
            $table->timestamp('edit_locked_at')->nullable()->after('status');

            // Snapshot of key fields at lock time for comparison
            // {title: '...', start_time: '...', location_name: '...', price_cents: 100}
            $table->jsonb('original_values')->nullable()->after('edit_locked_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropColumn(['edit_locked_at', 'original_values']);
        });
    }
};
