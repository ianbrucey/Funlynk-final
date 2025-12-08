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
        Schema::create('activity_refund_windows', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('activity_id')->constrained()->cascadeOnDelete();

            // Window timing
            $table->timestamp('triggered_at')->useCurrent();
            $table->timestamp('expires_at');

            // What triggered it
            $table->foreignUuid('trigger_edit_log_id')->nullable()->constrained('activity_edit_logs')->nullOnDelete();
            $table->jsonb('changes_summary'); // [{field: 'start_time', old: '...', new: '...'}]

            // Status: active, expired, cancelled
            $table->string('status', 20)->default('active');

            $table->timestamps();

            $table->index('activity_id');
            $table->index(['status', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_refund_windows');
    }
};
