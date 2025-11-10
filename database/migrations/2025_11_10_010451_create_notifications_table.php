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
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('user_id')
                ->constrained('users', 'id')
                ->cascadeOnDelete();

            $table->string('type', 50); // follow, rsvp, comment, activity_update, etc.
            $table->string('title');
            $table->text('message');
            $table->jsonb('data')->nullable(); // Additional structured data
            $table->boolean('is_read')->default(false);
            $table->string('delivery_status', 20)->default('pending'); // pending, sent, failed
            $table->string('delivery_method', 20); // push, email, in_app

            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('read_at')->nullable();

            // Indexes
            $table->index('user_id', 'idx_notifications_user');
            $table->index('type', 'idx_notifications_type');
            $table->index('is_read', 'idx_notifications_is_read');
            $table->index('created_at', 'idx_notifications_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
