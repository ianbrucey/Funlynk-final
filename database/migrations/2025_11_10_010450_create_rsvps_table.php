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
        Schema::create('rsvps', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('user_id')
                ->constrained('users', 'id')
                ->cascadeOnDelete();

            $table->foreignUuid('activity_id')
                ->constrained('activities', 'id')
                ->cascadeOnDelete();

            $table->string('status', 20)->default('attending'); // attending, maybe, declined
            $table->boolean('is_paid')->default(false);
            $table->string('payment_intent_id')->nullable(); // Stripe PaymentIntent ID
            $table->string('payment_status', 20)->nullable(); // pending, succeeded, failed, refunded

            $table->timestampsTz();

            // Constraints
            $table->unique(['user_id', 'activity_id']);

            // Indexes
            $table->index('user_id', 'idx_rsvps_user');
            $table->index('activity_id', 'idx_rsvps_activity');
            $table->index('status', 'idx_rsvps_status');
            $table->index('payment_intent_id', 'idx_rsvps_payment_intent');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rsvps');
    }
};
