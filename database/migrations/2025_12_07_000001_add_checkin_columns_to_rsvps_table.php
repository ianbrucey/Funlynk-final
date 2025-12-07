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
        Schema::table('rsvps', function (Blueprint $table) {
            $table->string('check_in_code', 6)->nullable()->comment('Unique per activity. Uppercase alphanumeric excluding O,0,I,1,L.');
            $table->uuid('qr_token')->nullable()->unique();
            $table->timestampTz('checked_in_at')->nullable();
            $table->string('check_in_method', 20)->nullable()->comment('Enum: qr_scan, manual_code, host_manual');
            $table->foreignUuid('checked_in_by')->nullable()->constrained('users')->nullOnDelete();

            // Add indexes
            $table->index('qr_token');
            $table->unique(['activity_id', 'check_in_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rsvps', function (Blueprint $table) {
            $table->dropUnique(['activity_id', 'check_in_code']);
            $table->dropIndex(['qr_token']);
            $table->dropConstrainedForeignId('checked_in_by');
            $table->dropColumn([
                'check_in_code',
                'qr_token',
                'checked_in_at',
                'check_in_method',
            ]);
        });
    }
};
