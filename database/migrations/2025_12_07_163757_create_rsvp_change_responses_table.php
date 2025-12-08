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
        Schema::create('rsvp_change_responses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('rsvp_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('refund_window_id')->constrained('activity_refund_windows')->cascadeOnDelete();

            // Response: accepted, refunded, null (pending/expired)
            $table->string('response', 20)->nullable();
            $table->timestamp('responded_at')->nullable();

            // If refunded - link to transaction for refund tracking
            $table->foreignUuid('transaction_id')->nullable()->constrained()->nullOnDelete();

            // Notification tracking
            $table->timestamp('notified_at')->nullable();
            $table->uuid('notification_id')->nullable();

            $table->timestamps();

            $table->unique(['rsvp_id', 'refund_window_id']);
            $table->index('refund_window_id');
            $table->index('response');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rsvp_change_responses');
    }
};
