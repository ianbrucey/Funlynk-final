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
        Schema::create('post_invitations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('post_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('inviter_id')->constrained('users')->onDelete('cascade');
            $table->foreignUuid('invitee_id')->constrained('users')->onDelete('cascade');
            $table->enum('status', ['pending', 'viewed', 'reacted', 'ignored'])->default('pending');
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('viewed_at')->nullable();
            $table->timestampTz('reacted_at')->nullable();
            
            $table->unique(['post_id', 'inviter_id', 'invitee_id']);
            $table->index(['post_id']);
            $table->index(['invitee_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('post_invitations');
    }
};
