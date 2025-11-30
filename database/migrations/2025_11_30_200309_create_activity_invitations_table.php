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
        Schema::create('activity_invitations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('activity_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('inviter_id')->constrained('users')->onDelete('cascade');
            $table->foreignUuid('invitee_id')->constrained('users')->onDelete('cascade');
            $table->enum('status', ['pending', 'viewed', 'rsvped', 'ignored'])->default('pending');
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('viewed_at')->nullable();
            $table->timestampTz('rsvped_at')->nullable();

            // Unique constraint: one invitation per activity/inviter/invitee combo
            $table->unique(['activity_id', 'inviter_id', 'invitee_id'], 'activity_invitations_unique');

            // Indexes
            $table->index('activity_id', 'idx_activity_invitations_activity');
            $table->index(['invitee_id', 'status'], 'idx_activity_invitations_invitee_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_invitations');
    }
};
